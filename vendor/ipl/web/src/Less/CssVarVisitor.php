<?php

namespace ipl\Web\Less;

use Less_Functions;
use Less_Tree_Call;
use Less_Tree_Color;
use Less_Tree_Keyword;
use Less_Tree_Mixin_Call;
use Less_Tree_Mixin_Definition;
use Less_Tree_Variable;
use SplDoublyLinkedList;

/**
 * Replace Less color variables with CSS `var()` function calls
 *
 * Every color variable reference (even nested aliases) is replaced with a `var(--name, fallback)` call.
 * The visitor is scope-aware: it excludes mixin parameter names from replacement and disables replacement
 * inside built-in Less functions that require resolved color values.
 *
 * See the following example Less and resolved CSS for a rough overview of the visitor features.
 * Note that at-signs used for Less variables are omitted because PHPStorm interprets them as annotations.
 *
 * Less:
 * ```
 * bg-color: black;
 * text-color: white;
 *
 * body {
 *   background-color: bg-color;
 *   color: text-color;
 * }
 * ```
 *
 * CSS:
 * ```
 * body {
 *   background-color: var(--bg-color, black);
 *   color: var(--text-color, white);
 * }
 * ```
 */
class CssVarVisitor extends PreEvalVisitor
{
    /**
     * Boolean stack that gates CSS `var()` replacement during compilation
     *
     * Push `false` when you compile a context that must see resolved Less values (e.g., built-in Less functions),
     * and pop it when you leave that context. Keep at least one frame on the stack at all times.
     *
     * @var SplDoublyLinkedList<bool>
     */
    protected SplDoublyLinkedList $replaceCssVars;

    /**
     * Stack of mixin parameter names to exclude from CSS `var()` replacement
     *
     * Each frame represents one mixin definition currently being visited and contains its parameter names.
     * Frames are kept in innermost-first order: {@see visitMixinDefinition()} prepends each new frame,
     * so index 0 always holds the currently active scope. This lets {@see visitVariable()} iterate
     * forward without reversing to check the nearest enclosing scope first.
     *
     * @var list<list<string>>
     */
    protected array $mixinParams = [];

    /**
     * Handle a {@see Less_Tree_Call} node during AST traversal
     *
     * Call nodes represent both built-in Less functions (e.g., `fade()`) and CSS functions (e.g., `calc()`).
     * Less.php calls our {@see visitVariable()} for function call arguments,
     * potentially replacing them with CSS `var()` calls.
     * That behavior is desired for CSS functions such as `calc()`, but it breaks built-in Less functions
     * such as `fade()`, because they require resolved values. To preserve Less semantics,
     * this method replaces the call node that compiles twice if applicable:
     *
     * - First pass: disable CSS `var()` replacement while compiling the call (so built-in Less functions see
     *   resolved values).
     *
     * - Second pass: if compilation still returns a call node, treat it as a CSS function and recompile with
     *   CSS `var()` replacement enabled again.
     *
     * Given the context `icinga-red: red`, this ensures that:
     *
     * - `fade(icinga-red)` compiles to `fade(red)`.
     * - `calc(icinga-red)` compiles to `calc(var(--icinga-red, red))`.
     *
     * @param Less_Tree_Call $c The function call node being visited
     *
     * @return Less_Tree_Call A new call node replacing the original with adjusted `compile()` logic
     *
     * @see visitVariable() For variable replacement logic
     * @see Less_Functions For built-in Less functions
     */
    public function visitCall(Less_Tree_Call $c): Less_Tree_Call
    {
        $call = new class ($c->name, $c->args, $c->index, $c->currentFileInfo) extends Less_Tree_Call {
            /** @var SplDoublyLinkedList<bool> */
            public SplDoublyLinkedList $replaceCssVars;

            public function compile($env)
            {
                // Temporarily disable CSS `var()` replacement for current call node arguments.
                $this->replaceCssVars->push(false);
                $compiled = parent::compile($env);
                $this->replaceCssVars->pop();

                if ($compiled instanceof Less_Tree_Call) {
                    // Built-in Less functions (e.g. `fade()`) compile to a specific value (e.g. Color),
                    // whereas CSS functions (e.g. `calc()`) remain a call node after compilation.
                    // Recompile such call nodes with CSS `var()` replacement enabled for arguments.
                    // (Note: Replacement might still be disabled due to nested function calls.)
                    $compiled = parent::compile($env);
                }

                return $compiled;
            }
        };
        $call->replaceCssVars = $this->replaceCssVars;

        return $call;
    }

