<?php

namespace ipl\Tests\Html;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html as h;
use ipl\Html\HtmlDocument;
use ipl\Tests\Html\TestDummy\AddsContentDuringAssemble;
use ipl\Tests\Html\TestDummy\AddsWrapperDuringAssemble;
use ipl\Tests\Html\TestDummy\IterableElement;
use ipl\Tests\Html\TestDummy\ObjectThatCanBeCastedToString;
use RuntimeException;

class HtmlDocumentTest extends TestCase
{
    public function testStaticCallsGiveValidElements()
    {
        $this->assertInstanceOf('ipl\\Html\\HtmlElement', h::span());
    }

    public function testStaticCallsAcceptContentAsFirstAttribute()
    {
        $this->assertRendersHtml('<span>&gt;5</span>', h::span('>5'));
        $this->assertRendersHtml('<span>&gt;5</span>', h::span(['>5']));
        $this->assertRendersHtml(
            '<span><b>&gt;5</b>&lt;</span>',
            h::span([h::b(['>5']), '<'])
        );
    }

    public function testStaticCallsAcceptAttributesAsFirstAttribute()
    {
        $this->assertRendersHtml(
            '<span class="test it" />',
            h::span(['class' => 'test it'])
        );
        $this->assertRendersHtml(
            '<span class="test it">&gt;5</span>',
            h::span(['class' => 'test it'], '>5')
        );
    }

    public function testAttributesAndContentAreAccepted()
    {
        $this->assertRendersHtml(
            '<span class="test it">&gt;5</span>',
            h::span(['class' => 'test it'], ['>5'])
        );
    }

    public function testDocumentSupportsMultipleWrappers()
    {
        $a = h::tag('a');
        $b = h::tag('b');
        $c = (new HtmlDocument())->add('Just some content');
        $c->addWrapper($a);
        $c->addWrapper($b);
        $this->assertRendersHtml(
            '<b><a>Just some content</a></b>',
            $c
        );
    }

    public function testWrapperAddedDuringAssemble()
    {
        $addsWrapperDuringAssemble = new AddsWrapperDuringAssemble();
        $addsWrapperDuringAssemble->add(h::tag('p', 'some text'));
        $this->assertRendersHtml(
            '<div><p>some text</p></div>',
            $addsWrapperDuringAssemble
        );
    }

    public function testDocumentSupportsMultiplePrependedWrappers()
    {
        $a = h::tag('a');
        $b = h::tag('b');
        $c = (new HtmlDocument())->add('Just some content');
        $c->prependWrapper($a);
        $c->prependWrapper($b);
        $this->assertRendersHtml(
            '<a><b>Just some content</b></a>',
            $c
        );
    }

    public function testTopLevelWrappersReferencedFromBelowAgain()
    {
        $a = h::tag('div', ['class' => 'a']);
        $b = h::tag('div', ['class' => 'b']);
        $c = h::tag('div', ['class' => 'c']);
        $d = (new HtmlDocument())->add('Just some');

        $d->addWrapper($a);     // a -> d
        $d->prependWrapper($c); // a -> c -> d
        $c->prependWrapper($b); // a -> b -> c -> d

        // This might also be done during assembly of $a (Think of a form element decorator here)
        $a->add(h::tag('div', 'content'));
        $a->prepend($d);

        // a -> b -> c
        // \         |
        //  \-> d <-/
        //   \-> e

        $this->assertHtml(
            '<div class="a"><div class="b"><div class="c">Just some</div></div><div>content</div></div>',
            $d
        );
    }

    public function testMultipleReferencesToWrappedElementAreCorrectlyResolved(): void
    {
        $a = h::tag('div', ['class' => 'a']);
        $b = h::tag('div', ['class' => 'b']);
        $c = h::tag('div', ['class' => 'c']);

        $a->prependWrapper($b); // b -> a
        $b->addHtml($a);

        // b
        // \
        //  a

        $a->addWrapper($c); // c -> b -> a
        $c->addHtml(h::tag('div', $a));

        // c -> b
        // \    \
        //  d    a
        //  \
        //   a

        $this->assertHtml(
            '<div class="c"><div><div class="b"><div class="a"></div></div></div></div>',
            $a
        );
    }

    public function testWrapperLoopsAreDetected()
    {
        $a = h::tag('a');
        $b = h::tag('b');
        $c = h::tag('c');
        $d = h::tag('d');
        $e = h::tag('e');
        $f = h::tag('f');

        $f->addWrapper($e); // e -> f
        $f->addWrapper($d); // d -> e -> f
        $f->addWrapper($c); // c -> d -> e -> f
        $f->addWrapper($b); // b -> c -> d -> e -> f
        $f->addWrapper($a); // a -> b -> c -> d -> e -> f
        $a->addWrapper($f); // ~

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Wrapper loop detected');

        $f->render();
    }

