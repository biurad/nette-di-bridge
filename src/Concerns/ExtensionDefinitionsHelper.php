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

namespace BiuradPHP\DependencyInjection\Concerns;

use Nette\DI\Definitions\Definition;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\LocatorDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\Resolver;
use Nette\Utils\Strings;

class ExtensionDefinitionsHelper
{
    /** @var Compiler */
    private $compiler;

    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * @param Definition[] $definitions
     *
     * @return ServiceDefinition[]
     */
    public function getServiceDefinitionsFromDefinitions(array $definitions): array
    {
        $serviceDefinitions = [];
        $resolver           = new Resolver($this->compiler->getContainerBuilder());

        foreach ($definitions as $definition) {
            if ($definition instanceof ServiceDefinition) {
                $serviceDefinitions[] = $definition;
            } elseif ($definition instanceof FactoryDefinition) {
                $serviceDefinitions[] = $definition->getResultDefinition();
            } elseif ($definition instanceof LocatorDefinition) {
                $references = $definition->getReferences();

                foreach ($references as $reference) {
                    // Check that reference is valid
                    $reference = $resolver->normalizeReference($reference);
                    // Get definition by reference
                    $definition = $resolver->resolveReference($reference);
                    // Only ServiceDefinition should be possible here
                    \assert($definition instanceof ServiceDefinition);
                    $serviceDefinitions[] = $definition;
                }
            } else {
                // Definition is of type:
                // accessor - service definition exists independently
                // imported - runtime-created service, cannot work with
                // unknown
                continue;
            }
        }

        // Filter out duplicates - we cannot distinguish if service from LocatorDefinition
        // is created by accessor or factory so duplicates are possible
        $serviceDefinitions = \array_unique($serviceDefinitions, \SORT_REGULAR);

        return $serviceDefinitions;
    }

    /**
     * @param mixed[]|Statement|string $config
     *
     * @return Definition|string
     */
    public function getDefinitionFromConfig($config, string $preferredPrefix)
    {
        $builder = $this->compiler->getContainerBuilder();

        // Definition is defined in ServicesExtension, try to get it
        if (\is_string($config) && Strings::startsWith($config, '@')) {
            $definitionName = \substr($config, 1);

            // Definition is already loaded (beforeCompile phase), return it
            if ($builder->hasDefinition($definitionName)) {
                return $builder->getDefinition($definitionName);
            }

            // Definition not loaded yet (loadConfiguration phase), return reference string
            return $config;
        }

        // Raw configuration given, create definition from it
        $this->compiler->loadDefinitionsFromConfig([$preferredPrefix => $config]);

        return $builder->getDefinition($preferredPrefix);
    }

    /**
     * Check if config is valid callable or callable syntax which may result in
     * valid callable at runtime and returns an definition otherwise.
     *
     * @param mixed[]|Statement|string $config
     *
     * @return mixed
     */
    public function getCallableFromConfig($config, string $preferredPrefix)
    {
        if (\is_callable($config)) {
            return $config;
        }

        // Might be valid callable at runtime
        if (
            \is_array($config) &&
            \is_callable($config, true) &&
            Strings::startsWith($config[0], '@')
        ) {
            return $config;
        }

        return $this->getDefinitionFromConfig($config, $preferredPrefix);
    }
}
