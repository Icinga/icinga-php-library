<?php

namespace ipl\Html;

use Countable;
use Exception;
use InvalidArgumentException;
use ipl\Html\Contract\Wrappable;
use RuntimeException;

/**
 * HTML document
 *
 * An HTML document is composed of a tree of HTML nodes, i.e. text nodes and HTML elements.
 */
class HtmlDocument implements Countable, Wrappable
{
    /** @var string Content separator */
    protected $contentSeparator = '';

    /** @var bool Whether the document has been assembled */
    protected $hasBeenAssembled = false;

    /** @var Wrappable Wrapper */
    protected $wrapper;

    /** @var Wrappable Wrapped element */
    private $wrapped;

    /** @var HtmlDocument The currently responsible wrapper */
    private $renderedBy;

    /** @var ValidHtml[] Content */
    private $content = [];

    /** @var array */
    private $contentIndex = [];

    /**
     * Set the element to wrap
     *
     * @param Wrappable $element
     *
     * @return $this
     */
    private function setWrapped(Wrappable $element)
    {
        $this->wrapped = $element;

        return $this;
    }

    /**
     * Consume the wrapped element
     *
     * @return Wrappable
     */
    private function consumeWrapped()
    {
        $wrapped = $this->wrapped;
        $this->wrapped = null;

        return $wrapped;
    }

    /**
     * Get the content
     *
     * return ValidHtml[]
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the content
     *
     * @param mixed $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = [];
        $this->setHtmlContent(...Html::wantHtmlList($content));

        return $this;
    }

    /**
     * Set content
     *
     * @param ValidHtml ...$content
     *
     * @return $this
     */
    public function setHtmlContent(ValidHtml ...$content)
    {
        $this->content = [];
        foreach ($content as $element) {
            $this->addIndexedContent($element);
        }

        return $this;
    }

    /**
     * Get the content separator
     *
     * @return string
     */
    public function getSeparator()
    {
        return $this->contentSeparator;
    }

    /**
     * Set the content separator
     *
     * @param string $separator
     *
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->contentSeparator = $separator;

        return $this;
    }

    /**
     * Get the first {@link BaseHtmlElement} with the given tag
     *
     * @param string $tag
     *
     * @return BaseHtmlElement
     *
     * @throws InvalidArgumentException If no {@link BaseHtmlElement} with the given tag exists
     */
    public function getFirst($tag)
    {
        foreach ($this->content as $c) {
            if ($c instanceof BaseHtmlElement && $c->getTag() === $tag) {
                return $c;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Trying to get first %s, but there is no such',
            $tag
        ));
    }

    /**
     * Add content
     *
     * @param mixed $content
     *
     * @return $this
     */
    public function add($content)
    {
        $this->addHtml(...Html::wantHtmlList($content));

        return $this;
    }

    /**
     * Add content
     *
     * @param ValidHtml ...$content
     *
     * @return $this
     */
    public function addHtml(ValidHtml ...$content)
    {
        foreach ($content as $element) {
            $this->addIndexedContent($element);
        }

        return $this;
    }

    /**
     * Add content from the given document
     *
     * @param HtmlDocument $from
     * @param callable     $callback Optional callback in order to transform the content to add
     *
     * @return $this
     */
    public function addFrom(HtmlDocument $from, $callback = null)
    {
        $from->ensureAssembled();

        $isCallable = is_callable($callback);
        foreach ($from->getContent() as $item) {
            $this->add($isCallable ? $callback($item) : $item);
        }

        return $this;
    }

