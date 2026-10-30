<?php

namespace ipl\Validator;

use ipl\I18n\Translation;
use ipl\Stdlib\Contract\Validator;
use ipl\Stdlib\Messages;

/**
 * Base class providing Messages and Translation support
 */
abstract class BaseValidator implements Validator
{
    use Messages;
    use Translation;
}
