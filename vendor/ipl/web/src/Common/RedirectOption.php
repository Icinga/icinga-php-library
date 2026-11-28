<?php

namespace ipl\Web\Common;

use ipl\Html\Contract\FormElement;
use ipl\Html\Form;
use LogicException;

trait RedirectOption
{
    /**
     * Create a form element to retrieve the redirect target upon form submit
     *
     * @return FormElement
     */
    protected function createRedirectOption()
    {
        /** @var Form $this */
        return $this->createElement('hidden', 'redirect');
    }

    /**
     * @see Form::getRedirectUrl()
     */
    public function getRedirectUrl()
    {
        /** @var Form $this */
        $redirectOption = $this->getValue('redirect');
        if (! $redirectOption) {
            return parent::getRedirectUrl();
        }

        if (! $this->hasElement('CSRFToken') || ! $this->getElement('CSRFToken')->isValid()) {
            throw new LogicException(
                'It is not safe to accept redirect targets from submit values without CSRF protection'
            );
        }

        return $redirectOption;
    }
}