    /**
     * Check whether the given element is a direct or indirect child of this document
     *
     * A direct child is one that is part of this document's content. An indirect child
     * is one that is either part of a direct child's content (recursively) or a wrapper
     * of such (recursively).
     *
     * @param ValidHtml $element
     *
     * @return bool
     */
    public function contains(ValidHtml $element)
    {
        $key = spl_object_hash($element);
        if (array_key_exists($key, $this->contentIndex)) {
            return true;
        }

        foreach ($this->content as $child) {
            if ($child instanceof self && ($child->contains($element) || $child->wrappedBy($element))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepend content
     *
     * @param mixed $content
     *
     * @return $this
     */
    public function prepend($content)
    {
        $this->prependHtml(...Html::wantHtmlList($content));

        return $this;
    }

    /**
     * Prepend content
     *
     * @param ValidHtml ...$content
     *
     * @return $this
     */
    public function prependHtml(ValidHtml ...$content)
    {
        foreach (array_reverse($content) as $html) {
            array_unshift($this->content, $html);
            $this->incrementIndexKeys();
            $this->addObjectPosition($html, 0);
        }

        return $this;
    }

    /**
     * Remove content
     *
     * @param ValidHtml $html
     *
     * @return $this
     */
    public function remove(ValidHtml $html)
    {
        $key = spl_object_hash($html);
        if (array_key_exists($key, $this->contentIndex)) {
            foreach ($this->contentIndex[$key] as $pos) {
                unset($this->content[$pos]);
            }
        }
        $this->content = array_values($this->content);

        $this->reIndexContent();

        return $this;
    }

    /**
     * Ensure that the document has been assembled
     *
     * @return $this
     */
    public function ensureAssembled()
    {
        if (! $this->hasBeenAssembled) {
            $this->hasBeenAssembled = true;
            $this->assemble();
        }

        return $this;
    }

    /**
     * Get whether the document is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        $this->ensureAssembled();

        return empty($this->content);
    }

    /**
     * Render the content to HTML but ignore any wrapper
     *
     * @return string
     */
    public function renderUnwrapped()
    {
        $this->ensureAssembled();
        $html = [];

        // This **must** be consumed after the document's assembly but before rendering the content.
        // If the document consumes it during assembly, nothing happens. If the document is used as
        // wrapper for another element, consuming it asap prevents a left-over reference and avoids
        // the element from getting rendered multiple times.
        $wrapped = $this->consumeWrapped();

        $content = $this->getContent();
        if ($wrapped !== null && ! $this->contains($wrapped)) {
            $content[] = $wrapped;
        }

        foreach ($content as $element) {
            if ($element instanceof self) {
                $element->renderedBy = $this;
            }

            $html[] = $element->render();

            if ($element instanceof self) {
                unset($element->renderedBy);
            }
        }

        return implode($this->contentSeparator, $html);
    }

    public function __clone()
    {
        foreach ($this->content as $key => $element) {
            $this->content[$key] = clone $element;
        }

        $this->reIndexContent();
    }

    /**
     * Render content to HTML when treated like a string
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

    /**
     * Assemble the document
     *
     * Override this method in order to provide content in concrete classes.
     */
    protected function assemble()
    {
    }

    /**
     * Render the document to HTML respecting the set wrapper
     *
     * @return string
     */
    protected function renderWrapped()
    {
        $wrapper = $this->wrapper;

        if (isset($this->renderedBy)) {
            if ($wrapper === $this->renderedBy || $wrapper->contains($this->renderedBy)) {
                // $this might be an intermediate wrapper that's already about to be rendered.
                // In case of an element (referencing $this as a wrapper) that is a child of an
                // outer wrapper, it is required to ignore $wrapper as otherwise it's a loop.
                // ($wrapper then is in the render path of the outer wrapper and sideways "stolen")
                return $this->renderUnwrapped();
            }

            $wrapper->renderedBy = $this->renderedBy;
        } elseif (isset($wrapper->renderedBy)) {
            throw new RuntimeException('Wrapper loop detected');
        } else {
            $this->renderedBy = $wrapper;
        }

        $html = $wrapper->renderWrappedDocument($this);

        if (isset($this->renderedBy)) {
            if ($this->renderedBy === $wrapper) {
                unset($this->renderedBy);
            } elseif ($wrapper->renderedBy === $this->renderedBy) {
                unset($wrapper->renderedBy);
            }
        }

        return $html;
    }

    /**
     * Render the given document to HTML by treating this document as the wrapper
     *
     * @param HtmlDocument $document
     *
     * @return string
     */
    protected function renderWrappedDocument(HtmlDocument $document)
    {
        return $this->setWrapped($document)->render();
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->content);
    }

    public function getWrapper()
    {
        return $this->wrapper;
    }

    public function setWrapper(Wrappable $wrapper)
    {
        $this->wrapper = $wrapper;

        return $this;
    }

    public function addWrapper(Wrappable $wrapper)
    {
        if ($this->wrapper === null) {
            $this->setWrapper($wrapper);
        } else {
            $this->wrapper->addWrapper($wrapper);
        }

        return $this;
    }

    public function prependWrapper(Wrappable $wrapper)
    {
        if ($this->wrapper === null) {
            $this->setWrapper($wrapper);
        } else {
            $wrapper->addWrapper($this->wrapper);
            $this->setWrapper($wrapper);
        }

        return $this;
    }

    /**
     * Check whether the given element wraps this document (recursively)
     *
     * @param ValidHtml $element
     *
     * @return bool
     */
    protected function wrappedBy(ValidHtml $element)
    {
        if ($this->wrapper === null) {
            return false;
        }

        if ($this->wrapper === $element || $this->wrapper->wrappedBy($element)) {
            return true;
        }

        return false;
    }

    public function render()
    {
        $this->ensureAssembled();
        if ($this->wrapper === null) {
            return $this->renderUnwrapped();
        } else {
            return $this->renderWrapped();
        }
    }

    private function addIndexedContent(ValidHtml $html)
    {
        $pos = count($this->content);
        $this->content[$pos] = $html;
        $this->addObjectPosition($html, $pos);
    }

    private function addObjectPosition(ValidHtml $html, $pos)
    {
        $key = spl_object_hash($html);
        if (array_key_exists($key, $this->contentIndex)) {
            $this->contentIndex[$key][] = $pos;
        } else {
            $this->contentIndex[$key] = [$pos];
        }
    }

    private function incrementIndexKeys()
    {
        foreach ($this->contentIndex as & $index) {
            foreach ($index as & $pos) {
                $pos++;
            }
        }
    }

    private function reIndexContent()
    {
        $this->contentIndex = [];
        foreach ($this->content as $pos => $html) {
            $this->addObjectPosition($html, $pos);
        }
    }
}
