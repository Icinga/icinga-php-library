<?php

namespace ipl\Web\FormElement\TermInput;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\FormElement\TermInput;

class TermContainer extends BaseHtmlElement
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'terms'];

    /** @var TermInput */
    protected $input;

    /**
     * Create a new TermContainer
     *
     * @param TermInput $input
     */
    public function __construct(TermInput $input)
    {
        $this->input = $input;
    }

    protected function assemble()
    {
        foreach ($this->input->getTerms() as $i => $term) {
            $label = $term->getLabel() ?: $term->getSearchValue();

            $this->addHtml(new HtmlElement(
                'label',
                Attributes::create([
                    'class' => $term->getClass(),
                    'data-search' => $term->getSearchValue(),
                    'data-label' => $label,
                    'data-index' => $i
                ]),
                new HtmlElement(
                    'input',
                    Attributes::create([
                        'type' => 'text',
                        'value' => $label,
                        'pattern' => $term->getPattern(),
                        'data-invalid-msg' => $term->getMessage()
                    ])
                )
            ));
        }
    }
}
