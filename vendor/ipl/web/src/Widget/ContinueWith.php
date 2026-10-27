<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Filter\Rule;
use ipl\Web\Url;

class ContinueWith extends BaseHtmlElement
{
    protected $tag = 'span';

    protected $defaultAttributes = ['class' => 'continue-with'];

    /** @var Url */
    protected $url;

    /** @var Filter\Rule|callable */
    protected $filter;

    /** @var string */
    protected $title;

    /** @var string The title to use when the filter is empty */
    protected string $emptyFilterTitle;

    /** @var ?string The reason why the widget is disabled */
    protected ?string $disableReason = null;

    /** @var ?string The `data-base-target` attribute */
    protected ?string $baseTarget = null;

    /**
     * @deprecated Use {@see ContinueWith::create()} and {@see ContinueWith::createDisabled()} instead
     */
    public function __construct(?Url $url = null, $filter = null)
    {
        $this->url = $url;
        $this->filter = $filter;

        $this->emptyFilterTitle = '';
    }

    /**
     * Create a `ContinueWith` widget
     *
     * @param Url $url The url to use
     * @param Rule|callable $filter The filter to apply
     * @param string $title The title of the widget
     * @param string $emptyFilterTitle The title to use when the filter is empty
     *
     * @return static
     */
    public static function create(
        Url $url,
        Filter\Rule|callable $filter,
        string $title,
        string $emptyFilterTitle
    ): static {
        return (new static($url, $filter))
            ->setTitle($title)
            ->setEmptyFilterTitle($emptyFilterTitle);
    }

    /**
     * Create a disabled `ContinueWith` widget
     *
     * @param string $reason The reason why the widget is disabled
     *
     * @return static
     */
    public static function createDisabled(string $reason): static
    {
        $instance = new static();
        $instance->disableReason = $reason;

        return $instance;
    }

    /**
     * Get the `data-base-target` attribute
     *
     * @return ?string
     */
    public function getBaseTarget(): ?string
    {
        return $this->baseTarget;
    }

    /**
     * Set the `data-base-target` attribute
     *
     * @param string $target
     *
     * @return $this
     */
    public function setBaseTarget(string $target): static
    {
        $this->baseTarget = $target;

        return $this;
    }

    /**
     * Set the title to use when the filter is empty
     *
     * @param string $emptyFilterTitle
     *
     * @return $this
     */
    public function setEmptyFilterTitle(string $emptyFilterTitle): static
    {
        $this->emptyFilterTitle = $emptyFilterTitle;

        return $this;
    }

    /**
     * Set title for the anchor
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function assemble()
    {
        $filter = $this->filter;
        if (is_callable($filter)) {
            $filter = $filter(); /** @var Filter\Rule $filter */
        }

        $baseFilter = $this->url?->getFilter();
        if ($baseFilter && ((! $baseFilter instanceof Filter\Chain) || ! $baseFilter->isEmpty())) {
            $filter = Filter::all($baseFilter, $filter);
        }

        if ($this->disableReason || ($filter instanceof Filter\Chain && $filter->isEmpty())) {
            $this->addHtml(new HtmlElement(
                'span',
                Attributes::create([
                    'class' => ['control-button', 'disabled'],
                    'title' => $this->disableReason ?? $this->emptyFilterTitle,
                ]),
                new Icon('share')
            ));
        } else {
            $link = new ActionLink(
                null,
                $this->url->setFilter($filter),
                'share',
                ['class' => 'control-button', 'title' => $this->title]
            );

            if ($this->getBaseTarget()) {
                $link->setBaseTarget($this->getBaseTarget());
            }

            $this->addHtml($link);
        }
    }
}
