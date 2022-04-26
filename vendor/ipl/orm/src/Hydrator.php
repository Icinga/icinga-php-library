<?php

namespace ipl\Orm;

use function ipl\Stdlib\get_php_type;

/**
 * Hydrates raw database rows into concrete model instances.
 */
class Hydrator
{
    /** @var Behaviors The model's behaviors */
    protected $behaviors;

    /** @var array Column to property resolution map */
    protected $columnToPropertyMap;

    /** @var array Property defaults in terms of key-value pairs */
    protected $defaults;

    /** @var array Additional hydration rules for the model's relations */
    protected $hydrators = [];

    /**
     * Set the model's behaviors
     *
     * @param Behaviors $behaviors
     *
     * @return $this
     */
    public function setBehaviors(Behaviors $behaviors)
    {
        $this->behaviors = $behaviors;

        return $this;
    }

    /**
     * Set the column to property resolution map
     *
     * @param array $columnToPropertyMap
     *
     * @return $this
     */
    public function setColumnToPropertyMap(array $columnToPropertyMap)
    {
        $this->columnToPropertyMap = $columnToPropertyMap;

        return $this;
    }

    /**
     * Get defaults
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Set defaults
     *
     * @param array $defaults
     *
     * @return $this
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Add a hydration rule
     *
     * @param string    $path                Property path
     * @param string    $propertyName        The name of the property to hydrate into
     * @param string    $class               The class to use for the model instance
     * @param array     $columnToPropertyMap Column to property resolution map
     * @param array     $defaults            Default properties
     * @param Behaviors $behaviors           The class' behaviors
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If a hydrator for the given property already exists
     * @throws \InvalidArgumentException If the class to use for the model class is not a subclass of {@link Model}
     */
    public function add($path, $propertyName, $class, array $columnToPropertyMap, array $defaults, Behaviors $behaviors)
    {
        if (isset($this->hydrators[$path])) {
            throw new \InvalidArgumentException("Hydrator for property '$propertyName' already exists");
        }

        // Test model class
        $model = new $class();
        if (! $model instanceof Model) {
            throw new \InvalidArgumentException(sprintf(
                '%s() expects parameter 2 to be a subclass of %s, %s given',
                __METHOD__,
                Model::class,
                get_php_type($model)
            ));
        }

        // TODO: Maybe .. teach this about the query after all?
        // Then only $propertyName and $class would need to be passed
        $this->hydrators[$path] = [$propertyName, $class, $columnToPropertyMap, $defaults, $behaviors];

        //natcasesort($this->hydrators);

        return $this;
    }

    /**
     * Hydrate the given raw database rows into the specified model
     *
     * @param array $data
     * @param Model $model
     *
     * @return Model
     */
    public function hydrate(array $data, Model $model)
    {
        $properties = $this->extractAndMap($data, $this->columnToPropertyMap);

        foreach ($this->hydrators as $path => $vars) {
            list($propertyName, $class, $columnToPropertyMap, $defaults, $behaviors) = $vars;

            $subject = &$properties;
            $parts = explode('.', $path);
            array_pop($parts);
            foreach ($parts as $part) {
                $subject = &$subject[$part];
            }

            /** @var Model $target */
            $target = new $class();
            $target->setProperties($this->extractAndMap($data, $columnToPropertyMap) + $defaults);
            $behaviors->retrieve($target);
            $subject[$propertyName] = $target;
        }

        // If there are any columns left, add them to the base model's properties
        $properties += $data;

        if ($this->defaults !== null) {
            $properties += $this->defaults;
        }

        $model->setProperties($properties);

        $this->behaviors->retrieve($model);

        return $model;
    }

    /**
     * Extract and map the given data based on the specified column to property resolution map
     *
     * @param array $data
     * @param array $columnToPropertyMap
     *
     * @return array
     */
    protected function extractAndMap(array &$data, array $columnToPropertyMap)
    {
        $extracted = [];
        foreach (array_intersect_key($columnToPropertyMap, $data) as $column => $property) {
            $extracted[$property] = $data[$column];
            unset($data[$column]);
        }

        return $extracted;
    }
}
