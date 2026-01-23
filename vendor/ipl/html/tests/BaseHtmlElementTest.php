<?php

namespace ipl\Tests\Html;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlString;
use RuntimeException;

class BaseHtmlElementTest extends TestCase
{
    public function testRenderDefaultAttributesAsProperty()
    {
        $defaultAttributesAsProperty = new class extends BaseHtmlElement {
            protected $tag = 'div';

            protected $defaultAttributes = ['class' => 'test'];
        };

        $this->assertXmlStringEqualsXmlString(
            '<div class="test"></div>',
            $defaultAttributesAsProperty->render()
        );
    }

    public function testRenderDefaultAttributesAsMethod()
    {
        $defaultAttributesAsMethod = new class extends BaseHtmlElement {
            protected $tag = 'div';

            public function getDefaultAttributes()
            {
                return ['class' => 'test'];
            }
        };

        $this->assertXmlStringEqualsXmlString(
            '<div class="test"></div>',
            $defaultAttributesAsMethod->render()
        );
    }

    public function testExceptionThrownForVoidElementsWithContent()
    {
        $voidElementWithContent = new class extends BaseHtmlElement {
            protected $tag = 'img';

            protected function assemble()
            {
                $this->add('content');
            }
        };

        $this->expectException(RuntimeException::class);
        $voidElementWithContent->render();
    }

    public function testGetTag()
    {
        $img = new class extends BaseHtmlElement {
            protected function tag()
            {
                return 'img';
            }
        };

        $this->assertSame('img', $img->getTag());
        $this->assertTrue($img->isVoid());
        $this->assertFalse($img->wantsClosingTag());
    }

    public function testSetTag()
    {
        $div = new class extends BaseHtmlElement {
            protected $tag = 'div';
        };

        $this->assertSame('div', $div->getTag());
        $this->assertFalse($div->isVoid());
        $this->assertTrue($div->wantsClosingTag());

        $div->setTag('img');

        $this->assertSame('img', $div->getTag());
        $this->assertTrue($div->isVoid());
        $this->assertFalse($div->wantsClosingTag());
    }

    public function testAssertTagInRender()
    {
        $noTag = new class extends BaseHtmlElement {}; // phpcs:ignore

        $this->expectException(RuntimeException::class);
        $noTag->render();
    }

    public function testAssertTagInIsVoid()
    {
        $noTag = new class extends BaseHtmlElement {}; // phpcs:ignore

        $this->expectException(RuntimeException::class);
        $noTag->isVoid();
    }

    public function testAssertTagInGetTag()
    {
        $noTag = new class extends BaseHtmlElement {}; // phpcs:ignore

        $this->expectException(RuntimeException::class);
        $noTag->getTag();
    }

    public function testSetVoid()
    {
        $element = new class extends BaseHtmlElement {
            protected $tag = 'img';
        };

        $this->assertFalse($element->wantsClosingTag());
        $this->assertEquals('<img />', $element->render());
        $element->setVoid();
        $this->assertFalse($element->wantsClosingTag());
        $this->assertEquals('<img />', $element->render());
        $element->setVoid(false);
        $this->assertTrue($element->wantsClosingTag());
        $this->assertEquals('<img></img>', $element->render());
        $element->setVoid(true);
        $this->assertFalse($element->wantsClosingTag());
        $this->assertEquals('<img />', $element->render());
        $element->setVoid(null);
        $this->assertFalse($element->wantsClosingTag());
        $this->assertEquals('<img />', $element->render());
    }

    public function testAttributeValueDependingOnContent()
    {
        $attributeValueDependingOnContent = new class extends BaseHtmlElement {
            protected $tag = 'div';

            protected function assemble()
            {
                $specialHtmlString = new class ('<hr>') extends HtmlString {
                    public $state;

                    public function render()
                    {
                        $html = parent::render();

                        $this->state = 42;

                        return $html;
                    }
                };

                $this->addHtml($specialHtmlString);

                $this->getAttributes()->registerAttributeCallback('state', function () use ($specialHtmlString) {
                    return $specialHtmlString->state;
                });
            }
        };

        $this->assertHtml(
            '<div state="42"><hr></div>',
            $attributeValueDependingOnContent
        );
    }
}
