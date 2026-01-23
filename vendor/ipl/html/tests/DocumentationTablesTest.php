<?php

namespace ipl\Tests\Html;

use ipl\Html\Table;

class DocumentationTablesTest extends TestCase
{
    protected $sampleRow = '<tr><td>app1.example.com</td><td>127.0.0.1</td><td>production</td></tr>';

    protected $sampleRowData = [
        'app1.example.com',
        '127.0.0.1',
        'production'
    ];

    public function testJustATable()
    {
        $table = new Table();
        $this->assertRendersHtml('<table></table>', $table);
    }

    public function testSimpleTableRow()
    {
        $this->assertRendersHtml(
            $this->sampleRow,
            Table::row($this->sampleRowData)->setSeparator("\n")
        );
    }

    public function testAddingJustAString()
    {
        $this->assertRendersHtml(
            '<table><tbody><tr><td>Some &lt;special&gt; string!</td></tr></tbody></table>',
            (new Table())->add('Some <special> string!')
        );
    }

    public function testAddingAnArray()
    {
        $this->assertRendersHtml(
            '<table><tbody>' . $this->sampleRow . '</tbody></table>',
            (new Table())->add($this->sampleRowData)
        );
    }

    public function testTableWithSimpleTableRow()
    {
        $this->assertRendersHtml(
            '<table><tbody>' . $this->sampleRow . '</tbody></table>',
            (new Table())->add(Table::row($this->sampleRowData))
        );
    }

    public function testAddingMultipleArrayRows()
    {
        $table = (new Table())
            ->add(['app1.example.com', '127.0.0.1', 'production'])
            ->add(['app2.example.com', '127.0.0.2', 'production'])
            ->add(['app3.example.com', '127.0.0.3', 'testing']);

        $html = '<table><tbody>'
            . '<tr><td>app1.example.com</td><td>127.0.0.1</td><td>production</td></tr>'
            . '<tr><td>app2.example.com</td><td>127.0.0.2</td><td>production</td></tr>'
            . '<tr><td>app3.example.com</td><td>127.0.0.3</td><td>testing</td></tr>'
            . '</tbody></table>';

        $this->assertRendersHtml($html, $table);
    }
}
