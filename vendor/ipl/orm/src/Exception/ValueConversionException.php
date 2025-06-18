<?php

namespace ipl\Orm\Exception;

use Exception;

/**
 * Exception thrown if values to be converted don't meet their constraints when reading or writing to the database
 */
class ValueConversionException extends Exception
{
}
