Form element decorators
=======================

This page shows how to use the new element decorators (`ipl\Html\Contract\FormElementDecoration`)
with practical examples.

How element decorators work
---------------------------
An array of decorators is assigned to the form using `setDefaultElementDecorators([...])`, which also determines the
rendering order.
The decoration of an element is triggered only when the form is rendered. This ensures that decoration remains isolated
and does not produce unexpected side effects.

> NOTE: Decorators are registered with unique identifiers to enable easy replacement. If the decorator is provided as a
> string and no identifier is given, the decorator name is used as a fallback. In all other cases, an identifier must be
> specified explicitly.

It is recommended to use the decorator name or its purpose as the identifier, for example, 'container' for an 'HtmlTag'
decorator that wraps content.

Example usage
-------------
Let's decorate a form element using the built-in `Label` and `Description` decorators.

To render a label, then the element itself, and finally a description, configure the default element decorators
in this order. The `RenderElement` decorator is the one that actually outputs the form element itself.

> NOTE: If the `RenderElement` decorator is missing, the form element is appended at the end, after all other
> decorators have been applied.

```php
use ipl\Html\Form;

$form = (new Form())
    ->setDefaultElementDecorators(['Label', 'RenderElement', 'Description']);

$form->addElement('text', 'username', [
    'label' => 'User name',
    'description' => 'Enter your login name',
]);

echo $form;
```

This produces the following HTML:

```html
<form method="POST">
  <label class="form-element-label" for="form-element-...">User name</label>
  <input type="text" name="username" id="form-element-..." aria-describedby="desc_form-element-...">
  <p id="desc_form-element-..." class="form-element-description">Enter your login name</p>
</form>
```

> Note: Only elements created using `Form::createElement()` or `Form::addElement()` are decorated by these default
> element decorators.

Accessibility
-------------
`LabelDecorator`: Ensures accessibility by guaranteeing that the form element has an `id` attribute
(generating one if it is missing) and by linking the `label` tag to the element through the `for` attribute.

`DescriptionDecorator`: Improves accessibility by generating an HTML description element with an `id` attribute and
adding this `id` to the form element as the value of its `aria-describedby` attribute.

Per Element Decorators
----------------------
You can override decorators per element by passing a `decorators` key in the element options. The list
replaces the form's defaults for that specific element.

```php
$form->addElement('password', 'password', [
    'label' => 'Password',
    'description' => 'Minimum 8 characters',
    'decorators' => ['Label', 'RenderElement', 'Description'],
]);
```

Decorator options
-----------------
Some decorators support options. These can be used to change CSS classes or enable other
functionality specific to the decorator. When addressing decorators by name, pass options using the following
array form. For the full list of supported options, see the respective classes under `ipl\Html\FormDecoration`.

```php
$form->setDefaultElementDecorators([
    'label' => [
        'name' => 'Label',
        'options' => [
            'class' => ['form-element-label', 'text-bold']
        ],
    ],
    'RenderElement',
    'description' => [
        'name' => 'Description',
        'options' => [
            'class' => 'help-text'
        ],
    ],
]);
```

HtmlTagDecorator
----------------
`HtmlTagDecorator` lets you add an extra HTML tag around your decorated content or place a tag before or after it.
This is useful for grouping label+element+description into a wrapper, adding layout containers, or placing
additional markup around elements.

- What it can do:
  - `Wrap`: Enclose the current decoration result in an HTML tag (default).
  - `Prepend`: Insert an HTML tag before the current decoration result.
  - `Append`: Insert an HTML tag after the current decoration result.
  - Conditionally apply the decoration based on the element (via a callback).

- Allowed options:
  - `tag` (string, required): The HTML tag name to create (e.g., div, span, p).
  - `class` (string|string[], optional): CSS class(es) to add to the tag.
  - `attrs` (array<string, mixed>, optional): Additional attributes to add to the tag.
  - `transformation` (ipl\Html\FormDecoration\Transformation, optional): One of Wrap, Prepend, Append.
  - `condition` (callable(FormElement): bool, optional): If provided and returns false, the tag is not added.

```php
use ipl\Html\Form;
use ipl\Html\FormDecoration\Transformation;

$form = (new Form())
    ->setDefaultElementDecorators([
        'Label',
        'RenderElement',
        'Description',
        'group' => [
            'name' => 'HtmlTag',
            'options' => [
                'tag'       => 'div',
                'class'     => 'field-group',
            ],
        ],
    ]);
```

This produces the following HTML:

```html
<form method="POST">
  <div class="field-group"> <!-- HtmlTag -->
    <label class="form-element-label" for="form-element-...">User name</label>
    <input type="text" name="username" id="form-element-..." aria-describedby="desc_form-element-...">
    <p id="desc_form-element-..." class="form-element-description">Enter your login name</p>
  </div>
</form>
```

Example: append a span separator after each element

```php
use ipl\Html\FormDecoration\Transformation;

$form->setDefaultElementDecorators([
    'Label',
    'RenderElement',
    'Description',
    'separator' => [
        'name' => 'HtmlTag',
        'options' => [
            'tag'               => 'span',
            'class'             => 'element-separator',
            'transformation'    => Transformation::Append
        ]
    ]
]);
```

