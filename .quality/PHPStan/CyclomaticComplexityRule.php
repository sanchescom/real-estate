<?php

declare(strict_types=1);

namespace Quality\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<ClassMethod> */
final readonly class CyclomaticComplexityRule implements Rule
{
    /** @var array<class-string<Node>, true> */
    private const BRANCH_NODES = [
        If_::class => true,
        ElseIf_::class => true,
        For_::class => true,
        Foreach_::class => true,
        While_::class => true,
        Do_::class => true,
        Catch_::class => true,
        Ternary::class => true,
        BooleanAnd::class => true,
        BooleanOr::class => true,
        LogicalAnd::class => true,
        LogicalOr::class => true,
    ];

    public function __construct(private int $threshold = 10) {}

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

        $cc = 1 + $this->countDecisionPoints($node);

        if ($cc <= $this->threshold) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Method %s() has cyclomatic complexity of %d (max %d).',
                $node->name->name,
                $cc,
                $this->threshold,
            ))->identifier('cyclomaticComplexity')->build(),
        ];
    }

    private function countDecisionPoints(ClassMethod $method): int
    {
        $count = 0;
        $finder = new NodeFinder;

        $nodes = $finder->find($method->stmts ?? [], static fn (Node $n): bool => isset(self::BRANCH_NODES[$n::class])
            || $n instanceof Case_
            || $n instanceof Match_);

        foreach ($nodes as $found) {
            $count += match (true) {
                $found instanceof Match_ => count($found->arms),
                $found instanceof Case_ && ! $found->cond instanceof Expr => 0,
                default => 1,
            };
        }

        return $count;
    }
}
