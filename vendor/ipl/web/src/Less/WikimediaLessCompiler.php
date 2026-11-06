<?php

namespace ipl\Web\Less;

use Less_Exception_Parser;
use Less_Parser;
use RuntimeException;

/**
 * Less compiler backed by the wikimedia/less.php library
 */
class WikimediaLessCompiler
{
    /**
     * Create a new WikimediaLessCompiler
     *
     * @param array<string, mixed> $parserOptions Options forwarded verbatim to {@see Less_Parser}
     */
    public function __construct(protected array $parserOptions = [])
    {
    }

    /**
     * Compile Less source to CSS
     *
     * @param string $less Less source code to compile
     * @param bool|null $minify Controls whether the CSS output is minified:
     *   - `null` (default): use the `compress` option from the constructor
     *   - `true`/`false`: override the constructor option for this call
     *
     * @return string The compiled CSS
     *
     * @throws RuntimeException If Less compilation fails
     */
    public function compile(string $less, ?bool $minify = null): string
    {
        $parserOptions = [];
        if ($minify !== null) {
            $parserOptions['compress'] = $minify;
        }

        try {
            return (new Less_Parser($parserOptions + $this->parserOptions))
                ->parse($less)
                ->getCss();
        } catch (Less_Exception_Parser $e) {
            throw new RuntimeException('Less compilation failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
