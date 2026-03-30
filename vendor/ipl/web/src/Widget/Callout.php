<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Common\CalloutType;

/**
 * An information box that can be used to display information to the user.
 * It consists of a set of standardized colors and icons that can be used to visually distinguish the type of
 * information.
 * A content string and an optional title can be passed to the constructor.
 */
class Callout extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'callout'];

    /**
     * Create a new callout
     *
     * @param CalloutType $type the type of the callout. The type determines the color and icon that is used.
     * @param ValidHtml|string $content the content of the callout
     * @param string|null $title an optional title, displayed above the content
     */
    public function __construct(
        protected CalloutType $type,
        protected ValidHtml|string $content,
        protected ?string $title = null
    ) {
        $this->addAttributes(Attributes::create(['class' => $type->value]));
    }

    public function assemble(): void
    {
        $this->addHtml($this->type->getIcon());

        $this->addHtml(HtmlElement::create(
            'div',
            ['class' => 'callout-text'],
            [
                $this->title
                    ? HtmlElement::create('strong', ['class' => 'callout-title'], new Text($this->title))
                    : null,
                is_string($this->content) ? new Text($this->content) : $this->content,
            ],
        ));
    }

    /**
     * Callouts are only as wide as their content.
     * Setting it to fullwidth will force the callout to be as wide as its container.
     *
     * @param bool $fullwidth should the callout be fullwidth
     *
     * @return $this
     */
    public function setFullwidth(bool $fullwidth = true): static
    {
        if ($fullwidth) {
            $this->addAttributes(Attributes::create(['class' => 'callout-fullwidth']));
        } else {
            $this->removeAttribute('class', 'callout-fullwidth');
        }

        return $this;
    }

    /**
     * Setting this to true will allow the callout to be used for a single form element.
     * This is used to visually align the callout to the content of the form element.
     *
     * @param bool $isFormElement should the callout be used for a form element
     *
     * @return $this
     */
    public function setFormElement(bool $isFormElement = true): static
    {
        if ($isFormElement) {
            $this->addAttributes(Attributes::create(['class' => 'form-callout']));
        } else {
            $this->removeAttribute('class', 'from-callout');
        }

        return $this;
    }
}
