<?php

declare(strict_types=1);

namespace Quality\PHPStan;

use PhpParser\Node;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Stmt> */
final class BannedDomainTermsRule implements Rule
{
    /** @var array<string, array{canonical: string, note: string}> */
    private array $terms = [];

    public function __construct(string $configPath)
    {
        if (! file_exists($configPath)) {
            return;
        }

        $lines = file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            if (preg_match('/^\s+-\s*\{\s*banned:\s*"([^"]+)",\s*canonical:\s*"([^"]+)",\s*note:\s*"([^"]+)"\s*\}/', $line, $m)) {
                $this->terms[$m[1]] = ['canonical' => $m[2], 'note' => $m[3]];
            }
        }
    }

    public function getNodeType(): string
    {
        return Stmt::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $ns = $scope->getNamespace();
        if ($ns === null || ! str_contains($ns, '\\Domain')) {
            return [];
        }

        $names = match (true) {
            $node instanceof Class_ => [$node->name?->name],
            $node instanceof ClassMethod => [$node->name->name],
            $node instanceof Property => array_map(
                static fn (PropertyItem $prop): string => $prop->name->name,
                $node->props,
            ),
            default => [],
        };

        $errors = [];
        foreach (array_filter($names) as $name) {
            $lower = strtolower($name);
            foreach ($this->terms as $banned => $meta) {
                if (str_contains($lower, strtolower($banned))) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        "Banned domain term '%s' in %s — use '%s' (%s)",
                        $banned,
                        $name,
                        $meta['canonical'],
                        $meta['note'],
                    ))->identifier('bannedDomainTerm')->build();
                }
            }
        }

        return $errors;
    }
}
