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

namespace BiuradPHP\DependencyInjection\Tests\Extensions;

use BiuradPHP\DependencyInjection\Concerns\Compiler;
use BiuradPHP\DependencyInjection\Interfaces\PassCompilerAwareInterface;

class PassCompilerExtension extends ExampleExtension implements PassCompilerAwareInterface
{
    public function addCompilerPasses(Compiler &$compiler): void
    {
    }
}
