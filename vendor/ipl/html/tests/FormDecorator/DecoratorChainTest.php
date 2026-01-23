<?php

namespace ipl\Tests\Html\FormDecorator;

use InvalidArgumentException;
use ipl\Html\FormDecoration\DecoratorChain;
use ipl\Html\Contract\FormElementDecoration;
use ipl\Html\HtmlDocument;
use ipl\Tests\Html\TestCase;
use ValueError;

class DecoratorChainTest extends TestCase
{
    protected DecoratorChain $chain;

    public function setUp(): void
    {
        $this->chain = (new DecoratorChain(FormElementDecoration::class))
            ->addDecoratorLoader(__NAMESPACE__, 'Decorator');
    }

    public function testMethodAddDecorator(): void
    {
        $this->chain->addDecorator('test', 'Test');
        $decorators = iterator_to_array($this->chain);

        $this->assertNotEmpty($decorators);
        $this->assertInstanceOf(TestDecorator::class, $decorators[0]);
    }

    /**
     * @depends testMethodAddDecorator
     */
    public function testMethodClearDecorators(): void
    {
         $this->chain
            ->addDecorator('test', 'Test')
            ->addDecorator('test2', 'TestWithOptions');

        $this->assertCount(2, iterator_to_array($this->chain));

        $this->chain->clearDecorators();

        $this->assertCount(0, iterator_to_array($this->chain));
    }

    /**
     * @depends testMethodClearDecorators
     */
    public function testMethodHasDecorators(): void
    {
        $this->assertFalse($this->chain->hasDecorators());

        $this->chain
            ->addDecorator('test1', 'Test')
            ->addDecorator('test2', 'TestWithOptions');

        $this->assertTrue($this->chain->hasDecorators());
        $this->assertFalse($this->chain->clearDecorators()->hasDecorators());
    }

    /**
     * @depends testMethodClearDecorators
     */
    public function testMethodAddDecoratorWithAllowedParamFormats(): void
    {
        $this->chain->addDecorator('test', 'TestWithOptions');
        $decorators = iterator_to_array($this->chain);
        $this->assertCount(1, $decorators);
        $decorator = $decorators[0];
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorator);
        $this->chain->clearDecorators();

