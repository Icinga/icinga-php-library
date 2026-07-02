<?php

namespace ipl\Web\Common;

use InvalidArgumentException;
use Stringable;

/**
 * Content Security Policy (CSP) header
 *
 * Provides an additive builder for CSP headers: directives and their expressions are
 * accumulated via {@see add()}, with duplicates silently ignored. Expressions are
 * validated against the CSP spec: {@link https://www.w3.org/TR/CSP3/}
 *
 * Fetch directives fall back through their inheritance chain to `default-src` when
 * queried via {@see getDirective()}, but {@see getRawDirective()} and
 * {@see hasRawDirective()} skip inheritance.
 *
 * The first nonce encountered across all directives is exposed via {@see getNonce()}.
 *
 * {@see fromString()} parses an existing CSP header string back into a `Csp` instance,
 * and {@see merge()} combines multiple `Csp` instances into one, deduplicating
 * expressions and preserving the base nonce.
 */
class Csp implements Stringable
{
    /** @var list<string> The hash algorithms that can be used in the Csp header */
    protected const AVAILABLE_HASHES = [
        'sha256',
        'sha384',
        'sha512',
    ];

    /** @var list<string> The keywords that can be used in the Csp header */
    protected const AVAILABLE_KEYWORDS = [
        "'self'",
        "'none'",
        "'strict-dynamic'",
        "'report-sample'",
        "'unsafe-inline'",
        "'unsafe-eval'",
        "'unsafe-hashes'",
        "'wasm-unsafe-eval'",
        "'allow-duplicates'",
    ];

    /** @var list<string> The keywords that can be used in the Csp header without quotes */
    protected const AVAILABLE_UNQUOTED_KEYWORDS = [
        'allow-downloads',
        'allow-forms',
        'allow-modals',
        'allow-orientation-lock',
        'allow-pointer-lock',
        'allow-popups',
        'allow-popups-to-escape-sandbox',
        'allow-presentation',
        'allow-same-origin',
        'allow-scripts',
        'allow-storage-access-by-user-activation',
        'allow-top-navigation',
        'allow-top-navigation-by-user-activation',
        'allow-top-navigation-to-custom-protocols',
    ];

    /** @var array<string, string> The characters that can't appear in expressions */
    protected const EXPRESSION_FORBIDDEN_CHARACTERS = [
        ';'  => ';',
        "\r" => '\r',
        "\n" => '\n',
        "\t" => '\t',
        "\0" => '\0',
    ];

    /**
     * Fetch directive inheritance map
     *
     * All fetch directives eventually have the `default-src` directive as their parent.
     *
     * @var array<string, ?string>
     */
    protected const FETCH_DIRECTIVES = [
        'child-src' => 'default-src',
        'connect-src' => 'default-src',
        'default-src' => null,
        'fenced-frame-src' => 'default-src',
        'font-src' => 'default-src',
        'frame-src' => 'default-src',
        'img-src' => 'default-src',
        'manifest-src' => 'default-src',
        'media-src' => 'default-src',
        'object-src' => 'default-src',
        'prefetch-src' => 'default-src',
        'script-src' => 'default-src',
        'script-src-attr' => 'script-src',
        'script-src-elem' => 'script-src',
        'style-src' => 'default-src',
        'style-src-attr' => 'style-src',
        'style-src-elem' => 'style-src',
    ];

    /** @var list<string> The directives that must be empty */
    protected const MANDATORY_EMPTY_DIRECTIVES = [
        'block-all-mixed-content',
        'upgrade-insecure-requests',
    ];

    /** @var list<string> The directives that can be empty */
    protected const POSSIBLE_EMPTY_DIRECTIVES = [
        'sandbox',
    ];

    /** @var array<string, list<string>> The directives and their values */
    protected array $directives = [];

    /**
     * First nonce found in the directives
     *
     * This assumes there will only ever be one nonce.
     *
     * @var ?string
     */
    protected ?string $nonce = null;

    /**
     * Create a new CSP from a string
     *
     * @param string $header The CSP header string
     *
     * @return static A new CSP containing all directives from the input header
     *
     * @throws InvalidArgumentException If the input header contains an invalid
     *   directive or expression.
     */
    public static function fromString(string $header): static
    {
        $header = str_replace(["\r\n", "\n", "\r"], ' ', $header);
        $result = new static();
        foreach (explode(';', $header) as $directive) {
            $directive = trim($directive);
            if (empty($directive)) {
                continue;
            }

            $parts = preg_split('/\s+/', $directive, 2);
            if ($parts === false) {
                throw new InvalidArgumentException("Failed to split directive: $directive");
            }

            if (count($parts) === 1) {
                $name = $parts[0];
                if (! $result->canDirectiveBeEmpty($name)) {
                    throw new InvalidArgumentException(
                        "Directives must contain the directive name and at least one expression. Directive: $directive"
                    );
                }

                $result->add($name, null);
            } else {
                $result->add(...$parts);
            }
        }

        return $result;
    }

