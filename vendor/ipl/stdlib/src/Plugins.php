<?php

namespace ipl\Stdlib;

use ipl\Stdlib\Contract\PluginLoader;
use ipl\Stdlib\Loader\AutoloadingPluginLoader;

trait Plugins
{
    /** @var array Registered plugin loaders by type */
    protected $pluginLoaders = [];

    /**
     * Factory for plugin loaders
     *
     * @param PluginLoader|string $loaderOrNamespace
     * @param string              $postfix
     *
     * @return PluginLoader
     */
    public static function wantPluginLoader($loaderOrNamespace, $postfix = '')
    {
        if ($loaderOrNamespace instanceof PluginLoader) {
            $loader = $loaderOrNamespace;
        } else {
            $loader = new AutoloadingPluginLoader($loaderOrNamespace, $postfix);
        }

        return $loader;
    }

    /**
     * Get whether a plugin loader for the given type exists
     *
     * @param string $type
     *
     * @return bool
     */
    public function hasPluginLoader($type)
    {
        return isset($this->pluginLoaders[$type]);
    }

    /**
     * Add a plugin loader for the given type
     *
     * @param string              $type
     * @param PluginLoader|string $loaderOrNamespace
     * @param string              $postfix
     *
     * @return $this
     */
    public function addPluginLoader($type, $loaderOrNamespace, $postfix = '')
    {
        $loader = static::wantPluginLoader($loaderOrNamespace, $postfix);

        if (! isset($this->pluginLoaders[$type])) {
            $this->pluginLoaders[$type] = [];
        }

        array_unshift($this->pluginLoaders[$type], $loader);

        return $this;
    }

    /**
     * Load the class file of the given plugin
     *
     * @param string $type
     * @param string $name
     *
     * @return string|false
     */
    public function loadPlugin($type, $name)
    {
        if ($this->hasPluginLoader($type)) {
            /** @var PluginLoader $loader */
            foreach ($this->pluginLoaders[$type] as $loader) {
                $class = $loader->load($name);
                if ($class) {
                    return $class;
                }
            }
        }

        return false;
    }

    protected function addDefaultPluginLoader($type, $loaderOrNamespace, $postfix)
    {
        $this->pluginLoaders[$type][] = static::wantPluginLoader($loaderOrNamespace, $postfix);

        return $this;
    }
}
