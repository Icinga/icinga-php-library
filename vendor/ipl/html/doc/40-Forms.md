ipl\Html\Form
=============

You probably rarely ever create a web application without some nice forms. And
forms are tough to get right. This part of the library tries to ease many tasks
and this tutorial wants to help you to get started with our Forms.

Overview
--------

A form consists mainly of:

* the form itself (`ipl\Html\Form`)
* any number of form elements (`ipl\Html\FormElement\BaseFormElement`)

In addition to this, there might be:

* element decorators (`ipl\Html\FormDecorator`)
* validators (`ipl\Validator`)
* sub forms, field sets, buttons...

Please note that default HTTP method to submit `ipl\Html\Form` with is **POST**.

Create your very first form
---------------------------

```php
<?php

use ipl\Html\Form;

$form = new Form();
$form->setAction('/your/url');
$form->addElement('text', 'name', ['label' => 'Your name']);

echo $form;
```

This outputs:

```html
<form action="/your/url" method="POST"><input name="name" type="text" /></form>
```

Select
------

Let's add a select box:

```php
<?php
$form->addElement('select', 'customer', [
    'label'   => 'Customer',
    'options' => [
        null => 'Please choose',
        '1'  => 'The one',
        '4'  => 'Four',
        '5'  => 'Hi five',
    ],
]);
```

Now, you eventually might want to interact with that element. Fetch it by name
and treat it like every other `HtmlElement`. Let's assume we do not want to add
some attribute and not to have the whole HTML string on a single line:

```php
<?= $form->getElement('customer')->addAttributes([
    'class' => ['important', 'customer'],
    'id'    => 'customer-field'
])->setSeparator("\n");
```

The result should look as expected:

```html
<select name="customer" class="important customer" id="customer-field">
<option value="" selected>Please choose</option>
<option value="1">The one</option>
<option value="4">Four</option>
<option value="5">Hi five</option>
</select>
```

Setting values
--------------

Often you want to preset some values. This is possible either for the whole
form via `$form->populate($values)` or for single element via
`$element->setValue($value)`.

Let's try this out with our shiny new form:

```php
<?php

$form->populate([
    'name'     => 'John Doe',
    'customer' => '5'
]);
$form->getElement('customer')->setValue('4');

print_r($form->getValues());
```

Expected output:
```
Array
(
    [name] => John Doe
    [customer] => 4
)
```

It's also possible to preset values during element creation:

```form
$this->addElement('hidden', 'foo', ['value' => 'bar']);
```

Form Elements
-------------

We have seen that we can define form element types as strings, like `text` an
element of type text. Who ever wrote HTML forms from scratch knows that it is
not enough to just specify `type="text"` for your `input` element. Some elements
come with a lot of custom logic.

You can find the implementation for our Form Elements sits in the namespace
`ipl\Html\FormElement`. It is perfectly legal to manually instantiate those
elements on your own:

```php
<?php

use ipl\Html\Form;
use ipl\Html\FormElement\TextElement;

$form = new Form();
$nameElement = new TextElement('name', ['class' => 'important']);
$form->addElement($nameElement);
```

Controlling Form Markup
-----------------------

When working with forms you'll very soon arrive to the point where you want to
have more influence on how Elements are rendered. `ipl\Html` allows you to have
full control over your markup in case you need such. Have a look at the following
example:

```php
<?php
use ipl\Html\Form;
use ipl\Html\Html;

$form = new Form();

echo $form
    ->registerElement($form->createElement('text', 'first_name'))
    ->registerElement($form->createElement('text', 'last_name'))
    ->add(Html::tag('div', [
        $form->getElement('first_name'),
        Html::tag('br'),
        $form->getElement('last_name'),
    ]));
```

The result looks as expected:

```html
<form method="POST"><div>
<input name="first_name" type="text" />
<br />
<input name="last_name" type="text" />
</div></form>
```
