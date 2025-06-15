<?php

namespace ipl\Html\FormElement;

use ipl\Html\Attributes;
use ipl\Html\Form;

class PasswordElement extends InputElement
{
    /** @var string Dummy passwd of this element to be rendered */
    public const DUMMYPASSWORD = '_ipl_form_5847ed1b5b8ca';

    protected $type = 'password';

    /** @var bool Status of the form */
    protected $isFormValid = true;

    /** @var bool Status indicating if the form got submitted */
    protected $isFormSubmitted = false;

    protected function registerAttributeCallbacks(Attributes $attributes)
    {
        parent::registerAttributeCallbacks($attributes);

        $attributes->registerAttributeCallback(
            'value',
            function () {
                if (
                    $this->hasValue()
                    && count($this->getValueCandidates()) === 1
                    && $this->isFormValid
                    && ! $this->isFormSubmitted
                ) {
                    return self::DUMMYPASSWORD;
                }

                if (parent::getValue() === self::DUMMYPASSWORD && count($this->getValueCandidates()) > 1) {
                    return self::DUMMYPASSWORD;
                }

                return null;
            }
        );
    }

    public function onRegistered(Form $form)
    {
        $form->on(Form::ON_VALIDATE, function ($form) {
            $this->isFormValid = $form->isValid();
        });

        $form->on(Form::ON_SENT, function ($form) {
            $this->isFormSubmitted = $form->hasBeenSent();
        });
    }

    public function getValue()
    {
        $value = parent::getValue();
        $candidates = $this->getValueCandidates();
        while ($value === self::DUMMYPASSWORD) {
            $value = array_pop($candidates);
        }

        return $value;
    }
}
