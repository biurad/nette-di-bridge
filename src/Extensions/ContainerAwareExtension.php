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

namespace BiuradPHP\DependencyInjection\Extensions;

use BiuradPHP\DependencyInjection\Definitions\ExtensionDefinitionsHelper;
use BiuradPHP\DependencyInjection\Interfaces\ContainerAwareInterface;

class ContainerAwareExtension extends \BiuradPHP\DependencyInjection\CompilerExtension
{
    /**
     * Tweak DI container
     */
    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        $definitionsHelper = new ExtensionDefinitionsHelper($this->compiler);
        $definitions       = $definitionsHelper->getServiceDefinitionsFromDefinitions(
            $builder->findByType(ContainerAwareInterface::class)
        );

        // Register as services
        foreach ($definitions as $definition) {
            $definition->addSetup('setContainer');
        }
    }
}