        $this->chain->addDecorator('test', new TestWithOptionsDecorator());
        $decorators = iterator_to_array($this->chain);
        $this->assertCount(1, $decorators);
        $decorator = $decorators[0];
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorator);
        $this->chain->clearDecorators();

        $options = [
            'attrs' => ['setter' => 'setter'], // set via setAttrs(), returned values of getAttrs() contains it
            'not-setter' => 'not-setter' // added as attribute, returned values of getAttrs() does not contain it
        ];
        $this->chain->addDecorator('test', 'TestWithOptions', $options);
        $decorators = iterator_to_array($this->chain);
        $this->assertCount(1, $decorators);
        $decorator = $decorators[0];
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorator);
        $this->assertSame($options['attrs'], $decorator->getAttrs());
        $this->chain->clearDecorators();
    }

    public function testMethodAddDecoratorThrowsExceptionWhenUnknownDecoratorNameIsPassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Can't load decorator 'invalid'. decorator unknown");

        $this->chain->addDecorator('unknown', 'invalid');
    }

    public function testMethodAddDecoratorThrowsExceptionWhenDecoratorInstanceWithOptionsIsPassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No options are allowed with parameter #2 of type Decorator');
        $this->chain->addDecorator('test', new TestDecorator(), ['optionKey1' => 'optionValue1']);
    }

    public function testMethodAddDecoratorThrowsExceptionWhenInvalidClassPathIsPassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid decorator class 'ipl\Html\HtmlDocument'."
            . " decorator must be an instance of ipl\Html\Contract\FormElementDecoration"
        );

        $this->chain->addDecorator('document', HtmlDocument::class);
    }

    public function testMethodAddDecoratorsWithValidArrayAsParam(): void
    {
        $decoratorFormats = [
            'TestWithOptions',
            'test' => new TestWithOptionsDecorator(),
            'test2' => TestWithOptionsDecorator::class,
            'test3' => [
                'name' => 'TestWithOptions',
                'options' => ['optionKey1' => 'optionValue1', 'options' => ['optionKey2' => 'optionValue2']]
            ]
        ];

        $this->assertCount(count($decoratorFormats), iterator_to_array($this->chain->addDecorators($decoratorFormats)));
    }

    /**
     * @depends testMethodAddDecoratorsWithValidArrayAsParam
     */
    public function testMethodAddDecoratorsWithDecoratorChainAsParam(): void
    {
        $decoratorFormats = [
            'TestWithOptions',
            'test1' => new TestWithOptionsDecorator(),
            'test2' => [
                'name' => 'TestWithOptions',
                'options' => ['optionKey1' => 'optionValue1']
            ]
        ];

        $chain = (new DecoratorChain(FormElementDecoration::class))
            ->addDecoratorLoader(__NAMESPACE__, 'Decorator')
            ->addDecorators($decoratorFormats);

        $this->assertCount(
            count($decoratorFormats),
            iterator_to_array($this->chain->addDecorators($chain))
        );
    }

    public function testMethodGetDecoratorsReturnsTheSameOrderAsTheArrayPassedToAddDecorators(): void
    {
        $decoratorFormats = ['TestWithOptions', 'Test'];
        $decorators = iterator_to_array($this->chain->addDecorators($decoratorFormats));
        $this->assertCount(count($decoratorFormats), $decorators);
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorators[0]);
        $this->assertInstanceOf(TestDecorator::class, $decorators[1]);
        $this->chain->clearDecorators();

        $decoratorFormats = [5 => 'TestWithOptions', 0 => 'Test'];
        $decorators = iterator_to_array($this->chain->addDecorators($decoratorFormats));
        $this->assertCount(count($decoratorFormats), $decorators);
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorators[0]);
        $this->assertInstanceOf(TestDecorator::class, $decorators[1]);
        $this->chain->clearDecorators();

        $decoratorFormats = [
            'test1' => ['name' => 'TestWithOptions'],
            'test2' => ['name' => 'Test']
        ];
        $decorators = iterator_to_array($this->chain->addDecorators($decoratorFormats));
        $this->assertCount(count($decoratorFormats), $decorators);
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorators[0]);
        $this->assertInstanceOf(TestDecorator::class, $decorators[1]);
    }

    public function testAddDecoratorsThrowsExceptionWhenADecoratorAsNonAssociativeArrayDoesNotDefineNameKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid decorator 'test'. Key 'name' is missing");

        $this->chain->addDecorators(['test' => ['TestWithOptions']]);
    }

    public function testAddDecoratorsThrowsExceptionWhenADecoratorAsNonAssociativeArrayDefinesUnknownKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("No other keys except 'name' and 'options' are allowed, got 'unknownKey', '5'");

        $this->chain
            ->addDecorators([
                'test' => ['name' => 'TestWithOptions', 'unknownKey' => 'unknownValue', 5 => 'also unknown'],
            ]);
    }

    public function testAddDecoratorsThrowsExceptionWhenADecoratorAsAssociativeArrayDoesNotDefineOptionsAsArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Can't load decorator 'this is string instead of array'. decorator unknown"
        );

        $this->chain->addDecorators(['something' => 'this is string instead of array']);
    }

    public function testAddDecoratorsThrowsExceptionWhenADecoratorOfTypeNonStringIsDefinedWithoutAnIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unexpected type at position 0, string expected, got ipl\Html\FormDecoration\DecoratorChain instead.'
            . ' Either provide an $identifier (string) as the key, or ensure the value is of type string',
        );

        $this->chain->addDecorators([$this->chain]);
    }

    public function testAddDecoratorsThrowsExceptionWhenADecoratorOfTypeClassStringIsDefinedWithoutAnIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Unexpected type at position 0, string expected, got class ipl\Html\FormDecoration\DecoratorChain instead.'
            . ' Either provide an $identifier (string) as the key, or ensure the value is of type string',
        );

        $this->chain->addDecorators([$this->chain::class]);
    }

    public function testAddDecoratorsDetectsDuplicateIdentifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Decorator with identifier 'Test' already exists. Duplicate identifiers are not allowed."
        );

        $this->chain->addDecorators([
            'Test' => 'TestWithOptions',
            'Test'
        ]);
    }

    public function testHasDecoratorReturnsFalseWhenNoDecoratorsAreAdded(): void
    {
        $this->assertFalse($this->chain->hasDecorator('test'));
    }

    public function testHasDecoratorReturnsTrueWhenDecoratorIsAdded(): void
    {
        $this->chain->addDecorator('test', 'Test');
        $this->assertTrue($this->chain->hasDecorator('test'));
    }

    public function testHasDecoratorReturnsFalseWhenDecoratorIsNotAdded(): void
    {
        $this->chain->addDecorator('test1', 'Test');
        $this->assertFalse($this->chain->hasDecorator('test2'));
    }

    public function testAddDecoratorThrowsExceptionWhenDecoratorIsAlreadyAdded(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(
            'Decorator with identifier "test" already exists. Use replaceDecorator() to replace it.'
        );

        $this->chain
            ->addDecorator('test', 'Test')
            ->addDecorator('test', 'Test');
    }

    public function testReplaceDecoratorWorksEvenIfThereIsNoDecoratorYet(): void
    {
        $this->chain->replaceDecorator('test', 'Test');
        $this->assertTrue($this->chain->hasDecorator('test'));
    }

    public function testReplaceDecoratorWorksWhenDecoratorIsAlreadyAdded(): void
    {
        $this->chain
            ->addDecorator('test', 'Test')
            ->replaceDecorator('test', 'TestWithOptions');

        $decorators = iterator_to_array($this->chain);
        $this->assertCount(1, $decorators);
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorators[0]);
    }

    public function testReplaceDecoratorPreservesDecoratorOrder(): void
    {
        $this->chain
            ->addDecorator('test1', 'Test')
            ->addDecorator('test2', 'TestWithOptions')
            ->replaceDecorator('test1', 'TestRenderElement');

        $decorators = iterator_to_array($this->chain);
        $this->assertInstanceOf(TestRenderElementDecorator::class, $decorators[0]);
        $this->assertInstanceOf(TestWithOptionsDecorator::class, $decorators[1]);
    }

    public function testAddDecoratorThrowsExceptionForUnexpectedClassInstance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expects parameter #2 to be a string or an instance of'
            . ' ipl\Html\Contract\FormElementDecoration, got ipl\Html\HtmlDocument instead',
        );

        $this->chain->addDecorator('document', new HtmlDocument());
    }

    public function testAddDecoratorsThrowsWhenNameKeyIsNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid decorator 'test'. Value of the 'name' key must be a string, got integer instead"
        );

        $this->chain->addDecorators(['test' => ['name' => 5]]);
    }

    public function testAddDecoratorsThrowsForInvalidTypeForIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid type for identifier 'test', expected an array, a string or an instance of"
             . " ipl\Html\Contract\FormElementDecoration, got integer instead",
        );

        $this->chain->addDecorators(['test' => 5]);
    }

    public function testAddDecoratorWithOptionsWithADecoratorWhichDoesNotSupportOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Decorator 'Test' does not support options");

        $this->chain->addDecorator('test', 'Test', ['foo' => 'bar']);
    }
}