    public function testWrapperLoopsAreDetectedAsLateAsPossible()
    {
        $a = h::tag('div', ['class' => 'a']);
        $b = h::tag('div', ['class' => 'b']);
        $c = h::tag('div', ['class' => 'c']);

        $a->addWrapper($c); // c -> a
        $a->prependWrapper($b); // c -> b -> a

        $c->addHtml($a);
        $b->addHtml($a);

        // c -> b -> a
        // \--->\-->/

        $this->assertHtml(
            '<div class="c"><div class="b"><div class="a"></div></div></div>',
            $a
        );
    }

    public function testWrapperReuseWorks()
    {
        $a = h::tag('div', ['class' => 'a']);
        $b = h::tag('div', ['class' => 'b']);
        $c = h::tag('div', ['class' => 'c']);
        $d = h::tag('div', ['class' => 'd']);

        $c->addWrapper($b); // b -> c
        $b->addWrapper($a); // a -> b -> c

        $d->addWrapper($a); // a -> d
        $c->add($d);

        // a -> b -> c
        // \-------->\-> d

        $this->assertHtml(
            '<div class="a"><div class="b"><div class="c"><div class="a"><div class="d"></div></div></div></div></div>',
            $c
        );
    }

    public function testRenderedByIsCorrectlyReset()
    {
        $a = h::tag('div', ['class' => 'a']);
        $b = h::tag('div', ['class' => 'b']);
        $c = h::tag('div', ['class' => 'c']);
        $d = h::tag('div', ['class' => 'd']);
        $e = h::tag('div', ['class' => 'e']);

        $a->setWrapper($b); // b -> a
        $c->setWrapper($d); // d -> c

        // b -> a
        // \-->/
        $b->addHtml($a);

        // d -> c
        // \-->/
        $d->addHtml($c);

        $a->prependWrapper(clone $e); // b -> e -> a
        $c->prependWrapper($e); // d -> e -> c

        $this->assertHtml(
            '<div class="b"><div class="e"><div class="a"></div></div></div>',
            $a
        );
        $this->assertHtml(
            '<div class="d"><div class="e"><div class="c"></div></div></div>',
            $c
        );
    }

    public function testWrapperCanPositionWrappedElement()
    {
        $a = h::tag('div', 'content');
        $a->addWrapper(h::tag('div', ['class' => 'wrapper'], $a));

        $this->assertHtml(
            '<div class="wrapper"><div>content</div></div>',
            $a
        );
    }

    public function testWrapperCanPositionWrappedElementInAnExtraContainer()
    {
        $a = h::tag('div', 'content');
        $a->addWrapper(h::tag('div', ['class' => 'outer'], h::tag('div', ['class' => 'inner'], $a)));

        $this->assertHtml(
            '<div class="outer"><div class="inner"><div>content</div></div></div>',
            $a
        );
    }

    public function testAcceptsObjectsWhichCanBeCastedToString()
    {
        $object = new ObjectThatCanBeCastedToString();
        $a = new HtmlDocument();
        $a->add($object);
        $this->assertEquals('Some String &lt;:-)', $a->render());
    }

    public function testSetContentFlattensNestedArrays()
    {
        $doc = new HtmlDocument();
        $doc->setSeparator(';');
        $doc->setContent([
            h::tag('span'),
            [
                'foo',
                'bar',
                [
                    h::tag('p', 'test'),
                    h::tag('strong', 'bla')
                ]
            ]
        ]);
        $this->assertHtml(
            '<span></span>;foo;bar;<p>test</p>;<strong>bla</strong>',
            $doc
        );
    }


    public function testAddFlattensNestedArrays()
    {
        $doc = new HtmlDocument();
        $doc->setSeparator(';');
        $doc->add([
            h::tag('span'),
            [
                'foo',
                'bar',
                [
                    h::tag('p', 'test'),
                    h::tag('strong', 'bla')
                ]
            ]
        ]);
        $this->assertHtml(
            '<span></span>;foo;bar;<p>test</p>;<strong>bla</strong>',
            $doc
        );
    }

    public function testPrependFlattensNestedArrays()
    {
        $doc = new HtmlDocument();
        $doc->setSeparator(';');
        $doc->prepend([
            h::tag('span'),
            [
                'foo',
                'bar',
                [
                    h::tag('p', 'test'),
                    h::tag('strong', 'bla')
                ]
            ]
        ]);
        $this->assertHtml(
            '<span></span>;foo;bar;<p>test</p>;<strong>bla</strong>',
            $doc
        );
    }

