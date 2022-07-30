<?php

namespace ipl\Html;

use Exception;

/**
 * Render {{#mustache}}Mustache{{/mustache}}-like string from {@link ValidHtml} element arguments
 *
 * # Example Usage
 * ```
 * $info = TemplateString::create(
 *      'Follow the {{#doc}}HTML documentation{{/doc}} for more information on {{#strong}}HTML elements{{/strong}}',
 *      [
 *          'doc'    => new Link(null, 'doc/html'),
 *          'strong' => Html::tag('strong')
 *      ]
 * );
 * ```
 */
class TemplateString extends FormattedString
{
    /** @var array */
    protected $templateArgs = [];

    /** @var int  */
    protected $pos = 0;

    /** @var string  */
    protected $string;

    /** @var int */
    protected $length;

    public function __construct($format, $args = null)
    {
        $parentArgs = [];
        foreach ($args ?: [] as $val) {
            if (is_array($val) && is_string(key($val))) {
                $this->templateArgs += $val;
            } else {
                $parentArgs[] = $val;
            }
        }

        parent::__construct($format, $parentArgs);
    }

    /**
     * Parse template strings
     *
     * @param null $for template name
     * @return HtmlDocument
     * @throws Exception in case of missing template argument or unbounded open or close templates
     */
    protected function parseTemplates($for = null)
    {
        $buffer = '';

        while (($char = $this->readChar()) !== false) {
            if ($char !== '{') {
                $buffer .= $char;
                continue;
            }

            $nextChar = $this->readChar();
            if ($nextChar !== '{') {
                $buffer .= $char . $nextChar;
                continue;
            }

            $templateHandle = $this->readChar();
            $start = $templateHandle === '#';
            $end = $templateHandle === '/';

            $templateKey = $this->readUntil('}');
            // if the string following '{{#' is read up to the last character or (length - 1)th character
            // then it is not a template
            if ($this->pos >= $this->length - 1) {
                $buffer .= $char . $nextChar . $templateHandle . $templateKey;
                continue;
            }

            $this->pos++;
            $closeChar = $this->readChar();

            if ($closeChar !== '}') {
                $buffer .= $char . $nextChar . $templateHandle . $templateKey . '}' . $closeChar;
                continue;
            }

            if ($start) {
                if (isset($this->templateArgs[$templateKey])) {
                    $wrapper = $this->templateArgs[$templateKey];

                    $buffer .= $this->parseTemplates($templateKey)->prependWrapper($wrapper);
                } else {
                    throw new Exception(sprintf(
                        'Missing template argument: %s ',
                        $templateKey
                    ));
                }
            } elseif ($for === $templateKey && $end) {
                // close the template
                $for = null;
                break;
            } else {
                // throw exception for unbounded closing of templates
                throw new Exception(sprintf(
                    'Unbound closing of template: %s',
                    $templateKey
                ));
            }
        }

        if ($this->pos === $this->length && $for !== null) {
            throw new Exception(sprintf(
                'Unbound opening of template: %s',
                $for
            ));
        }

        return (new HtmlDocument())->addHtml(HtmlString::create($buffer));
    }

    /**
     * Read until any of the given chars appears
     *
     * @param string ...$chars
     *
     * @return string
     */
    protected function readUntil(...$chars)
    {
        $buffer = '';
        while (($c = $this->readChar()) !== false) {
            if (in_array($c, $chars, true)) {
                $this->pos--;
                break;
            }

            $buffer .= $c;
        }

        return $buffer;
    }

    /**
     * Read a single character
     *
     * @return false|string false if there is no character left
     */
    protected function readChar()
    {
        if ($this->length > $this->pos) {
            return $this->string[$this->pos++];
        }

        return false;
    }

    public function render()
    {
        $formattedstring = parent::render();
        if (empty($this->templateArgs)) {
            return $formattedstring;
        }

        $this->string = $formattedstring;

        $this->length = strlen($formattedstring);

        return $this->parseTemplates()->render();
    }
}
