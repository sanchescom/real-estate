<?php

declare(strict_types=1);

namespace Quality\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<New_> */
final readonly class NoDomainGenericExceptionRule implements Rule
{
    /** @var list<string> */
    private const BANNED = [
        'Exception',
        'RuntimeException',
        'LogicException',
        'InvalidArgumentException',
        'DomainException',
        'UnexpectedValueException',
    ];

    public function getNodeType(): string
    {
        return New_::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $ns = $scope->getNamespace();
        if ($ns === null || ! str_contains($ns, '\\Domain')) {
            return [];
        }

        if (! $node->class instanceof Name) {
            return [];
        }

        $shortName = $node->class->getLast();

        if (! in_array($shortName, self::BANNED, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Generic exception %s is forbidden in Domain layer — use a domain-specific exception (e.g. MaximumHoldsReached).',
                $shortName,
            ))->identifier('noDomainGenericException')->build(),
        ];
    }
}
