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

use BiuradPHP\DependencyInjection\Container;
use BiuradPHP\DependencyInjection\Definitions\InterfaceDefinition;
use BiuradPHP\DependencyInjection\Exceptions\MissingServiceException;
use BiuradPHP\DependencyInjection\Exceptions\NotAllowedDuringResolvingException;
use BiuradPHP\DependencyInjection\Exceptions\ParameterNotFoundException;
use Nette;
use Nette\DI\Autowiring;
use Nette\DI\ContainerBuilder as NetteContainerBuilder;
use Nette\DI\Definitions;
use Nette\DI\Definitions\Definition;
use Nette\DI\Resolver;
use ReflectionClass;
use ReflectionFunctionAbstract;

use function BiuradPHP\Support\array_get;

/**
 * Container builder.
 *
 * @author David Grudl <https://davidgrudl.com>
 * @license BSD-3-Clause
 */
class ContainerBuilder extends NetteContainerBuilder
{
    use Nette\SmartObject;

    public const
        THIS_SERVICE   = 'self',
        THIS_CONTAINER = 'container';

    /** @var Definition[] */
    private $definitions = [];

    /** @var array of alias => service */
    private $aliases = [];

    /** @var Autowiring */
    private $autowiring;

    /** @var bool */
    private $needsResolve = true;

    /** @var bool */
    private $resolving = false;

    /** @var array */
    private $dependencies = [];

    public function __construct()
    {
        $this->autowiring = new Autowiring($this);
        $this->addImportedDefinition(self::THIS_CONTAINER)->setType(Container::class);
    }

    /**
     * Adds new service definition.
     *
     * @return Definitions\ServiceDefinition
     */
    public function addDefinition(?string $name, Definition $definition = null): Definition
    {
        $this->needsResolve = true;

        if ($name === null) {
            for (
                $i = 1;
                isset($this->definitions['0' . $i]) ||
                isset($this->aliases['0' . $i]);
                $i++
            );
            $name = '0' . $i; // prevents converting to integer in array key
        } elseif (\is_int(\key([$name => 1])) || !\preg_match('#^\w+(\.\w+)*$#D', $name)) {
            throw new Nette\InvalidArgumentException(
                \sprintf(
                    'Service name must be a alpha-numeric string and not a number, %s given.',
                    \gettype($name)
                )
            );
        } else {
            $name = $this->aliases[$name] ?? $name;

            if (isset($this->definitions[$name])) {
                throw new Nette\InvalidStateException("Service '$name' has already been added.");
            }
            $lname = \strtolower($name);

            foreach ($this->definitions as $nm => $foo) {
                if ($lname === \strtolower($nm)) {
                    throw new Nette\InvalidStateException(
                        "Service '$name' has the same name as '$nm' in a case-insensitive manner."
                    );
                }
            }
        }

        $definition = $definition ?: new Definitions\ServiceDefinition();
        $definition->setName($name);
        $definition->setNotifier(function (): void {
            $this->needsResolve = true;
        });

        return $this->definitions[$name] = $definition;
    }

    /**
     * Adds the service definitions.
     *
     * @param Definition[] $definitions An array of service definitions
     */
    public function addDefinitions(array $definitions): void
    {
        foreach ($definitions as $id => $definition) {
            $this->addDefinition($id, $definition);
        }
    }

    /**
     * Registers a service definition.
     *
     * This methods allows for simple registration of service definition
     * with a fluid interface.
     *
     * @param array|Definition|Definitions\Reference|Definitions\Statement|string $class
     *
     * @return Definitions\ServiceDefinition A Definition instance
     */
    public function register(string $id, $class = null, ?Definition $definition = null): Definition
    {
        return $this->addDefinition($id, $definition)->setFactory($class);
    }

    /**
     * Registers an autowired service definition.
     *
     * This method implements a shortcut for using addDefinition() with
     * an autowired definition.
     *
     * @return Definitions\ServiceDefinition The created definition
     */
    public function autowire(string $id, string $class = null)
    {
        return $this->register($id, $class)->setAutowired(true);
    }

    public function addInterfaceDefinition(string $name): InterfaceDefinition
    {
        return $this->addDefinition($name, new InterfaceDefinition());
    }

    /**
     * Computes a reasonably unique hash of a value.
     *
     * @param mixed $value A serializable value
     *
     * @return string
     */
    public static function hash($value)
    {
        $hash = \substr(\base64_encode(\hash('sha256', \serialize($value), true)), 0, 7);

        return \str_replace(['/', '+'], ['.', '_'], $hash);
    }

    /**
     * Removes the specified service definition.
     */
    public function removeDefinition(string $name): void
    {
        $this->needsResolve = true;
        $name               = $this->aliases[$name] ?? $name;
        unset($this->definitions[$name]);
    }