    /**
     * Handle a {@see Less_Tree_Variable} node during AST traversal
     *
     * Less variable nodes represent unresolved references that Less.php resolves during compilation.
     * This visitor replaces variable nodes that compile to a CSS `var()` call, unless:
     *
     * 1. The variable is a mixin parameter name.
     * 2. It is an argument to a built-in Less function.
     * 3. The resolved value is not a color or not already a CSS `var()` call.
     *
     * Mixin parameters are special: they behave like variables, but must not be replaced with CSS `var()` calls.
     * The parameter stack maintained by {@see visitMixinDefinition()} and {@see visitMixinDefinitionOut()}
     * provides context to ignore them.
     *
     * @param Less_Tree_Variable $v The variable node being visited
     *
     * @return Less_Tree_Variable The original variable if it is a mixin parameter; otherwise a
     *   replacement variable node that compiles to a CSS `var()` call if applicable
     *
     * @see visitCall() For disabling replacement in built-in Less functions
     * @see visitMixinDefinition() For mixin parameter stack setup
     * @see visitMixinDefinitionOut() For mixin parameter stack teardown
     */
    public function visitVariable(Less_Tree_Variable $v): Less_Tree_Variable
    {
        foreach ($this->mixinParams as $ignoreVars) {
            if (in_array($v->name, $ignoreVars, true)) {
                return $v;
            }
        }

        $variable = new class ($v->name, $v->index, $v->currentFileInfo) extends Less_Tree_Variable {
            /** @var SplDoublyLinkedList<bool> */
            public SplDoublyLinkedList $replaceCssVars;

            public function compile($env)
            {
                $compiled = parent::compile($env);

                // Do not replace variable with CSS `var()` function call if...
                if (
                    // ... replacing CSS vars is disabled because a function call is compiled,
                    ! $this->replaceCssVars->top()
                    // ... or the compiled variable is neither a color nor a CSS `var()` call.
                    || (
                        ! $compiled instanceof Less_Tree_Color
                        && (! $compiled instanceof Less_Tree_Call || $compiled->name !== 'var')
                    )
                ) {
                    return $compiled;
                }

                // Remove '@' from name.
                $name = substr($this->name, 1);

                if ($name[0] === '@') {
                    // Evaluate variable variable as in Less_Tree_Variable.
                    $name = (new Less_Tree_Variable($name, $this->index + 1, $this->currentFileInfo))
                        ->compile($env)
                        ->value;
                }

                $args = [
                    'var',
                    [
                        new Less_Tree_Keyword("--{$name}"),
                        $compiled,
                    ],
                    $this->index,
                ];

                // No need to call `compile()` on the new call node (replacing the variable node),
                // as it's not a built-in Less function, `Less_Tree_Keyword::compile()` is a no-op,
                // and it wraps the already compiled variable node.
                return new Less_Tree_Call(...$args);
            }
        };
        $variable->replaceCssVars = $this->replaceCssVars;

        return $variable;
    }

