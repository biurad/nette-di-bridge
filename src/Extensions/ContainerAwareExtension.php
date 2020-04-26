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

namespace BiuradPHP\DependencyInjection\Extensions;

use BiuradPHP\DependencyInjection\Interfaces\ContainerAwareInterface;

class ContainerAwareExtension extends \BiuradPHP\DependencyInjection\CompilerExtension
{

	/**
	 * Tweak DI container
	 */
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Register as services
		foreach ($builder->findByType(ContainerAwareInterface::class) as $service) {
			$service->addSetup('setContainer');
		}
	}

}
