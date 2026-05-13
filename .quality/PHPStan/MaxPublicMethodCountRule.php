<?php

declare(strict_types=1);

namespace Quality\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Class_> */
final readonly class MaxPublicMethodCountRule implements Rule
{
    public function __construct(private int $maxMethods = 20) {}

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        $namespace = $scope->getNamespace();
        if ($namespace === null || ! str_contains($namespace, '\\Domain\\Models')) {
            return [];
        }

        $count = $this->countPublicMethods($node);
        if ($count <= $this->maxMethods) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Class %s has %d public methods (max %d) — possible god aggregate, consider splitting.',
                $node->name->name,
                $count,
                $this->maxMethods,
            ))->identifier('maxPublicMethodCount')->build(),
        ];
    }

    private function countPublicMethods(Class_ $node): int
    {
        $count = 0;
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->isPublic() && ! str_starts_with($stmt->name->name, '__')) {
                $count++;
            }
        }

        return $count;
    }
}