    /**
     * Gets the service definition.
     */
    public function getDefinition(string $name): Definition
    {
        $service = $this->aliases[$name] ?? $name;

        if (!isset($this->definitions[$service])) {
            throw new MissingServiceException("Service '$name' not found.");
        }

        return $this->definitions[$service];
    }

    /**
     * Gets all service definitions.
     *
     * @return Definition[]
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * Does the service definition or alias exist?
     */
    public function hasDefinition(string $name): bool
    {
        $name = $this->aliases[$name] ?? $name;

        return isset($this->definitions[$name]);
    }

    public function addAlias(string $alias, string $service): void
    {
        if (!$alias) { // builder is not ready for falsy names such as '0'
            throw new Nette\InvalidArgumentException(
                \sprintf('Alias name must be a non-empty string, %s given.', \gettype($alias))
            );
        }

        if (!$service) { // builder is not ready for falsy names such as '0'
            throw new Nette\InvalidArgumentException(
                \sprintf('Service name must be a non-empty string, %s given.', \gettype($service))
            );
        }

        if (isset($this->aliases[$alias])) {
            throw new Nette\InvalidStateException("Alias '$alias' has already been added.");
        }

        if (isset($this->definitions[$alias])) {
            throw new Nette\InvalidStateException("Service '$alias' has already been added.");
        }
        $this->aliases[$alias] = $service;
    }

    /**
     * Adds the service aliases.
     */
    public function addAliases(array $aliases): void
    {
        foreach ($aliases as $alias => $id) {
            $this->addAlias($alias, $id);
        }
    }

    /**
     * Removes the specified alias.
     */
    public function removeAlias(string $alias): void
    {
        unset($this->aliases[$alias]);
    }

    /**
     * Gets all service aliases.
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param string[] $types
     *
     * @return static
     */
    public function addExcludedClasses(array $types)
    {
        $this->needsResolve = true;
        $this->autowiring->addExcludedClasses($types);

        return $this;
    }

    /**
     * Gets a parameter.
     *
     * @param string $name The parameter name
     *
     * @throws InvalidArgumentException if the parameter is not defined
     *
     * @return mixed The parameter value
     */
    public function getParameter(string $name)
    {
        if (!\array_key_exists($name, $this->parameters)) {
            if (!$name) {
                throw new ParameterNotFoundException($name);
            }

            $alternatives = [];

            foreach ($this->parameters as $key => $parameterValue) {
                $lev = \levenshtein($name, $key);

                if ($lev <= \strlen($name) / 3 || false !== \strpos($key, $name)) {
                    $alternatives[] = $key;
                }
            }

            $nonNestedAlternative = null;

            if (!\count($alternatives) && false !== \strpos($name, '.')) {
                $namePartsLength = \explode('.', $name);
                $key             = \array_shift($namePartsLength);

                //return $this->parameters . $namePartsLength;
                while (\count($namePartsLength)) {
                    if ($this->hasParameter($key)) {
                        if (!\is_array($this->getParameter($key))) {
                            $nonNestedAlternative = $key;

                            throw new ParameterNotFoundException(
                                $name,
                                null,
                                null,
                                null,
                                $alternatives,
                                $nonNestedAlternative
                            );
                        }

                        return array_get($this->parameters, $name);
                    }
                }
            }
        }

        return $this->parameters[$name];
    }

    /**
     * Checks if a parameter exists.
     *
     * @internal should not be used to check parameters existence
     *
     * @param string $name The parameter name
     *
     * @return bool The presence of parameter in container
     */
    public function hasParameter(string $name)
    {
        return \array_key_exists($name, $this->parameters);
    }

