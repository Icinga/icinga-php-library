<?php

namespace ipl\Web\Compat;

use ipl\Html\Form;
use ipl\I18n\Translation;
use ipl\Web\FormDecorator\IcingaFormDecorator;

class CompatForm extends Form
{
    use Translation;

    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

    public function hasDefaultElementDecorator()
    {
        if (parent::hasDefaultElementDecorator()) {
            return true;
        }

        $this->setDefaultElementDecorator(new IcingaFormDecorator());

        return true;
    }
}
