<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Str;

/**
 * Icon element
 */
class Icon extends BaseHtmlElement
{
    protected $tag = 'i';

    /** @var string Icon style */
    protected $style;

    /** @var string Icon default style */
    protected $defaultStyle = 'fa';

    /**
     * Create an icon element
     *
     * Creates an icon element from the given name and HTML attributes. The icon element's tag will be <i>. The given
     * name will be used as automatically added CSS class for the icon element in the format 'icon-$name'. In addition,
     * the CSS class 'icon' will be automatically added too.
     *
     * @param string           $name       The name of the icon
     * @param Attributes|array $attributes The HTML attributes for the element
     */
    public function __construct(string $name, $attributes = null)
    {
        if (! Str::startsWith($name, 'fa-')) {
            $name = "fa-$name";
        }

        $this
            ->getAttributes()
                ->add('class', ['icon', $name])
                ->add($attributes);
    }

    /**
     * Get the icon style
     *
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style ?? $this->defaultStyle;
    }

    /**
     * Set the icon style
     *
     * @param string $style Style class with prefix
     *
     * @return $this
     */
    public function setStyle(string $style): self
    {
        $this->style = $style;

        return $this;
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getStyle()]);
    }
}
