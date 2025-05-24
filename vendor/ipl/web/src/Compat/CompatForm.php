<?php

namespace ipl\Web\Compat;

use http\Exception\InvalidArgumentException;
use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\Form;
use ipl\Html\FormElement\SubmitButtonElement;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\I18n\Translation;
use ipl\Web\FormDecorator\IcingaFormDecorator;

class CompatForm extends Form
{
    use Translation;

    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

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
}
