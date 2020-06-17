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

use BiuradPHP\DependencyInjection\Concerns\ExtensionDefinitionsHelper;
use LogicException;
use Nette;
use Nette\DI\CompilerExtension as NetteCompilerExtension;

/**
 * Configurator compiling extension.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license BSD-3-Clause
 */
abstract class CompilerExtension extends NetteCompilerExtension
{
    /** @var null|ExtensionDefinitionsHelper */
    private $helper;

    /**
     * @internal do not use this function
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * ContainerBuilder is a DI container that provides an API to easily describe services.
     *
     * @return Concerns\ContainerBuilder
     */
    public function getContainerBuilder(): Nette\DI\ContainerBuilder
    {
        return $this->compiler->getContainerBuilder();
    }

    /**
     * Processes configuration data. Intended to be overridden by descendant.
     */
    public function loadConfiguration(): void
    {
    }

    /**
     * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
     */
    public function beforeCompile(): void
    {
    }

    /**
     * Adjusts DI container compiled to PHP class. Intended to be overridden by descendant.
     *
     * @param Nette\PhpGenerator\ClassType $class
     */
    public function afterCompile(Nette\PhpGenerator\ClassType $class): void
    {
    }

    /**
     * Returns the configuration array for the given extension.
     *
     * @return array An array of configuration or false if not found
     */
    protected function getExtensionConfig(string $name)
    {
        try {
            $extensionConfigs = $this->compiler->getExtension($name);
        } catch (LogicException $e) {
            $extensionConfigs = false;
        }

        if (!$this->compiler->hasExtension($name)) {
            $extensionConfigs = $this->compiler->getExtensions($name);
        }

        if (\is_array($extensionConfigs) && \count($extensionConfigs) === 1) {
            $extensionConfigs = $extensionConfigs[\key($extensionConfigs)];
        }

        return !$extensionConfigs ? false : $extensionConfigs->getConfig();
    }

    protected function getHelper(): ExtensionDefinitionsHelper
    {
        if ($this->helper === null) {
            $this->helper = new ExtensionDefinitionsHelper($this->compiler);
        }

        return $this->helper;
    }

    protected function createLoader(): Nette\DI\Config\Loader
    {
        return new Config\Loader();
    }
}