This produces the following HTML:

```html
<form method="POST">
  <label class="form-element-label" for="form-element-...">User name</label>
  <input type="text" name="username" id="form-element-..." aria-describedby="desc_form-element-...">
  <p id="desc_form-element-..." class="form-element-description">Enter your login name</p>
  <span class="element-separator"></span> <!-- HtmlTag -->
</form>
```

Example: prepend a span before each element

```php
use ipl\Html\FormDecoration\Transformation;

$form->setDefaultElementDecorators([
    'Label',
    'RenderElement',
    'Description',
    'before' => [
        'name' => 'HtmlTag',
        'options' => [
            'tag'               => 'span',
            'class'             => 'before-control',
            'transformation'    => Transformation::Prepend
        ]
    ]
]);
```

This produces the following HTML:

```html
<form method="POST">
  <span class="before-control"></span> <!-- HtmlTag -->
  <label class="form-element-label" for="form-element-...">User name</label>
  <input type="text" name="username" id="form-element-..." aria-describedby="desc_form-element-...">
  <p id="desc_form-element-..." class="form-element-description">Enter your login name</p>
</form>
```

Example: conditionally wrap only required fields

```php
use ipl\Html\FormDecoration\Transformation;

$form->setDefaultElementDecorators([
    'Label',
    'RenderElement',
    'Description',
    'required' => [
        'name' => 'HtmlTag',
        'options' => [
            'tag'               => 'div',
            'class'             => 'required-field',
            'condition'         => fn ($el) => $el->getAttributes()->has('required')
        ]
    ]
]);
```

If the element has the `required` attribute, this produces the following HTML:

```html
<form method="POST">
  <div class="required-field"> <!-- HtmlTag -->
    <label class="form-element-label" for="form-element-...">User name</label>
    <input type="text" name="username" id="form-element-..." aria-describedby="desc_form-element-..." required>
    <p id="desc_form-element-..." class="form-element-description">Enter your login name</p>
  </div>
</form>
```

If the element is not `required`, the wrapper `div` is omitted and the output matches the basic example.

Built-in decorators
-------------------

The following new decorators are introduced:
- `LabelDecorator`: Renders a `label` tag and links it to the form element with `for` attribute.
- `RenderElementDecorator`: Emits the element itself at the exact position in the decorator chain.
- `DescriptionDecorator`: Renders a `description` and links it via `aria-describedby` to the form element.
- `HtmlTagDecorator`: Wrap/prepend/append a decoration result with a given `tag` and `options`.
- `FieldsetDecorator`: Renders fieldset `legend` and `description`.
- `ErrorsDecorator`: Renders validation `errors` for the element.

Many decorators support options (e.g., CSS class changes), and `HtmlTagDecorator` supports conditional application
with different transformations.


Migrate to the new decorators implementation
--------------------------------------------
The new element decorators (`ipl\Html\Contract\FormElementDecoration`) and the legacy decorator implementation
(`ipl\Html\Contract\FormElementDecorator`) cannot be used together in the same form.

However, you can still assign the new decorators explicitly to a specific element via the element's
`decorators` option. These new decorators take priority over the legacy default decorators for that element.

To switch completely to the new decorators, use `$form->setDefaultElementDecorators([...])` instead of
`$form->setDefaultElementDecorator()`.


Breaking changes
----------------

The legacy decorator was applied to a form element immediately when calling `Form::addElement()` or `Form::decorate()`.
As a result, any later call to `getElement()` returned an element that had already been decorated, which meant that a
wrapper was automatically created around the form element. Based on this behavior, it was always assumed that the
wrapper existed, and it became common practice to call `$form->getElement("name")->getWrapper()` in order to make
further modifications to the wrapper. Once the old decorator was removed, this assumption no longer held true, and form
rendering began to fail because the wrapper was missing.

The new decorators no longer affect the wrapper of an element. A call to `getElement()` now returns the raw element,
and `getWrapper()` will only return a wrapper if one has been explicitly set.

Furthermore, legacy decorators were themselves instances of `ipl\Html\BaseHtmlElement`. This meant that they were only
assembled when needed, and the inspection of their decorated elements only happened then. For example, checking whether
an element has a label. Though, assembly might have happened only right upon rendering the element. In case an
element adjusts its label on its own during assembly, this was being accounted for in the legacy decorators.

The new decorators, on the other hand, inspect elements slightly earlier: When the form itself is being rendered.
Elements might have not been assembled at this stage.

Register your custom decorators
-------------------------------
You need to register loader paths if you have defined your own decorators and want to reference them by short names.

Example for registering a custom namespace:

```php
use ipl\Html\Form;

$form = (new Form())->addElementDecoratorLoaderPaths([
    // [namespace, class name suffix]
    ['My\App\FormDecoration', 'Decorator'], // Loads all decorators under the specified namespace with ‘Decorator’ suffix.
]);
```
