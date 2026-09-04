<?php

namespace ipl\Orm\Behavior;

use BackedEnum;
use InvalidArgumentException;
use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Exception\ValueConversionException;
use ValueError;

/**
 * Convert backed-enum database values to and from PHP enum instances
 *
 * The enum's backing value (string|int) is stored in the database. On retrieval the raw scalar
 * is converted to the corresponding enum case via {@see BackedEnum::from()}; on persistence the
 * enum instance is unwrapped back to its backing value. Null is preserved as null in both directions.
 *
 * ```php
 * $behaviors->add(new EnumCast(Status::class, ['status']));
 * ```
 */
class EnumCast extends PropertyBehavior
{
    /**
     * The fully qualified class name of the target-backed enum
     *
     * @var class-string<BackedEnum>
     */
    protected string $enum;

    /**
     * @param class-string<BackedEnum> $enum       Fully-qualified class name of the target backed enum
     * @param array<mixed>             $properties Property names to process, as values
     */
    public function __construct(string $enum, array $properties)
    {
        if (! is_subclass_of($enum, BackedEnum::class)) {
            throw new InvalidArgumentException("$enum must be a BackedEnum");
        }

        $this->enum = $enum;

        parent::__construct($properties);
    }

    /**
     * @param int|string|null $value
     *
     * @return ?BackedEnum
     *
     * @throws ValueConversionException
     */
    public function fromDb($value, $key, $_)
    {
        if ($value === null || $value instanceof $this->enum) {
            return $value;
        }

        try {
            return $this->enum::from($value);
        } catch (ValueError $e) {
            throw new ValueConversionException(sprintf(
                "%s (key: '%s')",
                $e->getMessage(),
                $key
            ));
        }
    }

    /**
     * @param BackedEnum|int|string|null $value
     *
     * @return int|string|null
     *
     * @throws ValueConversionException
     */
    public function toDb($value, $key, $_)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof $this->enum) {
            return $value->value;
        }

        try {
            return $this->enum::from($value)->value;
        } catch (ValueError $e) {
            throw new ValueConversionException(sprintf(
                "%s (key: '%s')",
                $e->getMessage(),
                $key
            ));
        }
    }
}
