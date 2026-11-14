<?php

namespace ipl\Web\Less;

use InvalidArgumentException;
use Less_Exception_Parser;
use Less_Parser;
use Less_Tree;
use Less_Tree_Declaration;
use Less_Tree_DetachedRuleset;
use Less_Tree_Element;
use Less_Tree_Mixin_Call;
use Less_Tree_Mixin_Definition;
use Less_Tree_Ruleset;
use RuntimeException;

/**
 * Expand a named detached ruleset declaration into a Less template that calls it
 *
 * When a Less variable with the configured name is assigned a detached ruleset, this visitor
 * replaces that declaration with the result of parsing a provided Less template, using `{ruleset}`
 * as the placeholder for the detached ruleset call. The variable may appear in multiple Less files
 * or scopes; each occurrence is transformed independently into the configured template.
 *
 * See the following example Less and resolved CSS for a rough overview of the visitor features.
 * Note that at-signs used for Less variables and media rules are omitted because PHPStorm interprets them as
 * annotations.
 *
 * PHP — visitor is configured with a variable name and a Less template containing `{ruleset}`:
 * ```
 * new DetachedRulesetCallVisitor('screen-only', 'media screen { {ruleset} }')
 * ```
 *
 * Less — `screen-only` can be defined in multiple scopes; each is expanded independently:
 * ```
 * screen-only: {
 *     color: red;
 * }
 * ```
 *
 * CSS:
 * ```
 * media screen {
 *     color: red;
 * }
 * ```
 */
class DetachedRulesetCallVisitor extends PreEvalVisitor
{
    /**
     * Parsed template mixin definition, prepended to each new tree's rules by {@see run()}
     *
     * The definition must remain in the AST after traversal so the Less compiler can resolve the
     * mixin calls that {@see visitDeclaration()} generates in place of matched declarations.
     *
     * @var Less_Tree_Mixin_Definition
     */
    private readonly Less_Tree_Mixin_Definition $mixinDef;

    /**
     * Create a new DetachedRulesetCallVisitor
     *
     * @param string $variableName The Less variable name (without `@`) whose value is the detached ruleset
     * @param string $template Less code to parse and inject, with `{ruleset}` as the placeholder for
     *   the detached ruleset call — must be a developer-controlled constant, not user input
     *   (the template is parsed as Less and could execute arbitrary Less if untrusted)
     *
     * @throws RuntimeException If a temporary file cannot be created or written to, or if the template cannot be parsed
     */
    public function __construct(
        protected readonly string $variableName,
        string $template,
    ) {
        parent::__construct();

        try {
            $this->mixinDef = $this->buildMixinDef($template);
        } catch (Less_Exception_Parser $e) {
            throw new RuntimeException(
                "Failed to parse template for detached ruleset: {$e->getMessage()}",
                $e->getCode(),
                $e,
            );
        }
    }

    /**
     * Build the mixin definition from the template
     *
     * Parses the template into a {@see Less_Tree_Mixin_Definition} so that
     * {@see visitDeclaration()} can call it wherever the configured variable is
     * assigned a detached ruleset. The definition is injected at the root AST by {@see run()}.
     *
     * @return Less_Tree_Mixin_Definition The mixin definition built from the template
     *
     * @throws RuntimeException If a temporary file cannot be created or written to
     * @throws Less_Exception_Parser If the template cannot be parsed
     */
    protected function buildMixinDef(string $template): Less_Tree_Mixin_Definition
    {
        $less = str_replace('{ruleset}', '@rules()', $template);
        // Less_Parser::parse() returns $this and does not expose the root ruleset.
        // parseFile(returnRoot: true) is the only public API that does, so we write
        // the content to a temp file and let the parser read it back.
        $t = tmpfile();
        if ($t === false) {
            throw new RuntimeException('Failed to create a temporary file for Less compilation');
        }

        try {
            if (fwrite($t, $less) === false) {
                throw new RuntimeException('Failed to write to temporary file for Less compilation');
            }

            // Less_Tree::$parse holds the active Less_Parser instance set during compilation.
            // @link https://github.com/wikimedia/less.php/blob/v5.5.0/lib/Less/Tree.php
            // Using it here avoids constructing a new Less_Parser(), which would itself overwrite
            // Less_Tree::$parse, resetting the active parser's configuration (import paths,
            // variables, etc.) for the remainder of the current compilation.
            // Fallback: if called outside a compilation context (Less_Tree::$parse is null),
            // a bare parser is used and the template is parsed without inherited options.
            $root = (Less_Tree::$parse ?? new Less_Parser())
                ->parseFile(stream_get_meta_data($t)['uri'], returnRoot: true);
        } finally {
            fclose($t);
        }

        return new Less_Tree_Mixin_Definition(
            '.' . uniqid($this->variableName),
            [['name' => '@rules']],
            $root->rules,
            null,
        );
    }

    /**
     * Inject the template mixin definition at the root of the AST before traversal
     *
     * Unlike {@see PreEvalVisitor::run()}, this override intentionally modifies `$tree->rules`:
     * it prepends {@see $mixinDef} when the definition is not yet present in the tree, so the
     * Less compiler can resolve the mixin calls that {@see visitDeclaration()} generates in place
     * of matched declarations. Presence is checked by object identity, so the same tree can be
     * passed more than once without duplicate injection, and a fresh tree always receives the
     * definition regardless of how many other trees this visitor has already processed.
     *
     * @param Less_Tree $tree The root AST node to traverse
     *
     * @return void
     *
     * @throws InvalidArgumentException If the root node is not a {@see Less_Tree_Ruleset}
     */
    public function run(Less_Tree $tree): void
    {
        if (! $tree instanceof Less_Tree_Ruleset) {
            throw new InvalidArgumentException(sprintf(
                '%s can only be run on %s instances',
                __METHOD__,
                Less_Tree_Ruleset::class,
            ));
        }

        $clone = clone $this;

        if (! in_array($this->mixinDef, $tree->rules, true)) {
            array_unshift($tree->rules, $this->mixinDef);
        }

        $clone->visitObj($tree);
    }

    /**
     * Handle a {@see Less_Tree_Declaration} node during AST traversal
     *
     * Leaves most declarations unchanged. When it encounters a variable declaration matching
     * {@see $variableName} whose value is a detached ruleset, it replaces that declaration with
     * a call to the template mixin, passing the detached ruleset directly as the argument.
     * The mixin definition itself is already in the root AST, injected by {@see run()}.
     *
     * @param Less_Tree_Declaration $d The declaration node being visited
     *
     * @return Less_Tree_Declaration|Less_Tree_Mixin_Call The original node, or the mixin call that replaces it
     */
    public function visitDeclaration(Less_Tree_Declaration $d): Less_Tree_Declaration|Less_Tree_Mixin_Call
    {
        if (
            $d->variable
            && $d->name === "@{$this->variableName}"
            && $d->value instanceof Less_Tree_DetachedRuleset
        ) {
            return new Less_Tree_Mixin_Call(
                [new Less_Tree_Element(null, $this->mixinDef->name)],
                [['name' => null, 'value' => $d->value]],
                $d->index,
                $d->currentFileInfo,
            );
        }

        return $d;
    }
}
