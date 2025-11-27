<?php

namespace ipl\Html\Contract;

use ipl\Html\FormDecoration\DecoratorChain;

/**
 * Representation of form elements that support decoration
 */
interface DecorableFormElement
{
    /**
     * Get all decorators of this element
     *
     * @return DecoratorChain<FormElementDecoration>
     */
    public function getDecorators(): DecoratorChain;

    /**
     * Get whether the element has any decorators
     *
     * @return bool
     */
    public function hasDecorators(): bool;

    /**
     * Decorate the element using its decorators
     *
     * @return void
     */
    public function applyDecoration(): void;
}
