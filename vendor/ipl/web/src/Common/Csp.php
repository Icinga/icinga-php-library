<?php

namespace ipl\Web\Common;

use InvalidArgumentException;

/**
 * Represents a Content Security Policy (CSP) header.
 * Methods are additive, and duplicate policies are ignored.
 */
class Csp
{
    /**
     * @var array<string, array<string>> The directives and their values
     */
    protected array $directives = [];

    /**
     * @var string|null The first nonce found in the directives.
     * Note: This assumes there will only ever be one nonce.
     */
    protected ?string $nonce = null;

    /**
     * Create a new CSP by merging multiple CSPs.
     *
     * @param Csp ...$csps The CSPs to merge
     *
     * @return static A new CSP containing all directives from the input CSPs
     */
    public static function merge(Csp ...$csps): static
    {
        $result = new static();
        foreach ($csps as $csp) {
            foreach ($csp->directives as $directive => $values) {
                if ($directive === 'default-src') {
                    continue;
                }
                $result->add($directive, $values);
            }
        }
        return $result;
    }

    /**
     * Create a new CSP from a string
     *
     * @param string $header The CSP header string
     *
     * @return static A new CSP containing all directives from the input header
     */
    public static function fromString(string $header): static
    {
        $header = trim($header);
        $header = str_replace("\r\n", ' ', $header);
        $header = str_replace("\n", ' ', $header);
        $result = new static();
        foreach (explode(';', $header) as $directive) {
            $directive = trim($directive);
            if (empty($directive) || $directive === 'default-src') {
                continue;
            }
            $parts = explode(' ', $directive, 2);
            if (count($parts) < 2) {
                continue;
            }
            $result->add($parts[0], $parts[1]);
        }

        return $result;
    }

    /**
     * Add a directive with a policy or a list of policies to the CSP
     *
     * @param string $directive The directive name
     * @param string|string[] $value The policy or list of policies to add
     *
     * @return $this
     */
    public function add(string $directive, string|array $value): static
    {
        if ($directive === "default-src") {
            throw new InvalidArgumentException("Changing default-src is forbidden.");
        }

        if (! preg_match('/^[a-z\-]+$/', $directive)) {
            throw new InvalidArgumentException(
                "Directive names contain only lowercase letters and '-'. Directive: $directive",
            );
        }

        if (is_string($value)) {
            $value = trim($value);

            if (str_contains($value, ' ')) {
                return $this->add($directive, explode(' ', $value));
            }

            if (! isset($this->directives[$directive])) {
                $this->directives[$directive] = [];
            }

            if (in_array($value, $this->directives[$directive])) {
                return $this;
            }

            $this->directives[$directive][] = $value;

            if (
                $this->nonce === null
                && str_starts_with($value, "'nonce-")
                && str_ends_with($value, "'")
            ) {
                $this->nonce = substr($value, 7, -1);
            }
        } else {
            foreach ($value as $v) {
                $this->add($directive, $v);
            }
        }

        return $this;
    }

    /**
     * @return string|null The first nonce found in the directives.
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * Get the values of a directive
     *
     * @param string $directive The directive name
     *
     * @return string[]
     */
    public function getDirective(string $directive): array
    {
        return $this->directives[$directive] ?? [];
    }

    /**
     * Get all directives
     *
     * @return array<string, array<string>>
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * Get the fully formated CSP header string.
     * This can be used directly in the Content-Security-Policy header.
     *
     * @return string The CSP header string
     */
    public function getHeader(): string
    {
        $directiveStrings = ["default-src 'self'"];
        foreach ($this->directives as $directive => $values) {
            $directiveStrings[] = sprintf('%s %s', $directive, implode(' ', $values));
        }
        return implode('; ', $directiveStrings);
    }

    public function __toString(): string
    {
        return $this->getHeader();
    }
}
