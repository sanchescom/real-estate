<?php

declare(strict_types=1);

namespace Quality\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<ClassMethod> */
final readonly class MethodLengthRule implements Rule
{
    public function __construct(private int $maxLines = 30) {}

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->stmts === null) {
            return [];
        }

        $start = $node->getStartLine();
        $end = $node->getEndLine();
        $length = $end - $start - 1; // exclude signature and closing brace

        if ($length <= $this->maxLines) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Method %s() has %d lines (max %d).',
                $node->name->name,
                $length,
                $this->maxLines,
            ))->identifier('methodLength')->build(),
        ];
    }
}