    /**
     * Check whether a given directive can be empty
     *
     * Only a subset of directives can be empty. Allowing them to be empty does not mean
     * they cannot have a value, only that it can be omitted.
     *
     * @param string $directive The directive name
     *
     * @return bool
     */
    protected function canDirectiveBeEmpty(string $directive): bool
    {
        return in_array($directive, static::POSSIBLE_EMPTY_DIRECTIVES, true)
            || in_array($directive, static::MANDATORY_EMPTY_DIRECTIVES, true);
    }

    /**
     * Add a directive with an expression or a list of expressions to the CSP
     *
     * @param string $directive The directive name
     * @param string|list<string>|null $value The expression or list of expressions to
     *   add. Note that adding multiple expressions with an array or a space-separated
     *   string is equivalent to adding each expression individually one by one, the
     *   behavior is therefore not atomic.
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the directive name, expression, or directive
     *   value state is invalid.
     */
    public function add(string $directive, string|array|null $value): static
    {
        if (! preg_match('/^[a-z\-]+$/', $directive)) {
            throw new InvalidArgumentException(
                "Directive names contain only lowercase letters and '-'. Directive: $directive",
            );
        }

        if ($value !== null && in_array($directive, static::MANDATORY_EMPTY_DIRECTIVES, true)) {
            throw new InvalidArgumentException(
                "Directive $directive can't have a value."
            );
        }

        if ($value === null) {
            if (! $this->canDirectiveBeEmpty($directive)) {
                throw new InvalidArgumentException(
                    "Directive $directive can't be empty."
                );
            }
            $this->directives[$directive] ??= [];
        } elseif (is_string($value)) {
            $value = trim($value, ' ');

            if (str_contains($value, ' ')) {
                $values = preg_split('/\s+/', trim($value));
                if ($values === false) {
                    throw new InvalidArgumentException("Failed to split expression: $value");
                }

                return $this->add($directive, $values);
            }

            $this->validateExpression($directive, $value);

            if (in_array($value, $this->directives[$directive] ?? [], true)) {
                return $this;
            }

            if ($this->nonce === null && $this->isNonce($value)) {
                $this->nonce = substr($value, 7, -1);
            }

            $this->directives[$directive][] = $value;
        } else {
            if ($value === []) {
                return $this->add($directive, null);
            }
            foreach ($value as $expression) {
                $this->add($directive, $expression);
            }
        }

        return $this;
    }

    /**
     * Validate an expression
     *
     * Throws an exception if the expression is invalid.
     *
     * @param string $directive The directive name
     * @param string $expression The expression to validate
     *
     * @return void
     *
     * @throws InvalidArgumentException If the expression is invalid.
     */
    protected function validateExpression(string $directive, string $expression): void
    {
        if ($expression === '') {
            throw new InvalidArgumentException('Expression must not be empty.');
        }

        if ($expression === '*') {
            return;
        }

        foreach (static::EXPRESSION_FORBIDDEN_CHARACTERS as $char => $str) {
            if (str_contains($expression, $char)) {
                throw new InvalidArgumentException(
                    sprintf("Expression must not contain '%s'.", $str),
                );
            }
        }

        // Reporting names
        if ($directive === 'report-to' && preg_match('/^[a-zA-Z0-9_-]+$/', $expression)) {
            return;
        }

        if (in_array($expression, static::AVAILABLE_UNQUOTED_KEYWORDS, true)) {
            return;
        }

        if (
            (str_starts_with($expression, "'") && ! str_ends_with($expression, "'"))
            || ! str_starts_with($expression, "'") && str_ends_with($expression, "'")
        ) {
            throw new InvalidArgumentException(
                "Quoted expression must be fully surrounded by single quotes. Expression: $expression",
            );
        }

        if (str_starts_with($expression, "'") && str_ends_with($expression, "'")) {
            if (str_starts_with($expression, "'nonce-")) {
                if (strlen($expression) < 9) {
                    throw new InvalidArgumentException("Nonce must have a value. Expression: $expression");
                }

                return;
            }

            if (str_starts_with($expression, "'report-") && $expression !== "'report-sample'") {
                foreach (static::AVAILABLE_HASHES as $hash) {
                    if ($expression === sprintf("'report-%s'", $hash)) {
                        return;
                    }
                }
                throw new InvalidArgumentException("Unsupported hash type. Expression: $expression");
            }

            if (preg_match('/^\'([a-zA-Z0-9]+)-/', $expression, $matches)) {
                $hash = $matches[1];
                if (in_array($hash, static::AVAILABLE_HASHES, true)) {
                    if (strlen($expression) <= strlen($hash) + 3) {
                        throw new InvalidArgumentException("Hash must have a value. Expression: $expression");
                    }

                    return;
                }
            }

            if (! in_array($expression, static::AVAILABLE_KEYWORDS, true)) {
                throw new InvalidArgumentException("Unsupported keyword. Expression: $expression");
            }

            return;
        }

        // scheme: and scheme://*
        if (preg_match('/^[a-z]+:(\/\/\*)?$/', $expression)) {
            return;
        }

        preg_match(
            '/^(?:(?<scheme>[a-z]+):\/\/)?(?<host>(?:[a-z0-9_*-]+\.)*[a-z0-9*]+)'
            . '(?::(?<port>-?[0-9]+|\*))?(?<path>(?:\/[a-z0-9%_.\-*]+)*)\/?$/i',
            $expression,
            $parsedUrl,
        );

        if (($parsedUrl['host'] ?? '') === '') {
            throw new InvalidArgumentException("Expression URL must specify a host. Expression: $expression");
        }

        if (substr_count($parsedUrl['host'], '*') > 1) {
            throw new InvalidArgumentException(
                "Expression URL can't contain more than one wildcard. Expression: $expression",
            );
        }

        if (str_starts_with($parsedUrl['host'], '*')) {
            if (! str_starts_with($parsedUrl['host'], '*.')) {
                throw new InvalidArgumentException("Wildcard host must be a full subdomain. Expression: $expression");
            }
        } elseif (str_contains($parsedUrl['host'], '*')) {
            throw new InvalidArgumentException(
                "Wildcards can only be used at the start of the host. Expression: $expression",
            );
        }

        if ($parsedUrl['path'] !== '' && str_contains($parsedUrl['path'], '*')) {
            throw new InvalidArgumentException("Wildcards can't be used in the path. Expression: $expression");
        }

        if ($parsedUrl['port'] !== '') {
            if ($parsedUrl['port'] === '*') {
                return;
            }

            if (! is_numeric($parsedUrl['port'])) {
                throw new InvalidArgumentException("Port must be a number. Expression: $expression");
            }

            if ($parsedUrl['port'] <= 0 || $parsedUrl['port'] > 0xFFFF) {
                throw new InvalidArgumentException("Port must be between 1 and 65535. Expression: $expression");
            }
        }
    }

