<?php

namespace ipl\Web\Common;

use ipl\Html\Form;
use ipl\Html\Contract\FormElement;
use LogicException;

trait FormUid
{
    protected $uidElementName = 'uid';

    /**
     * Create a form element to make this form distinguishable from others
     *
     * You'll have to define a name for the form for this to work.
     *
     * @return FormElement
     */
    protected function createUidElement()
    {
        /** @var Form $this */
        $element = $this->createElement('hidden', $this->uidElementName, ['ignore' => true]);
        $element->getAttributes()->registerAttributeCallback('value', function () {
            /** @var Form $this */
            return $this->getAttributes()->get('name')->getValue();
        });

        return $element;
    }

    /**
     * Get whether the form has been sent
     *
     * A form is considered sent if the request's method equals the form's method
     * and the sent UID is the form's UID.
     *
     * @return bool
     */
    public function hasBeenSent()
    {
        if (! parent::hasBeenSent()) {
            return false;
        } elseif ($this->getMethod() === 'GET') {
            // Get forms are unlikely to require a UID. If they do, change this.
            return true;
        }

        /** @var Form $this */
        $name = $this->getAttributes()->get('name')->getValue();
        if (! $name) {
            throw new LogicException('Form has no name');
        }

        $values = $this->getRequest()->getParsedBody();

        return isset($values[$this->uidElementName]) && $values[$this->uidElementName] === $name;
    }
}
