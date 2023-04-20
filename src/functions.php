<?php

declare(strict_types=1);

if (!function_exists('veins')) {
    /**
     * Return the Leaf veins instance
     *
     * @return Leaf\Veins
     */
    function veins()
    {
        if (!(\Leaf\Config::get('veins.instance'))) {
            \Leaf\Config::set('veins.instance', new Leaf\Veins());
        }

        return \Leaf\Config::get('veins.instance');
    }
}
