<?php

namespace ipl\Orm\Behavior;

use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Contract\RewriteFilterBehavior;
use ipl\Orm\Exception\ValueConversionException;
use ipl\Orm\Query;
use ipl\Sql\Adapter\Pgsql;
use ipl\Stdlib\Filter\Condition;
use UnexpectedValueException;

use function ipl\Stdlib\get_php_type;

/**
 * Support hex filters for binary columns and PHP resource (in) / bytea hex format (out) transformation for PostgreSQL
 */
class Binary extends PropertyBehavior implements QueryAwareBehavior, RewriteFilterBehavior
{
    /** @var bool Whether the query is using a pgsql adapter */
    protected $isPostgres = true;

    public function fromDb($value, $key, $_)
    {
        if (! $this->isPostgres) {
            return $value;
        }

        if ($value !== null) {
            if (is_resource($value)) {
                return stream_get_contents($value);
            }

            return $value;
        }

        return null;
    }

    /**
     * @throws ValueConversionException If value is a resource
     */
    public function toDb($value, $key, $_)
    {
        if (! $this->isPostgres) {
            return $value;
        }

        if (is_resource($value)) {
            throw new ValueConversionException(sprintf('Unexpected resource for %s', $key));
        }

        if ($value === '*') {
            /**
             * Support IS (NOT) NULL filter transformation.
             * {@see \ipl\Sql\Compat\FilterProcessor::assemblePredicate()}
             */
            return $value;
        }

        /**
         * TODO(lippserd): If the filter is moved to a subquery, the value has already been processed.
         * This is because our filter processor is unfortunately doing the transformation twice at the moment:
         *
         * {@link https://github.com/Icinga/ipl-orm/issues/48}
         *
         * {@see \ipl\Orm\Compat\FilterProcessor::requireAndResolveFilterColumns()}
         */
        if (substr($value, 0, 2) === '\\x') {
            return $value;
        }

        return sprintf('\\x%s', bin2hex($value));
    }

    public function setQuery(Query $query)
    {
        $this->isPostgres = $query->getDb()->getAdapter() instanceof Pgsql;

        return $this;
    }

    public function rewriteCondition(Condition $condition, $relation = null)
    {
        /**
         * TODO(lippserd): Duplicate code because {@see RewriteFilterBehavior}s come after {@see PropertyBehavior}s.
         * {@see \ipl\Orm\Compat\FilterProcessor::requireAndResolveFilterColumns()}
         */
        $column = $condition->metaData()->get('columnName');
        if (isset($this->properties[$column])) {
            $value = $condition->metaData()->get('originalValue');

            if ($this->isPostgres && is_resource($value)) {
                throw new UnexpectedValueException(sprintf('Unexpected resource for %s', $column));
            }

            // ctype_xdigit expects strings.
            $value = (string) $value;
            /**
             * Although this code path is also affected by the duplicate behavior evaluation stated in {@see toDb()},
             * no further adjustments are needed as ctype_xdigit returns false for binary and bytea hex strings.
             */
            if (ctype_xdigit($value)) {
                if (! $this->isPostgres) {
                    $condition->setValue(hex2bin($value));
                } elseif (substr($value, 0, 2) !== '\\x') {
                    $condition->setValue(sprintf('\\x%s', $value));
                }
            }
        }
    }
}
