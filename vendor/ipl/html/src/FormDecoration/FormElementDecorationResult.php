<?php

namespace ipl\Html\FormDecoration;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\MutableHtml;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;

/**
 * Stores and renders the results of decorators
 *
 * @phpstan-type content array<int, ValidHtml|array<int, mixed>>
 */
class FormElementDecorationResult implements DecorationResult
{
    /** @var content The HTML content */
    protected array $content = [];

    public function append(ValidHtml $html): static
    {
        $this->content[] = $html;

        return $this;
    }

    public function prepend(ValidHtml $html): static
    {
        array_unshift($this->content, $html);

        return $this;
    }

    public function wrap(MutableHtml $html): static
    {
        $this->content = [[$html, $this->content]];

        return $this;
    }

    /**
     * Assemble the results
     *
     * @return HtmlDocument
     */
    public function assemble(): HtmlDocument
    {
        $content = new HtmlDocument();

        if (empty($this->content)) {
            return $content;
        }

        $this->resolveContent($content, $this->content);

        return $content;
    }

    /**
     * Resolve content
     *
     * @param MutableHtml $parent The parent element
     * @param content $content The content to be added
     *
     * @return void
     */
    protected function resolveContent(MutableHtml $parent, array $content): void
    {
        foreach ($content as $item) {
            if (is_array($item)) {
                $item = $this->resolveWrappedContent($item[0], $item[1]);
            }

            $parent->addHtml($item);
        }
    }

    /**
     * Resolve wrapped content
     *
     * @param MutableHtml $parent The parent element
     * @param ValidHtml|content $item The content to be added
     *
     * @return ValidHtml The resolved parent element with content added
     */
    protected function resolveWrappedContent(MutableHtml $parent, ValidHtml|array $item): ValidHtml
    {
        if ($item instanceof ValidHtml) {
            $parent->addHtml($item);
        } else {
            $this->resolveContent($parent, $item);
        }

        return $parent;
    }
}