    /**
     * Adds parameters to the service container parameters.
     *
     * @param string $name  The parameter name
     * @param mixed  $value The parameter value
     */
    public function setParameter(string $name, $value): void
    {
        if (\strpos($name, '.') !== false) {
            $parameters = &$this->parameters;
            $keys       = \explode('.', $name);

            while (\count($keys) > 1) {
                $key = \array_shift($keys);

                if (!isset($parameters[$key]) || !\is_array($parameters[$key])) {
                    $parameters[$key] = [];
                }

                $parameters = &$parameters[$key];
            }

            $parameters[\array_shift($keys)] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * Removes a parameter.
     *
     * @param string $name The parameter name
     */
    public function removeParameter(string $name): void
    {
        if ($this->hasParameter($name)) {
            unset($this->parameters[$name]);
        } elseif (\strpos($name, '.') !== false) {
            $parts = \explode('.', $name);
            $array = &$this->parameters;

            while (\count($parts) > 1) {
                $part = \array_shift($parts);

                if (isset($array[$part]) && \is_array($array[$part])) {
                    $array = &$array[$part];
                }
            }

            unset($array[\array_shift($parts)]);
        }
    }

    /**
     * Resolves autowired service name by type.
     *
     * @param bool $throw exception if service doesn't exist?
     *
     * @throws MissingServiceException
     */
    public function getByType(string $type, bool $throw = false): ?string
    {
        $this->needResolved();

        return $this->autowiring->getByType($type, $throw);
    }

    /**
     * Gets autowired service definition of the specified type.
     *
     * @throws MissingServiceException
     */
    public function getDefinitionByType(string $type): Definition
    {
        return $this->getDefinition($this->getByType($type, true));
    }

    /**
     * Gets the autowired service names and definitions of the specified type.
     *
     * @return Definition[] service name is key
     *
     * @internal
     */
    public function findAutowired(string $type): array
    {
        $this->needResolved();

        return $this->autowiring->findByType($type);
    }

    /**
     * Gets the service names and definitions of the specified type.
     *
     * @return Definition[] service name is key
     */
    public function findByType(string $type): array
    {
        $this->needResolved();
        $found = [];

        foreach ($this->definitions as $name => $def) {
            if (\is_a($def->getType(), $type, true)) {
                $found[$name] = $def;
            }
        }

        return $found;
    }

    /**
     * Gets the service names and tag values.
     *
     * @return array of [service name => tag attributes]
     */
    public function findByTag(string $tag): array
    {
        $found = [];

        foreach ($this->definitions as $name => $def) {
            if (($tmp = $def->getTag($tag)) !== null) {
                $found[$name] = $tmp;
            }
        }

        return $found;
    }

    /********************* building ****************d*g**/

    /**
     * Checks services, resolves types and rebuilts autowiring classlist.
     */
    public function resolve(): void
    {
        if ($this->resolving) {
            return;
        }
        $this->resolving = true;

        $resolver = new Nette\DI\Resolver($this);

        foreach ($this->definitions as $def) {
            $resolver->resolveDefinition($def);
        }

        $this->autowiring->rebuild();

        $this->resolving = $this->needsResolve = false;
    }

    public function complete(): void
    {
        $this->resolve();

        foreach ($this->definitions as $def) {
            $def->setNotifier(null);
        }

        $resolver = new Nette\DI\Resolver($this);

        foreach ($this->definitions as $def) {
            $resolver->completeDefinition($def);
        }
    }

    /**
     * Adds item to the list of dependencies.
     *
     * @param ReflectionClass|ReflectionFunctionAbstract|string $dep
     *
     * @return static
     *
     * @internal
     */
    public function addDependency($dep)
    {
        $this->dependencies[] = $dep;

        return $this;
    }

    /**
     * Returns the list of dependencies.
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /** @internal */
    public function exportMeta(): array
    {
        $defs = $this->definitions;
        \ksort($defs);

        foreach ($defs as $name => $def) {
            if ($def instanceof Definitions\ImportedDefinition) {
                $meta['types'][$name] = $def->getType();
            }

            foreach ($def->getTags() as $tag => $value) {
                $meta['tags'][$tag][$name] = $value;
            }
        }

        $meta['aliases'] = $this->aliases;
        \ksort($meta['aliases']);

        $all = [];

        foreach ($this->definitions as $name => $def) {
            if ($type = $def->getType()) {
                foreach (\class_parents($type) + \class_implements($type) + [$type] as $class) {
                    $all[$class][] = $name;
                }
            }
        }

        [$low, $high] = $this->autowiring->getClassList();

        foreach ($all as $class => $names) {
            $meta['wiring'][$class] = \array_filter([
                $high[$class] ?? [],
                $low[$class] ?? [],
                \array_diff($names, $low[$class] ?? [], $high[$class] ?? []),
            ]);
        }

        return $meta;
    }

    /**
     * Format ServiceDefinition's statement.
     *
     * @param string $statement
     * @param array  $args
     *
     * @return string
     */
    public function formatPhp(string $statement, array $args): string
    {
        \array_walk_recursive($args, function (&$val): void {
            if ($val instanceof Definitions\Statement) {
                $val = (new Resolver($this))->completeStatement($val);
            } elseif ($val instanceof Definition) {
                $val = new Definitions\Reference($val->getName());
            }
        });

        return (new PhpGenerator($this))->formatPhp($statement, $args);
    }

    private function needResolved(): void
    {
        if ($this->resolving) {
            throw new NotAllowedDuringResolvingException();
        }

        if ($this->needsResolve) {
            $this->resolve();
        }
    }
}
