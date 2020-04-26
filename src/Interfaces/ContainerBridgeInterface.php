<?php

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  DependencyInjection
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/dependencyinjection
 * @since     Version 0.1
 */

namespace BiuradPHP\DependencyInjection\Interfaces;

use BiuradPHP\DependencyInjection\CompilerExtension;

interface ContainerBridgeInterface
{
    public static function of(CompilerExtension $extension): ContainerBridgeInterface;

    public function setConfig($config): ContainerBridgeInterface;

    public function withDefault(string $driver): ContainerBridgeInterface;

    public function getDefinition(string $service);
}