    /**
     * Check whether an expression is a nonce
     *
     * @param string $expression The expression to check
     *
     * @return bool
     */
    protected function isNonce(string $expression): bool
    {
        return str_starts_with($expression, "'nonce-") && str_ends_with($expression, "'");
    }

    /**
     * Get the fully formatted CSP header string
     *
     * This can be used directly in the Content-Security-Policy header.
     *
     * @return string The CSP header string
     */
    public function getHeader(): string
    {
        $directiveStrings = [];
        foreach ($this->directives as $directive => $expressions) {
            $directiveStrings[] = implode(' ', array_merge([$directive], $expressions));
        }
        return implode('; ', $directiveStrings);
    }

    /**
     * Get the values of a directive
     *
     * @param string $directive The directive name. Note that this can be a fetch directive,
     *   in which case the values will be fetched from its designated parent directive.
     *
     * @return list<string>
     */
    public function getDirective(string $directive): array
    {
        if (isset($this->directives[$directive])) {
            return $this->directives[$directive];
        } elseif (isset(static::FETCH_DIRECTIVES[$directive])) {
            return $this->getDirective(static::FETCH_DIRECTIVES[$directive]);
        }

        return [];
    }

    /**
     * Get all directives
     *
     * @return array<string, list<string>>
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * Get the first nonce found in the directives
     *
     * This can be used to set the nonce in a script or style tag.
     *
     * @return ?string The first nonce found in the directives.
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Get the values of a directive without inheritance fallback
     *
     * @param string $directive The directive name
     *
     * @return ?list<string>
     */
    public function getRawDirective(string $directive): ?array
    {
        return $this->directives[$directive] ?? null;
    }

    /**
     * Check whether a directive is present
     *
     * This follows the fetch directive inheritance chain.
     *
     * @param string $directive The directive name
     *
     * @return bool
     */
    public function hasDirective(string $directive): bool
    {
        if ($this->hasRawDirective($directive)) {
            return true;
        } elseif (isset(static::FETCH_DIRECTIVES[$directive])) {
            return $this->hasDirective(static::FETCH_DIRECTIVES[$directive]);
        }

        return false;
    }

    /**
     * Check whether a directive is present
     *
     * @param string $directive The directive name
     *
     * @return bool
     */
    public function hasRawDirective(string $directive): bool
    {
        return isset($this->directives[$directive]);
    }

    /**
     * Check whether the CSP does not contain any directives
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->directives === [];
    }

    /**
     * Merge multiple CSPs into the current one
     *
     * @param Csp ...$csps The CSPs to merge
     *
     * @return $this
     */
    public function merge(Csp ...$csps): static
    {
        foreach ($csps as $csp) {
            foreach ($csp->directives as $directive => $values) {
                $this->add($directive, $values === [] ? null : $values);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getHeader();
    }
}
