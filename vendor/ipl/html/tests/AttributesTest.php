<?php

namespace ipl\Tests\Html;

use ipl\Html\Attribute;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\ValidHtml;

class AttributesTest extends TestCase
{
    public function testGetWithNonexistentAttribute()
    {
        $attributes = new Attributes();

        $attributes
            ->get('name')
            ->setValue('value');

        $this->assertSame(
            'value',
            $attributes->get('name')->getValue()
        );
    }

    public function testForeach()
    {
        $attrs = ['foo' => 'bar', 'baz' => 'qux'];

        $attributes = new Attributes($attrs);

        reset($attrs);

        foreach ($attributes as $attribute) {
            $name = key($attrs);
            $value = current($attrs);

            $this->assertSame($name, $attribute->getName());
            $this->assertSame($value, $attribute->getValue());

            next($attrs);
        }
    }

    public function testNativeAttributesAndCallbacks()
    {
        $objectOne = new class extends BaseHtmlElement {
            protected $defaultAttributes = ['class' => 'foo'];

            protected $attr;

            public function getAttr()
            {
                return $this->attr;
            }

            public function setAttr($val)
            {
                $this->attr = $val;
            }
        };

        $objectOne->getAttributes()->registerAttributeCallback(
            'class',
            [$objectOne, 'getAttr'],
            [$objectOne, 'setAttr']
        );

        $objectOne->getAttributes()->set('class', 'bar');

        $this->assertEquals('bar', $objectOne->getAttr());
        $this->assertEquals(' class="foo bar"', $objectOne->getAttributes()->render());
        $this->assertEquals('foo', $objectOne->getAttributes()->getAttributes()['class']->getValue());
    }

    public function testAttributesMerge()
    {
        $emptyAttributes = new Attributes();
        $filledAttributes = Attributes::create([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);

        $emptyAttributes->merge($filledAttributes);

        $this->assertEquals(' foo="bar" bar="foo"', $emptyAttributes->render());

        $moreAttributes = Attributes::create(['foo' => 'rab']);

        $moreAttributes->merge($filledAttributes);

        $this->assertEquals(' foo="rab bar" bar="foo"', $moreAttributes->render());
    }

    public function testAttributesMergeWithCallbacks()
    {
        $attributes = Attributes::create(['foo' => 'bar']);
        $callbacks = (new Attributes())
            ->registerAttributeCallback('foo', function () {
                return 'rab';
            })
            ->registerAttributeCallback(
                'bar',
                function () use (&$value) {
                    return $value;
                },
                function ($v) use (&$value) {
                    $value = $v;
                }
            );

        $attributes->merge($callbacks);

        $attributes->set('bar', 'foo');

        $this->assertEquals(' foo="bar rab" bar="foo"', $attributes->render());
    }

    public function testClone(): void
    {
        $original = Attributes::create([
            'class' => 'original-class',
            'value' => 'original-value'
        ]);

        $clone = clone $original;
        $clone->get('class')->setValue('clone-class');
        $clone->get('name')->setValue('clone-name');
        $clone->remove('value');

        $cloneCone = clone $clone;
        $cloneCone->get('class')->addValue('clone-clone-class');
        $cloneCone->get('name')->setValue('clone-clone-name');
        $cloneCone->get('value')->setValue('clone-clone-value');

        $this->assertSame('original-class', $original->get('class')->getValue());
        $this->assertSame('original-value', $original->get('value')->getValue());
        $this->assertNull($original->get('name')->getValue());
        $this->assertSame(
            ' class="original-class" value="original-value"',
            $original->render()
        );

        $this->assertSame('clone-class', $clone->get('class')->getValue());
        $this->assertNull($clone->get('value')->getValue());
        $this->assertSame('clone-name', $clone->get('name')->getValue());
        $this->assertSame(
            ' class="clone-class" name="clone-name"',
            $clone->render()
        );

        $this->assertSame(['clone-class', 'clone-clone-class'], $cloneCone->get('class')->getValue());
        $this->assertSame('clone-clone-name', $cloneCone->get('name')->getValue());
        $this->assertSame('clone-clone-value', $cloneCone->get('value')->getValue());
        $this->assertSame(
            ' class="clone-class clone-clone-class" name="clone-clone-name" value="clone-clone-value"',
            $cloneCone->render()
        );
    }
}
