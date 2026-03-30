<?php

namespace ipl\Html\Test;

use DOMDocument;
use ipl\Html\ValidHtml;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Assert that HTML is equal
     *
     * @param string    $expected
     * @param ValidHtml $actual
     */
    protected function assertHtml(string $expected, ValidHtml $actual): void
    {
        $expectedHtml = str_replace(
            "\n",
            '',
            preg_replace('/^\s+/m', '', trim($expected))
        );
        $actualHtml = $actual->render();

        $expectedDom = new DOMDocument();
        $this->assertTrue($expectedDom->loadHTML($expectedHtml), 'Expected HTML is not valid');
        $actualDom = new DOMDocument();
        $this->assertTrue($actualDom->loadHTML($actualHtml), 'Actual HTML is not valid');

        $this->assertEquals($expectedDom, $actualDom);
    }

    /**
     * @deprecated Use {@link assertHtml()} instead. assertRendersHtml() suffers from the fact that the HTML being
     * processed must have a root node, e.g. the HTML `<b>foo</b><b>bar</b>` would always fail with "Extra content at
     * the end of the document". {@link assertHtml()} just does the job.
     */
    protected function assertRendersHtml(string $html, ValidHtml $element): void
    {
        $this->assertXmlStringEqualsXmlString($html, $element->render());
    }
}
