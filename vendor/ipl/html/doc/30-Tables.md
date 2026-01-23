ipl\Html\Table
==============

Creating tables is a common task, and as all tables have roughly the very same
structure there is a dedicated class helping you with this.

Your very first Table
---------------------

```php
<?php
 
use ipl\Html\Table;

echo new Table();
```

The output is pretty boring and not very helpful:

```html
<table></table>
```

You could now of course create a `tbody` tag, add some `tr` elements, each of
them with some `td` elements and so on. `ipl\Html\Table` however tries to make
your life easier. So let's just add a simple string to our table:

```php
<?= (new Table())->add('Some <special> string!');
```

Some magic kicks in, as the output looks as follows:

```html
<table>
<tbody>
<tr><td>Some &lt;special&gt; string!</td></tr>
</tbody>
</table>
```

Let's try again with an array:

```php
<?= (new Table())->add([
    'app1.example.com',
    '127.0.0.1',
    'production'
]);
```

As one might expect, output looks like this:

```html
<table>
<tbody>
<tr>
<td>app1.example.com</td><td>127.0.0.1</td><td>production</td>
</tr>
</tbody>
</table>
```

The `Table` class comes with some helper methods that want to make your life
easier. One of them is `Table::row`. It creates a new `tr` Html Element, and
accepts an array as it's first parameter. Let's give it a try:

```php
<?= Table::row([
    'app1.example.com',
    '127.0.0.1',
    'production'
])->setSeparator("\n");
```

It outputs exactly one row:

```html
<tr>
<td>app1.example.com</td>
<td>127.0.0.1</td>
<td>production</td>
</tr>
```

This is what also happened internally in our Array example from above. The HTML
standard allows only specific elements to be added to a Table, that's why we can
allow this class to make magic assumptions in case you add something different.

You can of course add such a `tr` element to a table, the result will still be
fine:

```php
<?= (new Table())->add(Table::row([
  'app1.example.com',
  '127.0.0.1',
  'production'
]));
```

As you can see, the output matches the above one:

```html
<table>
<tbody>
<tr><td>app1.example.com</td><td>127.0.0.1</td><td>production</td></tr>
</tbody>
</table>
```

So to keep things simple you can stick with the array syntay:

Let's try again with an array:

```php
<?= (new Table())
    ->add(['app1.example.com', '127.0.0.1', 'production'])
    ->add(['app2.example.com', '127.0.0.2', 'production'])
    ->add(['app3.example.com', '127.0.0.3', 'testing']);
```

Simple and works as expected:

```html
<table>
<tbody>
<tr>
<td>app1.example.com</td><td>127.0.0.1</td><td>production</td>
</tr>
<tr>
<td>app2.example.com</td><td>127.0.0.2</td><td>production</td>
</tr>
<tr>
<td>app3.example.com</td><td>127.0.0.3</td><td>testing</td>
</tr>
</tbody>
</table>
```
