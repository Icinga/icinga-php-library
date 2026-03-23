<?php

namespace ipl\Web\Compat;

use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;

class Multipart extends HtmlDocument
{
    /** @var string */
    protected $for;

    protected $contentSeparator = "\n";

    /**
     * Set the container's id which this part is for
     *
     * @param string $id
     *
     * @return $this
     */
    public function setFor($id)
    {
        $this->for = $id;

        return $this;
    }

    protected function assemble()
    {
        $this->prepend(HtmlString::create(sprintf('for=%s', $this->for)));
    }
}
