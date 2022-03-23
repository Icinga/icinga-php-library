<?php

namespace ipl\Html;

use Exception;

/**
 * A text node
 *
 * Primitive element that renders text to HTML while automatically escaping its content.
 * If the passed content is already escaped, see {@link setEscaped()} to indicate this.
 */
class Text implements ValidHtml
{
    /** @var string */
    protected $content;

    /** @var bool Whether the content is already escaped */
    protected $escaped = false;

    /**
     * Create a new text node
     *
     * @param string $content
     */
    public function __construct($content)
    {
        $this->setContent($content);
    }

    /**
     * Create a new text node
     *
     * @param string $content
     *
     * @return static
     */
    public static function create($content)
    {
        return new static($content);
    }

    /**
     * Get the content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the content
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = (string) $content;

        return $this;
    }

    /**
     * Get whether the content promises to be already escaped
     *
     * @return bool
     */
    public function isEscaped()
    {
        return $this->escaped;
    }

    /**
     * Set whether the content is already escaped
     *
     * @param bool $escaped
     *
     * @return $this
     */
    public function setEscaped($escaped = true)
    {
        $this->escaped = $escaped;

        return $this;
    }

    /**
     * Render text to HTML when treated like a string
     *
     * Calls {@link render()} internally in order to render the text to HTML.
     * Exceptions will be automatically caught and returned as HTML string as well using {@link Error::render()}.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return Error::render($e);
        }
    }

    public function render()
    {
        if ($this->escaped) {
            return $this->content;
        } else {
            return Html::escape($this->content);
        }
    }
}
