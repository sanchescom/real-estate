<?php

declare(strict_types=1);

namespace Quality\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<FuncCall> */
final class NoAppHelperInDomainRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $ns = $scope->getNamespace();
        if ($ns === null || ! str_contains($ns, '\\Domain')) {
            return [];
        }

        if (! $node->name instanceof Name) {
            return [];
        }

        if ($node->name->toLowerString() !== 'app') {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'app() helper is forbidden in Domain layer — use constructor injection instead.',
            )->identifier('noAppHelperInDomain')->build(),
        ];
    }
}
