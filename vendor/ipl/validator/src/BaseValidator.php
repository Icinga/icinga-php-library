<?php

namespace ipl\Validator;

use ipl\I18n\Translation;
use ipl\Stdlib\Contract\Validator;
use ipl\Stdlib\Messages;

abstract class BaseValidator implements Validator
{
    use Messages;
    use Translation;
}
