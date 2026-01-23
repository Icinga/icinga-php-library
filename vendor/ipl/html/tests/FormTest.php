<?php

namespace ipl\Tests\Html;

use ipl\Html\Form;
use ipl\Html\FormElement\BaseFormElement;
use Psr\Http\Message\ServerRequestInterface;

class FormTest extends TestCase
{
    public function testIsEmptyValue()
    {
        $this->assertTrue(Form::isEmptyValue(''), "`''` is not empty");
        $this->assertTrue(Form::isEmptyValue(null), '`null` is not empty');
        $this->assertTrue(Form::isEmptyValue([]), '`[]` is not empty');
        $this->assertTrue(Form::isEmptyValue('  '), '`  ` is not empty');

        $this->assertFalse(Form::isEmptyValue(0), '`0` is empty');
        $this->assertFalse(Form::isEmptyValue('0'), "`'0'` is empty");
        $this->assertFalse(Form::isEmptyValue(false), '`false` is empty');

        $this->assertFalse(Form::isEmptyValue('foo'), "`'foo' is empty");
        $this->assertFalse(Form::isEmptyValue(1), '`1` is empty');
        $this->assertFalse(Form::isEmptyValue(['']), "`['']` is empty");
        $this->assertFalse(Form::isEmptyValue([0]), '`[0]` is empty');
    }

    public function testOnRequestIsTriggeredIfNotSent(): void
    {
        $form = (new class extends Form {
            protected function assemble()
            {
                $this->add('bogus');
            }
        })->on(Form::ON_REQUEST, function ($req, Form $form) {
            $this->assertFalse($form->hasBeenSent(), 'ON_REQUEST is triggered for sent forms');
            $this->assertEmpty($form->getContent(), 'Form has been assembled before ON_REQUEST');
        });

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->exactly(2))->method('getMethod')->willReturn('GET');

        $form->handleRequest($request);

        $request2 = $this->createMock(ServerRequestInterface::class);
        $request2->expects($this->any())->method('getMethod')->willReturn('POST');
        $request2->expects($this->once())->method('getParsedBody')->willReturn([]);
        $request2->expects($this->once())->method('getUploadedFiles')->willReturn([]);

        $form->handleRequest($request2);
    }

    public function testEscapeReservedChars(): void
    {
        // Reserved chars '.', ' ', '[' and ']' are escaped
        $this->assertSame(Form::escapeReservedChars('foo.bar'), 'foo' . chr(28) . 'bar');
        $this->assertSame(Form::escapeReservedChars('foo bar'), 'foo' . chr(29) . 'bar');
        $this->assertSame(Form::escapeReservedChars('foo[bar]'), 'foo' . chr(30) . 'bar' . chr(31));

        // The string remains the same
        $this->assertSame(Form::escapeReservedChars('foo[bar]', false), 'foo[bar]');
        $this->assertSame(Form::escapeReservedChars('foo-bar123'), 'foo-bar123');
    }

    public function testFormElementsWithReservedCharsInName(): void
    {
        $form = new Form();

        // Test that the element name gets escaped as soon as the name is set
        /** @var BaseFormElement $el1 */
        $el1 = $form->createElement('text', 'foo.bar');
        $this->assertSame('foo' . chr(28) . 'bar', $el1->getEscapedName());

        /** @var BaseFormElement $el2 */
        $el2 = $form->createElement('text', 'foo[bar]');
        $this->assertSame('foo' . chr(30) . 'bar' . chr(31), $el2->getEscapedName());

        $el2->setName('foo bar');
        $this->assertSame('foo' . chr(29) . 'bar', $el2->getEscapedName());

        $form->registerElement($el1);
        $form->registerElement($el2);

        // Test that the element name is escaped when rendered
        $this->assertHtml('<input name="foo' . chr(28) . 'bar" type="text" />', $form->getElement('foo.bar'));
        $this->assertHtml('<input name="foo' . chr(29) . 'bar" type="text" />', $form->getElement('foo bar'));

        // Test for data population; Initial data is not escaped
        $form->populate(['foo.bar' => 'foo', 'foo bar' => 'bar']);
        $this->assertSame('foo', $form->getPopulatedValue('foo.bar'));
        $this->assertSame('bar', $form->getPopulatedValue('foo bar'));

        // Test for data population; The POST data contains the escaped chars
        $form->populate(['foo' . chr(28) . 'bar' => 'foo1', 'foo' . chr(29) . 'bar' => 'bar1']);
        $this->assertSame('foo1', $form->getPopulatedValue('foo.bar'));
        $this->assertSame('bar1', $form->getPopulatedValue('foo bar'));
    }
}
