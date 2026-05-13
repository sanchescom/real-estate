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
final readonly class MaxParameterCountRule implements Rule
{
    public function __construct(private int $maxParams = 3) {}

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->name === '__construct') {
            return [];
        }

        $count = count($node->params);

        if ($count <= $this->maxParams) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Method %s() has %d parameters (max %d) — introduce a DTO or config object.',
                $node->name->name,
                $count,
                $this->maxParams,
            ))->identifier('maxParameterCount')->build(),
        ];
    }
}
