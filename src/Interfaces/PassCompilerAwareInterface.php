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
