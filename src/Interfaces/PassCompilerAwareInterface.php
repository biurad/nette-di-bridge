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

use BiuradPHP\DependencyInjection\Concerns\Compiler;

/**
 * ContainerAwareInterface should be implemented by classes that depends on a Container.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface PassCompilerAwareInterface
{
    /**
     * Adds a pass to the PassConfig, implementing $compiler->addPass method.
     *
     * @param Compiler &$compiler
     */
    public function addCompilerPasses(Compiler &$compiler): void;
}
