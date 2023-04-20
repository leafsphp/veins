<?php

namespace Leaf;

use Leaf\Veins\Parser;

/**
 * Leaf Veins
 * ---
 * Simple, fast and powerful PHP template engine.
 *
 * @package Leaf\Veins
 * @version 2.0.0
 * @since 1.0.0
 * @license MIT
 * @author Michael Darko <mickdd22@gmail.com>
 */
class Veins
{
    /**
     * Values to pass into template files
     */
    protected $data = [];

    /**
     * Leaf Veins config
     */
    protected $config = [
        'checksum' => [],
        'charset' => 'UTF-8',
        'debug' => false,
        'templateDir' => 'views/',
        'cacheDir' => 'cache/',
        'baseUrl' => '',
        'phpEnabled' => false,
        'autoEscape' => true,
        'sandbox' => true,
        'removeComments' => false,
        'customTags' => [],
    ];

    /**
     * Add a value to a template file
     */
    public function set($key, $value = null): veins
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Update configuration for Leaf Veins
     */
    public function configure($setting, $value = null): Veins
    {
        if (is_array($setting)) {
            $this->config = array_merge($this->config, $setting);
        } else {
            $this->config[$setting] = $value;
        }

        return $this;
    }

    /**
     * Render a Leaf Vein template file
     */
    public function render(string $file, array $data = []): string
    {
        extract(array_merge($this->data, $data));

        ob_start();
        require Parser::checkTemplate($this->config, $file);
        $html = ob_get_clean();

        return $html;
    }
}
