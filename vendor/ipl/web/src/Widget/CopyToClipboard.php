<?php

namespace ipl\Web\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;

/**
 * Copy to clipboard button
 */
class CopyToClipboard extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'button';

    protected $defaultAttributes = ['type' => 'button'];

    /**
     * Create a copy to clipboard button
     *
     * Creates a copy to clipboard button, which when clicked copies the text from the html element identified as
     * clipboard source that the clipboard button attaches itself to.
     */
    private function __construct()
    {
        $this->addAttributes(
            [
                'class'                 => 'copy-to-clipboard',
                'data-icinga-clipboard' => true,
                'tabindex'              => -1,
                'data-copied-label'     => $this->translate('Copied'),
                'title'                 => $this->translate('Copy to clipboard'),
            ]
        );
    }

    /**
     * Attach the copy to clipboard button to the given Html source element
     *
     * @param BaseHtmlElement $source
     *
     * @return void
     */
    public static function attachTo(BaseHtmlElement $source): void
    {
        $clipboardWrapper = new HtmlElement(
            'div',
            Attributes::create(['class' => 'clipboard-wrapper'])
        );

        $clipboardWrapper->addHtml(new static());

        $source->addAttributes(['data-clipboard-source' => true]);
        $source->prependWrapper($clipboardWrapper);
    }

    public function assemble(): void
    {
        $this->setHtmlContent(new Icon('clone'));
    }
}
