<?php

declare(strict_types=1);

namespace Quality\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\While_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<ClassMethod> */
final readonly class MaxNestingLevelRule implements Rule
{
    public function __construct(private int $maxLevel = 2) {}

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

        $maxFound = $this->findMaxDepth($node->stmts, 0);

        if ($maxFound <= $this->maxLevel) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Method %s() has nesting level of %d (max %d) — extract method or invert condition.',
                $node->name->name,
                $maxFound,
                $this->maxLevel,
            ))->identifier('maxNestingLevel')->build(),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $nodes
     */
    private function findMaxDepth(array $nodes, int $currentDepth): int
    {
        $max = $currentDepth;

        foreach ($nodes as $node) {
            if (! $node instanceof Node) {
                continue;
            }

            if ($this->isNestingNode($node)) {
                $childMax = $this->findMaxDepth($this->getChildren($node), $currentDepth + 1);
                $max = max($max, $childMax);

                continue;
            }

            $max = max($max, $this->traverseSubNodes($node, $currentDepth));
        }

        return $max;
    }

    private function traverseSubNodes(Node $node, int $currentDepth): int
    {
        $max = $currentDepth;

        foreach ($node->getSubNodeNames() as $name) {
            $sub = $node->$name;

            if (is_array($sub)) {
                $max = max($max, $this->findMaxDepth($sub, $currentDepth));
            } elseif ($sub instanceof Node) {
                $max = max($max, $this->findMaxDepth([$sub], $currentDepth));
            }
        }

        return $max;
    }

    private function isNestingNode(Node $node): bool
    {
        return $node instanceof If_
            || $node instanceof For_
            || $node instanceof Foreach_
            || $node instanceof While_
            || $node instanceof Do_
            || $node instanceof Match_;
    }

    /**
     * @return list<Node>|array<Node>
     */
    private function getChildren(Node $node): array
    {
        if ($node instanceof If_) {
            $children = $node->stmts;
            foreach ($node->elseifs as $elseif) {
                $children = array_merge($children, $elseif->stmts);
            }
            if ($node->else instanceof Else_) {
                return array_merge($children, $node->else->stmts);
            }

            return $children;
        }

        if ($node instanceof For_ || $node instanceof Foreach_ || $node instanceof While_ || $node instanceof Do_) {
            return $node->stmts;
        }

        if ($node instanceof Match_) {
            $children = [];
            foreach ($node->arms as $arm) {
                $children[] = $arm->body;
            }

            return $children;
        }

        return [];
    }
}
