<?php

namespace ipl\Tests\Html;

use DOMDocument;
use ipl\Html\ValidHtml;

// phpcs:disable
if (class_exists('PHPUnit_Util_XML')) {
    // Support older PHPUnit versions
    class_alias('PHPUnit_Util_XML', 'PHPUnit\\Util\\Xml');
}

// phpcs:enable

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Assert that HTML is equal
     *
     * @param string    $expectedHtml
     * @param ValidHtml $actual
     */
    protected function assertHtml($expectedHtml, ValidHtml $actualHtml)
    {
        $expectedHtml = str_replace(
            "\n",
            '',
            preg_replace('/^\s+/m', '', trim($expectedHtml))
        );

        $expected = new DOMDocument();
        $this->assertTrue($expected->loadHTML($expectedHtml), 'Expected HTML is not valid');
        $actual = new DOMDocument();
        $this->assertTrue($actual->loadHTML($actualHtml->render()), 'Actual HTML is not valid');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @deprecated Use {@link assertHtml()} instead. assertRendersHtml() suffers from the fact that the HTML being
     * processed must have a root node, e.g. the HTML `<b>foo</b><b>bar</b>` would always fail with "Extra content at
     * the end of the document". {@link assertHtml()} just does the job.
     */
    protected function assertRendersHtml($html, ValidHtml $element)
    {
        $this->assertXmlStringEqualsXmlString($html, $element->render());
    }
}
