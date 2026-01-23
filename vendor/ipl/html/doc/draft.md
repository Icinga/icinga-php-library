

First of all, we want you to never care about escaping content. This happens
automatically. You render any kind of String? It will be escaped. 

```php
<?php

use ipl\Html\Html;

echo Html::escape('This is true: 2 > 1');
```



This should be used directly or indirectly every time you want to create a
standalone text node based on an unescaped string. `Text` implements `ValidHtml`.

#### Text Usage

```php
<?php

use ipl\Html\Text;

$text = new Text('This is true: 2 > 1');
echo $text->render();
```

Short form:

```php
echo Text::create('This is true: 2 > 1');
```

Both examples will result in the following output:

```html
This is true: 2 &gt; 1
```

### Html Tags



### DeferredText

Like `Text`, but instead of a string you'll give it a callback that must then
be able to return a string at render time. This is extremely helpful when you
want to defer I/O-bound lookups to save roundtrips:

```php
<?php

use ipl\Html\Html;
use ipl\Html\DeferredText;

$ul = Html::tag('ul');
foreach ($this->getRows() as $row) {
    $li = Html::tag('li');
    $li->add(DeferredText::create($this->lazyLoadSomething($row)));
    $ul->add($li);
}
``` 


### FormattedString

```php
<?php

use ipl\Html\FormattedString;

$string = new FormattedString('The <special> %s is %s.', ['car', 'red']);
echo $string->render();
```

FormattedString uses `sprintf`, and therefore uses the same [string format](http://www.php.net/sprintf).
While the constructor requires an array with optional arguments, the `::create()`
shortcut accepts a variable amount of parameters:

```php
<?php
echo FormattedString::create('The <special> %s is %s.', 'car', 'red');
```

Both examples have the same output:

```html
The &lt;special&gt; car is red.
```

### HtmlString

> WARNING: this class is dangerous. When using it, it's up to you to make sure
>          that all your input is correctly escaped according our main rules

This class extends the `Text` class, but there is one small but very important
difference: it blindly trusts you that the given string is valid HTML. It will
not be touched and treated like any other `ValidHtml`. So, `HtmlString` can be
seen as a container telling others "Yes, I'm carrying valid Html". In case you
lie to `HtmlString`, it will lie for you.

#### HtmlString Usage

```php
<?php

use ipl\Html\HtmlString;

$html = new HtmlString('<h1>Custom HTML</h1>');
echo $html->render();
```

Short form:

```php
<?php

echo HtmlString::create('<h1>Custom HTML</h1>');
```

Both examples will produce the following output:

```html
<h1>Custom HTML</h1>
```

Be aware of the fact that all this means, that the following invalid string...
```php
<?php

echo HtmlString::create('<h1');
```

...will happily be rendered as such:

```html
<h1
```

Html
----

Html is the base class for all 


BaseElement
-----------

This abstract class should be the base of every HTML element (or HTML tag). It
provides useful helpers, access to HTML attributes and decides whether your
Element needs a closing tag or not.

We'll later on learn more about how to correctly extend this when implementing
own HTML widgets. For now, it should suffice to know that this base class exists.

HtmlElement
-----------

Every time you create a custom Html Element, this will be an instance of
`ipl\Html\HtmlElement`. In addition to the abstract `BaseHtmlElement`, this
class comes with a constructor and a static `::create()` helper method. Both
have a mandatory `$tag` parameter and optionally allow for `$attributes` and
`$content`.

Class Hierarchy
---------------

```
ValidHtml
  -> HtmlElement
  -> Text
     -> HtmlString

```




Related View Helpers
--------------------

Every HTML view is an instance of `ValidHtml` ...
