<?php

namespace ipl\Web\Widget;

use ipl\Html\Attribute;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Web\Common\BaseTarget;
use ipl\Web\Url;

/**
 * Link element, i.e. <a href="...
 */
class Link extends BaseHtmlElement
{
    use BaseTarget;

    /** @var Url */
    protected $url;

    protected $tag = 'a';

    /**
     * Create a link element
     *
     * @param mixed            $content
     * @param Url|string       $url
     * @param Attributes|array $attributes
     */
    public function __construct($content, $url, $attributes = null)
    {
        $this
            ->setContent($content)
            ->setUrl($url)
            ->getAttributes()
                ->add($attributes)
                ->registerAttributeCallback('href', [$this, 'createHrefAttribute']);
    }

    /**
     * Get the URL of the link
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the URL of the link
     *
     * @param Url|string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        if (! $url instanceof Url) {
            try {
                $url = Url::fromPath($url);
            } catch (\Exception $e) {
                $url = 'invalid';
            }
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Create and return the href attribute
     *
     * Used as attribute callback for the href attribute.
     *
     * @return Attribute
     */
    public function createHrefAttribute()
    {
        return new Attribute('href', (string) $this->getUrl());
    }
}
