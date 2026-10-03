<?php

namespace ipl\Orm\Behavior;

use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Exception\ValueConversionException;
use ipl\Orm\Query;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\ExpressionInterface;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;
use UnexpectedValueException;

class UUID extends PropertyBehavior implements QueryAwareBehavior
{
    /** @var bool Whether the query is using a pgsql adapter */
    protected bool $isPostgres = true;

    public function fromDb($value, $key, $_)
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new UnexpectedValueException(sprintf("Unexpected value '%s' for key '%s'", $value, $key));
        }

        try {
            if (! $this->isPostgres) {
                return RamseyUuid::fromBytes($value);
            }

            return RamseyUuid::fromString($value);
        } catch (InvalidArgumentException $e) {
            throw new ValueConversionException($e->getMessage());
        }
    }

    public function toDb($value, $key, $_)
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        if (! $value instanceof UuidInterface) {
            if (! is_string($value)) {
                throw new UnexpectedValueException(sprintf("Unexpected value '%s' for key '%s'", $value, $key));
            }

            try {
                $value = RamseyUuid::fromString($value);
            } catch (InvalidArgumentException $_) {
                throw new ValueConversionException(sprintf("Invalid UUID value provided: %s", $value));
            }
        }

        if (! $this->isPostgres) {
            return $value->getBytes();
        }

        return $value->toString();
    }

    public function setQuery(Query $query)
    {
        $this->isPostgres = $query->getDb()->getAdapter() instanceof Pgsql;

        return $this;
    }
}
