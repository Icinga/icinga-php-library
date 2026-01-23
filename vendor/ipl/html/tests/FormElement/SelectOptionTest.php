<?php

namespace ipl\Tests\Html\FormElement;

use ipl\Html\FormElement\SelectElement;
use ipl\Html\FormElement\SelectOption;
use ipl\Tests\Html\TestCase;

class SelectOptionTest extends TestCase
{
    public function testRendering()
    {
        $option = new SelectOption('test', 'Original label');

        $this->assertHtml('<option value="test">Original label</option>', $option);
    }

    public function testRenderingAfterSetLabel()
    {
        $option = new SelectOption('test', 'Original label');
        $option->setLabel('New label');

        $this->assertHtml('<option value="test">New label</option>', $option);
    }

    public function testGetLabel()
    {
        $option = new SelectOption('test', 'Original label');
        $this->assertSame('Original label', $option->getLabel());
    }

    public function testGetLabelAfterSetLabel()
    {
        $option = new SelectOption('test', 'Original label');
        $this->assertSame('Original label', $option->getLabel());

        $option->setLabel('New label');
        $this->assertSame('New label', $option->getLabel());
    }
}
