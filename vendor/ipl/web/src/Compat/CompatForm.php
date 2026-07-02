<?php

namespace ipl\Web\Compat;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use InvalidArgumentException;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\Contract\HtmlElementInterface;
use ipl\Html\Form;
use ipl\Html\FormElement\SubmitButtonElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\I18n\Translation;
use ipl\Web\Compat\FormDecorator\PrimaryButtonDecorator;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use ipl\Web\Compat\FormDecorator\LabelDecorator;
use Stringable;
use Throwable;

class CompatForm extends Form
{
    use Translation;

    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

    /** @var bool Whether to disable the legacy form decorator */
    private bool $disableLegacyDecorator = false;

    /**
     * Apply default element decorators
     *
     * This method must be called before adding any elements to the form.
     *
     * Calling this method disables the legacy decorator.
     *
     * @return $this
     */
    public function applyDefaultElementDecorators(): static
    {
        $this->disableLegacyDecorator = true;

        $this->addElementDecoratorLoaderPaths([
            ['ipl\\Web\\Compat\\FormDecorator', 'Decorator']
        ]);

        $this->getDecorators()->addDecorators([
            'PrimaryButton' => new PrimaryButtonDecorator(),
            'Required' => [
                'name' => LabelDecorator::class,
                'options' => ['uniqueName' => fn(string $name) => Icinga::app()->getRequest()->protectId($name)]
            ]
        ]);

        $this->setDefaultElementDecorators([
            'Label' => $this->getDecorators()->getDecorator('Required'),
            'LabelGroup' => [
                'name' => 'HtmlTag',
                'options' => [
                    'tag' => 'div',
                    'class' => 'control-label-group',
                    'condition' => function (FormElement $element): bool {
                        return ! $element instanceof FormSubmitElement
                            && (! $element instanceof HtmlElementInterface || $element->getTag() !== 'fieldset');
                    }
                ]
            ],
            'Fieldset',
            'Checkbox' => [
                'name' => 'Checkbox',
                'options' => [
                    'uniqueName' => fn(string $name) => Icinga::app()->getRequest()->protectId($name)
                ]
            ],
            'RenderElement',
            'AutosubmitIndication' => [
                'name' => 'AutosubmitIndication',
                'options' => [
                    'uniqueName' => fn(string $name) => Icinga::app()->getRequest()->protectId($name)
                ]
            ],
            'Description' => [
                'name' => 'Description',
                'options' => [
                    'uniqueName' => fn(string $name) => Icinga::app()->getRequest()->protectId($name)
                ]
            ],
            'Errors' => ['name' => 'Errors', 'options' => ['class' => 'errors']],
            'ControlGroup' => [
                'name' => 'HtmlTag',
                'options' => [
                    'tag' => 'div',
                    'class' => 'control-group',
                    'condition' => fn(FormElement $element): bool => ! $element instanceof FormSubmitElement
                ]
            ],
            'FormControls' => [
                'name' => 'HtmlTag',
                'options' => [
                    'tag' => 'div',
                    'class' => 'control-group form-controls',
                    'condition' => fn(FormElement $element): bool => $element instanceof FormSubmitElement
                ]
            ],
        ]);

        return $this;
    }

    /**
     * Render the content of the element to HTML
     *
     * A duplicate of the primary submit button is being prepended if there is more than one present
     *
     * @return string
     */
    public function renderContent(): string
    {
        if (count($this->submitElements) > 1) {
            return (new HtmlDocument())
                ->setHtmlContent(
                    $this->duplicateSubmitButton($this->submitButton),
                    new HtmlString(parent::renderContent())
                )
                ->render();
        }

        return parent::renderContent();
    }

    public function hasDefaultElementDecorator()
    {
        if ($this->disableLegacyDecorator) {
            return false;
        }

        if (parent::hasDefaultElementDecorator()) {
            return true;
        }

        $this->setDefaultElementDecorator(new IcingaFormDecorator());

        return true;
    }

    protected function ensureDefaultElementLoaderRegistered()
    {
        if (! $this->defaultElementLoaderRegistered) {
            $this->addPluginLoader(
                'element',
                'ipl\\Web\\FormElement',
                'Element'
            );

            parent::ensureDefaultElementLoaderRegistered();
        }

        return $this;
    }

    /**
     * Return a duplicate of the given submit button with the `class` attribute fixed to `primary-submit-btn-duplicate`
     *
     * @param FormSubmitElement $originalSubmitButton
     *
     * @return FormSubmitElement
     */
    public function duplicateSubmitButton(FormSubmitElement $originalSubmitButton): FormSubmitElement
    {
        $attributes = (clone $originalSubmitButton->getAttributes())
            ->set('class', 'primary-submit-btn-duplicate');
        $attributes->remove('id');
        // Remove to avoid `type="submit submit"` in SubmitButtonElement
        $attributes->remove('type');

        if ($originalSubmitButton instanceof SubmitElement) {
            $newSubmitButton = new SubmitElement($originalSubmitButton->getName(), $attributes);
            $newSubmitButton->setLabel($originalSubmitButton->getButtonLabel());

            return $newSubmitButton;
        } elseif ($originalSubmitButton instanceof SubmitButtonElement) {
            $newSubmitButton = new SubmitButtonElement($originalSubmitButton->getName(), $attributes);
            $newSubmitButton->setSubmitValue($originalSubmitButton->getSubmitValue());

            return $newSubmitButton;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot duplicate submit button of type "%s"',
            get_class($originalSubmitButton)
        ));
    }

    /**
     * Log an error and surface it as a form message
     *
     * $template may contain a `{error}` placeholder, which is replaced with the error
     * message. Any additional $args are forwarded to {@see Form::addMessage()} for
     * further `sprintf()`-style formatting of $template:
     *
     *     $this->logAndShowError($e, $this->translate('Method "%s" failed: {error}'), $name);
     *
     * @param Throwable|string $error Exception or error message to log and display
     * @param string $template Message to show in the form. {error} is replaced with the
     *   error message.
     * @param mixed ...$args Additional arguments for $template
     *
     * @return void
     */
    protected function logAndShowError(Throwable|string $error, string $template, mixed ...$args): void
    {
        if ($error instanceof Throwable) {
            Logger::error("%s\n%s", $error->getMessage(), IcingaException::getConfidentialTraceAsString($error));
            $errorMessage = $error->getMessage();
        } else {
            Logger::error($error);
            $errorMessage = $error;
        }

        $this->addMessage(str_replace('{error}', $errorMessage, $template), ...$args);
        $this->onError();
    }
}
