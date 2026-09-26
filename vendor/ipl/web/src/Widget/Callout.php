<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Str;
use ipl\Web\Common\CalloutType;

/**
 * Information box with a type specific color and icon
 *
 * The type controls both the color scheme and the icon. An optional title
 * is displayed above the content.
 */
class Callout extends BaseHtmlElement
{
    /** @var string Class name for fit content callouts */
    protected const CLASS_FIT_CONTENT = 'callout-fit-content';

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'callout'];

    /**
     * Create a new callout
     *
     * The $type parameter determines the color and icon of the callout.
     *
     * @param CalloutType $type The type of the callout
     * @param ValidHtml|string $content The content of the callout
     * @param ?string $title An optional title, displayed above the content
     */
    public function __construct(
        protected CalloutType $type,
        protected ValidHtml|string $content,
        protected ?string $title = null
    ) {
        $this->addAttributes(Attributes::create(['class' => $type->value]));
    }

    protected function assemble(): void
    {
        $this->addHtml($this->type->getIcon());

        $this->addHtml(HtmlElement::create(
            'div',
            ['class' => 'callout-text'],
            [
                Str::isEmpty($this->title)
                    ? null
                    : HtmlElement::create('strong', ['class' => 'callout-title'], Text::create($this->title)),
                is_string($this->content) ? Text::create($this->content) : $this->content,
            ],
        ));
    }

    /**
     * Set the callout width to 100% of its parent container
     *
     * Callouts are by default sized to fill their parent container.
     *
     * @param bool $isFitContent Whether the callout size should be dependent on its content
     *
     * @return $this
     */
    public function setFitContent(bool $isFitContent = true): static
    {
        if ($isFitContent) {
            $this->addAttributes(Attributes::create(['class' => static::CLASS_FIT_CONTENT]));
        } else {
            $this->removeAttribute('class', static::CLASS_FIT_CONTENT);
        }

        return $this;
    }
}
