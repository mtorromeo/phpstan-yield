<?php

declare(strict_types=1);

namespace PHPStanYield;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Yield_;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/**
 * Resolves the type of call-side yield expressions by reading the
 * `@phpstan-yield`, `@psalm-yield`, or `@yield` annotation from the
 * yielded class or any of its ancestors/interfaces.
 *
 * Example: `PromiseInterface<string>` annotated with `@yield T` causes
 * `yield $promise` to be inferred as `string`.
 */
class YieldExpressionTypeExtension implements ExpressionTypeResolverExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private PhpDocStringResolver $phpDocStringResolver,
        private FileTypeMapper $fileTypeMapper,
    ) {}

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (!$expr instanceof Yield_ || $expr->value === null) {
            return null;
        }

        $valueType = $scope->getType($expr->value);

        foreach ($valueType->getObjectClassNames() as $className) {
            $type = $this->resolveFromClassHierarchy($valueType, $className);
            if ($type !== null) {
                return $type;
            }
        }

        return null;
    }

    private function resolveFromClassHierarchy(Type $valueType, string $className): ?Type
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        /** @var array<string, true> $visited */
        $visited = [];
        $queue = [$this->reflectionProvider->getClass($className)];

        while ($queue !== []) {
            $classReflection = array_shift($queue);
            $name = $classReflection->getName();

            if (isset($visited[$name])) {
                continue;
            }
            $visited[$name] = true;

            $type = $this->resolveFromDocblock($valueType, $classReflection);
            if ($type !== null) {
                return $type;
            }

            $parent = $classReflection->getParentClass();
            if ($parent !== null) {
                $queue[] = $parent;
            }

            foreach ($classReflection->getInterfaces() as $interface) {
                $queue[] = $interface;
            }
        }

        return null;
    }

    private function resolveFromDocblock(Type $valueType, ClassReflection $classReflection): ?Type
    {
        $docComment = $classReflection->getNativeReflection()->getDocComment();
        if ($docComment === false || $docComment === '') {
            return null;
        }

        // @phpstan-ignore phpstanApi.method
        $phpDocNode = $this->phpDocStringResolver->resolve($docComment);

        foreach ($phpDocNode->getTags() as $tagNode) {
            if (
                in_array($tagNode->name, ['@phpstan-yield', '@psalm-yield', '@yield'], true)
                && $tagNode->value instanceof GenericTagValueNode
            ) {
                if ($tagNode->value->value === 'null') {
                    return null;
                }
                return $this->resolveTagType($tagNode->value->value, $valueType, $classReflection);
            }
        }

        return null;
    }

    private function resolveTagType(string $rawValue, Type $valueType, ClassReflection $classReflection): ?Type
    {
        // Resolve a fake var tag type in the context of the annotated class,
        // so that namespace imports and template types are properly handled.
        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $classReflection->getFileName(),
            $classReflection->getName(),
            null,
            null,
            sprintf('/** @var %s */', $rawValue),
        );

        $firstVarTag = current($resolvedPhpDoc->getVarTags());
        if ($firstVarTag === false) {
            return null;
        }

        $type = $firstVarTag->getType();

        // If the annotation resolved to a template type (e.g. @yield T), bind it
        // against the actual generic type arguments of the yielded value.
        if ($type instanceof TemplateType) {
            $type = $valueType->getTemplateType($classReflection->getName(), $type->getName());
        }

        if (!$type instanceof ErrorType && !$type instanceof MixedType) {
            return $type;
        }

        return null;
    }
}
