<?php

namespace ipl\Web\Less;

use Less_Parser;
use Less_Tree;
use Less_VisitorReplacing;

/**
 * Base class for Less pre-evaluation AST visitors
 *
 * Subclasses hook into the pre-`compile()` phase of the Less parser,
 * receiving the raw parsed AST before variables, functions, and mixins are resolved.
 * Override {@see init()} to set up any state the visitor needs before traversal.
 *
 * {@see run()} clones the instance before each traversal so that the clean state
 * established by {@see init()} is preserved across multiple runs. The same instance
 * can therefore be safely passed to multiple parsers without state from one traversal
 * leaking into the next.
 *
 * Subclasses whose {@see init()} assigns object-type properties must also implement
 * `__clone()` to deep-copy those objects, as PHP's default `clone` is shallow and
 * would otherwise leave both copies sharing the same object reference.
 */
abstract class PreEvalVisitor extends Less_VisitorReplacing
{
    /**
     * Control the visitor execution phase
     *
     * When `true`, this visitor runs in the pre-`compile()` phase,
     * receiving the raw parsed AST before variables, functions, and mixins are resolved.
     *
     * @phpstan-ignore property.neverRead (Used by {@see Less_Parser::PreVisitors()})
     *
     * @var true
     *
     * @link https://github.com/wikimedia/less.php/blob/v5.5.0/lib/Less/Parser.php#L405
     */
    public readonly true $isPreEvalVisitor;

    public function __construct()
    {
        parent::__construct();

        $this->isPreEvalVisitor = true;
        $this->init();
    }

    /**
     * Workaround for a port bug in wikimedia/less.php
     *
     * {@see Less_Tree_Import::accept()} calls `$visitor->visit($this->root)` instead
     * of `$visitor->visitObj($this->root)`. In less.js, the visitor's main
     * type-dispatch method is named `visit()`; the PHP port renamed it to `visitObj()`
     * but missed this one call site in `Import::accept()`, leaving it referencing a
     * method that does not exist on {@see Less_VisitorReplacing}.
     *
     * Pre-eval visitors are the only ones affected: {@see Less_ImportVisitor} runs
     * first and populates `Import::$root` with each imported file's parsed ruleset, so
     * the `$root` branch in `accept()` is always taken during pre-eval traversal.
     * Post-eval visitors never hit it because `$root->compile()` inlines the import
     * contents beforehand, dissolving the `Import` nodes entirely.
     *
     * Delegates to {@see visitObj()} to preserve normal type dispatch.
     *
     * @param Less_Tree $node The imported file's root ruleset
     *
     * @return Less_Tree The visited node, potentially replaced
     *
     * @link https://github.com/wikimedia/less.php/blob/v5.5.0/lib/Less/Tree/Import.php#L84
     */
    public function visit(Less_Tree $node): Less_Tree
    {
        return $this->visitObj($node);
    }


    /**
     * Visitor entrypoint called by {@see Less_Parser::PreVisitors()}
     *
     * Clones this instance for a fresh copy of the construction-time state established by
     * {@see init()}, starts traversal on the clone, and leaves the original untouched.
     * Subclasses may override this to intentionally modify `$tree` (e.g., injecting helper nodes
     * before traversal) — see {@see DetachedRulesetCallVisitor::run()} for an example.
     *
     * @param Less_Tree $tree The root AST node to traverse
     *
     * @return void
     *
     * @link https://github.com/wikimedia/less.php/blob/v5.5.0/lib/Less/Parser.php#L406
     */
    public function run(Less_Tree $tree): void
    {
        $clone = clone $this;
        $clone->visitObj($tree);
    }

    /**
     * Set up visitor state at construction time
     *
     * Called once from {@see __construct()}; {@see run()} clones the instance before each
     * traversal so this state serves as the clean starting point for every run. Subclasses
     * assigning object-type properties must implement `__clone()` to deep-copy them.
     *
     * @return void
     */
    protected function init(): void
    {
    }
}
