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

namespace BiuradPHP\DependencyInjection\Compiler;

use BiuradPHP\DependencyInjection\Concerns\Compiler;
use BiuradPHP\DependencyInjection\Concerns\ContainerBuilder;
use BiuradPHP\DependencyInjection\Interfaces\CompilerPassInterface;

abstract class AbstractCompilerPass implements CompilerPassInterface
{
    /** @var Compiler */
    protected $compiler;

    abstract function process(ContainerBuilder $container);


    public function getCompiler(): ?Compiler
    {
        return $this->compiler;
    }

    public function setCompiler(Compiler $compiler)
    {
		$new  = clone $this;
        $new->compiler = $compiler;

        return $new;
    }
}