    public function testSkipsNullValues()
    {
        $a = new HtmlDocument();
        $a->setSeparator('x');
        $a->add([null, null]);
        $a->add(null);
        $a->add(null);
        $this->assertEquals('', $a->render());
    }

    public function testElementIsCleanlyRemoved()
    {
        $a = new HtmlDocument();
        $s1 = h::tag('b', 'one');
        $s2 = h::tag('b', 'two');
        $s3 = h::tag('b', 'three');
        $a->add([$s1, $s2, $s3]);
        $a->ensureAssembled();
        $a->remove($s2);
        $this->assertEquals(
            '<b>one</b><b>three</b>',
            $a->render()
        );
        $a->add($s1);
        $this->assertEquals(
            '<b>one</b><b>three</b><b>one</b>',
            $a->render()
        );
        $a->remove($s1);
        $this->assertEquals(
            '<b>three</b>',
            $a->render()
        );
    }

    public function testAddFromCorrectlyPassesElements()
    {
        $ul = h::tag('ul');
        $ul->add(h::tag('li', ['style' => 'margin:0'], 'one'));
        $ul->add(h::tag('li', 'two'));
        $ul->add(h::tag('li', 'three'));

        $ol = h::tag('ol');
        $ol->addFrom($ul);

        $this->assertRendersHtml(
            '<ol><li style="margin:0">one</li><li>two</li><li>three</li></ol>',
            $ol
        );
    }

    public function testAddFromCorrectlyPassesElementsWithCallback()
    {
        $ul = h::tag('ul');
        $ul->add(h::tag('li', ['id' => '1'], 'one'));
        $ul->add(h::tag('li', ['id' => '2'], 'two'));
        $ul->add(h::tag('li', ['id' => '3'], 'three'));

        $div = h::tag('div');
        $div->addFrom($ul, function (BaseHtmlElement $item) {
            if ($item->getAttributes()->get('id')->getValue() !== '2') {
                $item->setTag('p');
                $item->getAttributes()->remove('id');
                return $item;
            }
        });

        $this->assertRendersHtml(
            '<div><p>one</p><p>three</p></div>',
            $div
        );
    }

    public function testAddSupportsIterable()
    {
        $content = function () {
            yield h::tag('b', 'foo');
            yield h::tag('b', 'bar');
        };

        $html = (new HtmlDocument())
            ->add($content());

        $this->assertHtml('<b>foo</b><b>bar</b>', $html);
    }

    public function testPrependSupportsIterable()
    {
        $content = function () {
            yield h::tag('b', 'foo');
        };

        $html = (new HtmlDocument())
            ->add(h::tag('b', 'bar'))
            ->prepend($content());

        $this->assertHtml('<b>foo</b><b>bar</b>', $html);
    }

    public function testAddSupportsIterableValidHtml()
    {
        $html = (new HtmlDocument())
            ->add(new IterableElement());

        $this->assertHtml('<b>foo</b><b>bar</b>', $html);
    }

    public function testPrependSupportsIterableValidHtml()
    {
        $html = (new HtmlDocument())
            ->add(h::tag('b', 'baz'))
            ->prepend(new IterableElement());

        $this->assertHtml('<b>foo</b><b>bar</b><b>baz</b>', $html);
    }

    public function testIsEmptyRespectsContentAddedInAssemble()
    {
        $this->assertFalse((new AddsContentDuringAssemble())->isEmpty());
    }

    public function testOnAssembledDocument()
    {
        $a = new HtmlDocument();
        $s1 = h::tag('span', 'one');
        $s2 = h::tag('span', 'two');
        $s3 = h::tag('span', 'three');
        $a->add([$s1, $s2]);

        $a->on(HtmlDocument::ON_ASSEMBLED, function ($b) use ($s2, $s3) {
            $b->remove($s2);
            $b->add($s3);
        });

        $this->assertEquals(
            '<span>one</span><span>three</span>',
            $a->render()
        );
    }

    public function testInsertAfter()
    {
        $a = new HtmlDocument();
        $s1 = h::tag('span', 'one');
        $s2 = h::tag('span', 'two');
        $s3 = h::tag('span', 'three');
        $a->add([$s1, $s2]);
        $a->insertAfter($s3, $s1);

        $this->assertEquals(
            '<span>one</span><span>three</span><span>two</span>',
            $a->render()
        );
    }

    public function testInsertBefore()
    {
        $a = new HtmlDocument();
        $s1 = h::tag('span', 'one');
        $s2 = h::tag('span', 'two');
        $s3 = h::tag('span', 'three');
        $a->add([$s1, $s2]);
        $a->insertBefore($s3, $s1);

        $this->assertEquals(
            '<span>three</span><span>one</span><span>two</span>',
            $a->render()
        );
    }
}
