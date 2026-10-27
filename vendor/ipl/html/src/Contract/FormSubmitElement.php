<?php

namespace ipl\Html\Contract;

interface FormSubmitElement extends FormElement
{
    /**
     * Get whether the element has been pressed
     *
     * @return bool
     */
    public function hasBeenPressed();
}
