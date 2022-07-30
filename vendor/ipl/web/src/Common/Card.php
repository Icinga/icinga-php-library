<?php

namespace ipl\Web\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

abstract class Card extends BaseHtmlElement
{
    protected $tag = 'section';

    abstract protected function assembleBody(BaseHtmlElement $body);

    abstract protected function assembleFooter(BaseHtmlElement $footer);

    abstract protected function assembleHeader(BaseHtmlElement $header);

    protected function createBody()
    {
        $body = Html::tag('div', ['class' => 'card-body']);

        $this->assembleBody($body);

        return $body;
    }

    protected function createFooter()
    {
        $footer = Html::tag('div', ['class' => 'card-footer']);

        $this->assembleFooter($footer);

        return $footer;
    }

    protected function createHeader()
    {
        $header = Html::tag('div', ['class' => 'card-header']);

        $this->assembleHeader($header);

        return $header;
    }

    protected function assemble()
    {
        $this->addAttributes(['class' => 'card']);

        $this->add([
            $this->createHeader(),
            $this->createBody(),
            $this->createFooter()
        ]);
    }
}
