<?php

namespace ipl\Stdlib\Contract;

/**
 * Load plugin class names by plugin name
 *
 * Implementations must provide the fully qualified class name of a plugin via {@see load()}.
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
    public function load(string $name): string|false;
}
