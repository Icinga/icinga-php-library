<?php

namespace ipl\Stdlib\Loader;

use ipl\Stdlib\Contract\PluginLoader;

/**
 * Plugin loader that makes use of registered PHP autoloaders
 */
class AutoloadingPluginLoader implements PluginLoader
{
    /** @var string Namespace of the plugins */
    protected $namespace;

    /** @var string Class name postfix */
    protected $postfix;

    /**
     * Create a new autoloading plugin loader
     *
     * @param string $namespace Namespace of the plugins
     * @param string $postfix   Class name postfix
     */
    public function __construct($namespace, $postfix = '')
    {
        $this->namespace = $namespace;
        $this->postfix = $postfix;
    }

    /**
     * Get the FQN of a plugin
     *
     * @param string $name Name of the plugin
     *
     * @return string
     */
    protected function getFqn($name)
    {
        return $this->namespace . '\\' . ucfirst($name) . $this->postfix;
    }

    public function load($name)
    {
        $class = $this->getFqn($name);

        if (! class_exists($class)) {
            return false;
        }

        return $class;
    }
}
