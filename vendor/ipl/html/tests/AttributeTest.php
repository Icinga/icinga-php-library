<?php

namespace ipl\Tests\Html;

use ipl\Html\Attribute;

class AttributeTest extends TestCase
{
    public function testSimpleAttributeCanBeRendered()
    {
        $this->assertEquals(
            'class="simple"',
            $this->simpleAttribute()->render()
        );
    }

    public function testConstructorAcceptsArray()
    {
        $this->assertEquals(
            'class="two classes"',
            (new Attribute('class', ['two', 'classes']))->render()
        );
    }

    public function testCustomSeparatorIsUsed()
    {
        $this->assertSame(
            'accept="image/png, image/jpg"',
            (new Attribute('accept', ['image/png', 'image/jpg']))
                ->setSeparator(', ')
                ->render()
        );
    }

    public function testAttributeNameCanBeRetrieved()
    {
        $this->assertEquals(
            'class',
            $this->simpleAttribute()->getName()
        );
    }

    public function testAttributeValueCanBeRetrieved()
    {
        $this->assertEquals(
            'simple',
            $this->simpleAttribute()->getValue()
        );
    }

    public function testAttributeValueCanBeSet()
    {
        $this->assertEquals(
            'class="changed"',
            $this->simpleAttribute()
                ->addValue('byebye')
                ->setValue('changed')
                ->render()
        );
    }

    public function testCreateFactoryGivesAttribute()
    {
        $attribute = Attribute::create('class', 'simple');
        $this->assertInstanceOf('ipl\\Html\\Attribute', $attribute);
        $this->assertEquals(
            'class="simple"',
            $attribute->render()
        );
    }

    public function testAdditionalValuesCanBeAdded()
    {
        $attribute = $this
            ->simpleAttribute()
            ->addValue('one')
            ->addValue('more');

        $this->assertEquals(
            'class="simple one more"',
            $attribute->render()
        );

        $this->assertEquals(
            ['simple', 'one', 'more'],
            $attribute->getValue()
        );
    }

    public function testUmlautCharactersArePreserved()
    {
        $this->assertEquals(
            'süß',
            Attribute::create('x', 'süß')->renderValue()
        );
    }

    public function testEmojisAreAllowed()
    {
        $this->assertEquals(
            'heart="♥"',
            Attribute::create('heart', '♥')->render()
        );
    }

    public function testComplexAttributeIsCorrectlyEscaped()
    {
        $this->assertEquals(
            'data-some-thing="&quot;sweet&quot; & - $ ist <süß>"',
            Attribute::create('data-some-thing', '"sweet" & - $ ist <süß>')->render()
        );
    }

    public function testEscapeValueEscapesDoubleQuotes()
    {
        $this->assertSame(
            '&quot;value&quot;',
            Attribute::escapeValue('"value"')
        );
    }

    public function testEscapeValueEscapesAmbiguousAmpersands()
    {
        $this->assertSame(
            'value&amp;1234;',
            Attribute::escapeValue('value&1234;')
        );
    }

    public function testEscapeValueDoesNotDoubleQuote()
    {
        $this->assertSame(
            '&quot;value&quot;',
            Attribute::escapeValue('&quot;value&quot;')
        );
    }

    public function testEscapeValueTreatsSpecialCharacters()
    {
        $this->assertEquals(
            '“‘>&quot;&>’”',
            Attribute::create('x', '“‘>"&>’”')->renderValue()
        );
    }

    public function testRenderFalse()
    {
        $this->assertSame('', (new Attribute('name', false))->render());
    }

    public function testRenderNull()
    {
        $this->assertSame('', (new Attribute('name', null))->render());
    }

    public function testRenderEmptyArray()
    {
        $this->assertSame('', (new Attribute('name', []))->render());
    }

    public function testRemoveValue()
    {
        $this->assertSame(
            null,
            (new Attribute('name', 'value'))->removeValue('value')->getValue()
        );
    }

    public function testRemoveValueNoop()
    {
        $this->assertSame(
            'value',
            (new Attribute('name', 'value'))->removeValue('noop')->getValue()
        );
    }

    public function testRemoveValueWithArrayAndArrayValue()
    {
        $this->assertSame(
            ['foo'],
            (new Attribute('class', ['foo', 'bar']))->removeValue(['bar'])->getValue()
        );
    }

    public function testRemoveValueNoopWithArrayAndArrayValue()
    {
        $this->assertSame(
            ['foo', 'bar'],
            (new Attribute('class', ['foo', 'bar']))->removeValue(['baz'])->getValue()
        );
    }

    public function testRemoveValueWithArrayAndScalarValue()
    {
        $this->assertSame(
            null,
            (new Attribute('class', 'foo'))->removeValue(['foo'])->getValue()
        );
    }

    public function testRemoveValueNoopWithArrayAndScalarValue()
    {
        $this->assertSame(
            'foo',
            (new Attribute('class', 'foo'))->removeValue(['bar'])->getValue()
        );
    }

    public function testSpecialCharactersInAttributeNamesAreNotYetSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        Attribute::create('a_a', 'sa');
    }

    public function testSanitizeIdRemovesInvalidCharactersAndPrependsA()
    {
        $this->assertSame('aabc-_', Attribute::sanitizeId('a b$c-_.:Ä'));
    }

    public function testSanitizeIdForIdStartingWithNumber()
    {
        $this->assertSame('a123abc', Attribute::sanitizeId('123abc'));
    }

    public function testSanitizeIdWithEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ID must not be empty');

        Attribute::sanitizeId('');
    }

    public function testSanitizeIdRemovesUnicodeAndKeepsAscii()
    {
        $this->assertSame('ass', Attribute::sanitizeId('♥süßs'));
    }

    public function testSanitizeIdKeepsHyphenAndUnderscore()
    {
        $this->assertSame('afoo_bar-baz', Attribute::sanitizeId('foo_bar-baz'));
    }

    protected function simpleAttribute()
    {
        return new Attribute('class', 'simple');
    }

    protected function complexAttribute()
    {
        return ;
    }
}