    /**
     * Handle a {@see Less_Tree_Mixin_Definition} node during AST traversal
     *
     * Less.php does not visit default parameter values, which would leave
     * `.mixin(@color: @icinga-red)` producing `var(--color, …)` instead of `var(--icinga-red, …)`.
     *
     * This method works around the traversal gap by pushing the mixin's parameter names onto a stack and
     * manually visiting each parameter value. For all frames in the stack, {@see visitVariable()}
     * returns parameter names unchanged and only rewrites parameter values.
     *
     * Simplified AST traversal context:
     * ```
     * MixinDefinition(.mixin) ← visitMixinDefinition()
     * └─ Parameters
     *    ├─ Variable (name: color) ← instrument visitVariable() call to ignore this variable
     *    └─ Variable (value: icinga-red) ← force visitVariable() call
     * visitMixinDefinitionOut() revert instrumentation
     * ```
     *
     * @param Less_Tree_Mixin_Definition $d The mixin definition node being visited
     *
     * @return Less_Tree_Mixin_Definition The original mixin definition node (modified in-place)
     *
     * @see visitMixinDefinitionOut() For cleanup after traversal
     * @see visitVariable() For replacement logic that checks exclusion stack
     */
    public function visitMixinDefinition(Less_Tree_Mixin_Definition $d): Less_Tree_Mixin_Definition
    {
        // Less_Tree_Mixin_Definition::accept() does not visit parameters, but we have to replace them if necessary.
        foreach ($d->params as &$p) {
            if (isset($p['value'])) {
                $p['value'] = $this->visitObj($p['value']);
            }
        }
        unset($p);

        array_unshift($this->mixinParams, array_column($d->params, 'name'));

        return $d;
    }

    /**
     * Leave a {@see Less_Tree_Mixin_Definition} node
     *
     * Removes the current mixin's parameter names from {@see $mixinParams},
     * restoring the parent scope.
     *
     * @return void
     */
    public function visitMixinDefinitionOut(): void
    {
        array_shift($this->mixinParams);
    }

    /**
     * Handle a {@see Less_Tree_Mixin_Call} node during AST traversal
     *
     * Since Less.php does not visit arguments of mixin calls, this visitor forces each argument to be visited
     * to trigger our {@see visitVariable()} so that arguments are replaced by CSS `var()` calls if applicable,
     * e.g., `.mixin(icinga-red)` → `.mixin(var(--icinga-red, ...))`.
     *
     * Simplified AST traversal context:
     * ```
     * MixinCall (.mixin) ← visitMixinCall()
     * └─ Arguments
     *     └─ Variable (icinga-red) ← force visitVariable() call
     * ```
     *
     * @param Less_Tree_Mixin_Call $c The mixin call node being visited
     *
     * @return Less_Tree_Mixin_Call The original mixin call node (modified in-place)
     */
    public function visitMixinCall(Less_Tree_Mixin_Call $c): Less_Tree_Mixin_Call
    {
        // Less_Tree_Mixin_Call::accept() does not visit arguments, but we have to replace them if necessary.
        foreach ($c->arguments as &$a) {
            $a['value'] = $this->visitObj($a['value']);
        }
        unset($a);

        return $c;
    }

    /**
     * Set up the CSS `var()` replacement state stack
     *
     * Creates {@see $replaceCssVars} with an initial `true` frame so that replacement
     * is enabled at the start of each traversal.
     *
     * @return void
     *
     * @see visitCall() Which pushes/pops frames to disable replacement inside built-in Less functions
     */
    protected function init(): void
    {
        $this->replaceCssVars = new SplDoublyLinkedList();
        // Enable CSS `var()` replacement.
        $this->replaceCssVars->push(true);
    }

    /**
     * Deep-copy object-type state when this visitor is cloned by {@see PreEvalVisitor::run()}
     *
     * {@see $replaceCssVars} is a {@see SplDoublyLinkedList} and would otherwise be shared
     * between the prototype and the clone. Cloning it ensures each traversal starts with
     * its own independent stack.
     *
     * @return void
     */
    public function __clone(): void
    {
        $this->replaceCssVars = clone $this->replaceCssVars;
    }
}
