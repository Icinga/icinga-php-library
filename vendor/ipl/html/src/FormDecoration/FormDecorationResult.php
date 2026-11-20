<?php

namespace ipl\Html\FormDecoration;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\MutableHtml;
use ipl\Html\Contract\Wrappable;
use ipl\Html\Form;
use ipl\Html\HtmlDocument;
use ipl\Html\ValidHtml;

class FormDecorationResult implements DecorationResult
{
    /** @var MutableHtml & Wrappable The current content */
    private MutableHtml & Wrappable $content;

    /**
     * Create a new FormDecorationResult
     *
     * @param Form $form
     */
    public function __construct(Form $form)
    {
        $this->content = $form;
    }

    public function append(ValidHtml $html): static
    {
        $this->content->addHtml($html);

        return $this;
    }

    public function prepend(ValidHtml $html): static
    {
        $this->content->prependHtml($html);

        return $this;
    }

    public function wrap(MutableHtml $html): static
    {
        if (! $html instanceof Wrappable) {
            // If it's not a wrappable, mimic what wrapping usually means
            $html = (new HtmlDocument())->addHtml($html);
        }

        $this->content->addWrapper($html);
        $html->addHtml($this->content);
        $this->content = $html;

        return $this;
    }
}
