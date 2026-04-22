<?php

namespace ipl\Html\Contract;

use ipl\Html\ValidHtml;

interface MutableHtml extends ValidHtml
{
    /**
     * Add content
     *
     * @param ValidHtml ...$content
     *
     * @return $this
     */
    public function addHtml(ValidHtml ...$content);

    /**
     * Prepend content
     *
     * @param ValidHtml ...$content
     *
     * @return $this
     */
    public function prependHtml(ValidHtml ...$content);

    /**
     * Set content
     *
     * @param ValidHtml ...$content
     *
     * @return $this
     */
    public function setHtmlContent(ValidHtml ...$content);

    /**
     * Insert Html after an existing Html node
     *
     * @param ValidHtml $newNode
     * @param ValidHtml $existingNode
     *
     * @return $this
     */
    public function insertAfter(ValidHtml $newNode, ValidHtml $existingNode): self;

    /**
     * Insert Html before an existing Html node
     *
     * @param ValidHtml $newNode
     * @param ValidHtml $existingNode
     *
     * @return $this
     */
    public function insertBefore(ValidHtml $newNode, ValidHtml $existingNode): self;

    /**
     * Remove content
     *
     * @param ValidHtml $content
     *
     * @return $this
     */
    public function remove(ValidHtml $content);

    /**
     * Get the content
     *
     * @return ValidHtml[]
     */
    public function getContent();

    /**
     * Check whether the given content is a direct or indirect child of this Html
     *
     * A direct child is one that is part of this Html element's content. An indirect child
     * is one that is part of a direct child's content (recursively).
     *
     * @param ValidHtml $content
     *
     * @return bool
     */
    public function contains(ValidHtml $content);

    /**
     * Get whether there is any content
     *
     * @return bool
     */
    public function isEmpty();
}
