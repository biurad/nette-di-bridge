<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\DependencyInjection;

/**
 * The singleton base class restricts the instantiation of
 * derived classes to one object only.
 *
 * @final
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Singleton
{
    /**
     * @var Singleton
     */
    private static $instance;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead.
     */
    private function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it).
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it).
     */
    private function __wakeup(): void
    {
    }

    /**
     * gets the instance via lazy initialization (created on first usage).
     */
    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Sets the singleton instance. For testing purposes.
     *
     * @ignore
     */
    public static function setInstance($instance): void
    {
        $class                    = static::class;
        static::$instance[$class] = $instance;
    }

    /**
     * @ignore
     */
    public static function clearAll(): void
    {
        static::$instance = [];
    }
}
