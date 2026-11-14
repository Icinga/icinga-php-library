<?php

namespace ipl\Stdlib;

use ipl\Stdlib\Contract\PluginLoader;
use ipl\Stdlib\Loader\AutoloadingPluginLoader;

/**
 * Register plugin loaders by type and resolve plugin class names
 */
trait Plugins
{
    /** @var array<string, PluginLoader[]> Registered plugin loaders by type */
    protected array $pluginLoaders = [];

    /**
     * Factory for plugin loaders
     *
     * @param PluginLoader|string $loaderOrNamespace
     * @param ?string $postfix
     *
     * @return PluginLoader
     */
    public static function wantPluginLoader(
        PluginLoader|string $loaderOrNamespace,
        ?string $postfix = null
    ): PluginLoader {
        return $loaderOrNamespace instanceof PluginLoader
            ? $loaderOrNamespace
            : new AutoloadingPluginLoader($loaderOrNamespace, $postfix);
    }

    /**
     * Get whether a plugin loader for the given type exists
     *
     * @param string $type
     *
     * @return bool
     */
    public function hasPluginLoader(string $type): bool
    {
        return isset($this->pluginLoaders[$type]);
    }

    /**
     * Add a plugin loader for the given type
     *
     * @param string $type
     * @param PluginLoader|string $loaderOrNamespace
     * @param ?string $postfix
     *
     * @return $this
     */
    public function addPluginLoader(
        string $type,
        PluginLoader|string $loaderOrNamespace,
        ?string $postfix = null
    ): static {
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
    public function loadPlugin(string $type, string $name): string|false
    {
        if ($this->hasPluginLoader($type)) {
            foreach ($this->pluginLoaders[$type] as $loader) {
                $class = $loader->load($name);
                if ($class) {
                    return $class;
                }
            }
        }

        return false;
    }

    /**
     * Add a default plugin loader for the given type
     *
     * Default loaders are appended after any loaders added via {@see addPluginLoader()}.
     *
     * @param string $type
     * @param PluginLoader|string $loaderOrNamespace
     * @param string $postfix
     *
     * @return $this
     */
    protected function addDefaultPluginLoader(
        string $type,
        PluginLoader|string $loaderOrNamespace,
        string $postfix
    ): static {
        $this->pluginLoaders[$type][] = static::wantPluginLoader($loaderOrNamespace, $postfix);

        return $this;
    }
}
