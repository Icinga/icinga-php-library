<?php

namespace ipl\Stdlib\Contract;

/**
 * Representation of plugin loaders
 *
 * Plugin loaders must implement the {@link load()} method in order to provide the fully qualified class name of a
 * plugin to load.
 */
interface PluginLoader
{
    /**
     * Load the class file for a given plugin name
     *
     * @param string $name Name of the plugin
     *
     * @return string|false FQN of the plugin's class if found, false otherwise
     */
    public function load($name);
}
