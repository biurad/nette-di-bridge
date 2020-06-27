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

use ArrayAccess;
use BiuradPHP\DependencyInjection\Exceptions\ContainerResolutionException;
use BiuradPHP\DependencyInjection\Exceptions\MissingServiceException;
use BiuradPHP\DependencyInjection\Exceptions\ParameterNotFoundException;
use BiuradPHP\DependencyInjection\Interfaces\FactoryInterface;
use BiuradPHP\Support\BoundMethod;
use Closure;
use Nette;
use Nette\DI\Container as NetteContainer;
use Nette\Utils\Callback;
use Nette\Utils\Validators;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionType;
use Serializable;

use function BiuradPHP\Support\array_get;

/**
 * The dependency injection container default implementation.
 *
 * Auto-wiring container: declarative singletons, contextual injections, outer delegation and
 * ability to lazy wire.
 *
 * Container does not support setter injections, private properties and etc. Normally it will work
 * with classes only to be as much invisible as possible. Attention, this is hungry implementation
 * of container, meaning it WILL try to resolve dependency unless you specified custom lazy factory.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Container extends NetteContainer implements ArrayAccess, Serializable, FactoryInterface
{
    /** @var object[] service name => instance */
    private $instances = [];

    /** @var array circular reference detector */
    private $creating;

    /** @var array */
    private $methods;

    /**
     * Provide psr container interface in order to proxy get and has requests.
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->parameters = $params;
        $this->methods    = \array_flip(\get_class_methods($this));
    }

    /**
     * Container can not be cloned.
     */
    public function __clone()
    {
        throw new ContainerResolutionException('Container is not clonable');
    }

    public function __serialize(): array
    {
        return [
            'parameters' => $this->parameters,
            'types'      => $this->types,
            'aliases'    => $this->aliases,
            'tags'       => $this->tags,
            'wiring'     => $this->wiring,
            'instances'  => $this->instances,
            'methods'    => $this->methods,
            'creating'   => $this->creating,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->parameters = $data['parameters'];
        $this->types      = $data['types'];
        $this->aliases    = $data['aliases'];
        $this->tags       = $data['tags'];
        $this->wiring     = $data['wiring'];
        $this->instances  = $data['instances'];
        $this->methods    = $data['methods'];

        if (isset($data['creating'])) {
            $this->creating = $data['creating'];
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function hasParameter($name): bool
    {
        return \array_key_exists($name, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function addService(string $name, $service): Container
    {
        $name = $this->aliases[$name] ?? $name;

        // Report exception if name already exists.
        if (isset($this->instances[$name])) {
            throw new ContainerResolutionException("Service [$name] already exists.");
        }

        if (!\is_object($service)) {
            throw new ContainerResolutionException(
                \sprintf("Service '%s' must be a object, %s given.", $name, \gettype($name))
            );
        }

        // Resolving the closure of the service to return it's type hint or class.
        $type = $this->resolveClosure($service);

        // Resolving wiring so we could call the service parent classes and interfaces.
        if (!$service instanceof Closure) {
            $this->resolveWiring($name, $type);
        }

        // Resolving the method calls.
        $this->resolveMethod($name, self::getMethodName($name), $type);

        if ($service instanceof Closure) {
            $this->types[$name] = $type;

            // Get the method binding for the given method.
            $this->methods[self::getMethodName($name)] = $service;
        } else {
            $this->instances[$name] = $service;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeService(string $name): void
    {
        $name = $this->aliases[$name] ?? $name;
        unset($this->instances[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethodBinding($method): bool
    {
        return isset($this->methods[$method]);
    }

    /**
     * {@inheritdoc}
     */
    public function getService(string $name)
    {
        if (!isset($this->instances[$name])) {
            if (isset($this->aliases[$name])) {
                return $this->getService($this->aliases[$name]);
            }
            $this->instances[$name] = $this->createService($name);
        }

        return $this->instances[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getByType(string $type, bool $throw = true)
    {
        $type = Nette\DI\Helpers::normalizeClass($type);

        if (!empty($this->wiring[$type][0])) {
            if (\count($names = $this->wiring[$type][0]) === 1) {
                return $this->getService($names[0]);
            }
            \natsort($names);

            throw new MissingServiceException(
                "Multiple services of type $type found: " . \implode(', ', $names) . '.'
            );
        }

        if ($throw) {
            throw new MissingServiceException("Service of type $type not found.");
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceType(string $name): string
    {
        $method = self::getMethodName($name);

        if (isset($this->aliases[$name])) {
            return $this->getServiceType($this->aliases[$name]);
        }

        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        if ($this->hasMethodBinding($method)) {
            /** @var ReflectionMethod $type */
            $type = $this->parseBindMethod([$this, $method]);

            return $type ? $type->getName() : '';
        }

        throw new MissingServiceException("Service '$name' not found.");
    }

    /**
     * {@inheritdoc}
     */
    public function hasService(string $name): bool
    {
        $name = $this->aliases[$name] ?? $name;

        return $this->hasMethodBinding(self::getMethodName($name)) || isset($this->instances[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function isCreated(string $name): bool
    {
        if (!$this->hasService($name)) {
            throw new MissingServiceException("Service '$name' not found.");
        }
        $name = $this->aliases[$name] ?? $name;

        return isset($this->instances[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function createService(string $name, array $args = [])
    {
        $name   = $this->aliases[$name] ?? $name;
        $method = self::getMethodName($name);
        $cb     = $this->methods[$method] ?? null;

        if (isset($this->creating[$name])) {
            throw new ContainerResolutionException(
                \sprintf(
                    'Circular reference detected for services: %s.',
                    \implode(', ', \array_keys($this->creating))
                )
            );
        }

        if ($cb === null) {
            throw new MissingServiceException("Service '$name' not found.");
        }

        try {
            $this->creating[$name] = true;
            $service               = $cb instanceof Closure ? $this->callMethod($cb, $args) : $this->$method(...$args);
        } finally {
            unset($this->creating[$name]);
        }

        if (!\is_object($service)) {
            throw new Nette\UnexpectedValueException(
                "Unable to create service '$name', value returned by " .
                ($cb instanceof Closure ? 'closure' : "method $method()") . ' is not object.'
            );
        }

        return $service;
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance(string $class, array $args = [])
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new ContainerResolutionException("Targeted class [$class] does not exist.", 0, $e);
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            throw new ContainerResolutionException("Targeted [$class] is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (null === $constructor) {
            return $reflector->newInstance();
        }

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        // this will be handled in a recursive way...
        try {
            $instances = $this->autowireArguments($constructor, $args);
        } catch (MissingServiceException $e) {
            // Resolve default pararamters on class, if paramter was not given and
            // default paramter exists, why not let's use it.
            foreach ($constructor->getParameters() as $position => $parameter) {
                try {
                    if (!(isset($args[$position]) || isset($args[$parameter->name]))) {
                        $args[$position] = Nette\Utils\Reflection::getParameterDefaultValue($parameter);
                    }
                } catch (\ReflectionException $e) {
                    continue;
                }
            }

            return $this->createInstance($class, $args);
        }

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, $concrete = null): Container
    {
        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if (!$concrete instanceof Closure || (\is_string($concrete) || \is_object($concrete))) {
            if ((\is_string($concrete) && \class_exists($concrete))) {
                $concrete = $this->createInstance($concrete);
            }

            return $this->addService($abstract, $concrete);
        }

        $this->addService($abstract, function () use ($concrete) {
            return $this->callMethod($concrete);
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function call($callback, array $parameters = [])
    {
        return BoundMethod::call($this, $callback, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function runScope(array $bindings, callable $scope)
    {
        $cleanup = $previous = [];

        foreach ($bindings as $alias => $resolver) {
            if ($this->has($alias)) {
                $previous[$alias] = $this->get($alias);
                continue;
            }
            
            $cleanup[] = $alias;
            $this->bind($alias, $resolver);
        }

        try {
            return $scope(...[&$this]);
        } finally {
            foreach (\array_reverse($previous) as $alias => $resolver) {
                $this->instances[$alias] = $resolver;
            }

            foreach ($cleanup as $alias) {
                $this->removeService($alias);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function make(string $alias, ...$parameters)
    {
        try {
            return $this->getService($alias);
        } catch (MissingServiceException $e) {
            // Allow passing arrays or individual lists of dependencies
            if (isset($parameters[0]) && \is_array($parameters[0]) && \count($parameters) === 1) {
                $parameters = \array_shift($parameters);
            }

            //No direct instructions how to construct class, make is automatically
            return $this->autowire($alias, $parameters);
        }

        return null;
    }

    /**
     * @internal
     */
    final public function serialize(): string
    {
        return \serialize($this->__serialize());
    }

    /**
     * @internal
     */
    final public function unserialize($serialized): void
    {
        $this->__unserialize(\unserialize($serialized));
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->hasService($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function get($id)
    {
        return $this->make($id);
    }

    /**
     * Determine if a given offset exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param string $key
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Container
     */
    public function offsetSet($key, $value)
    {
        return $this->bind($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param string $key
     */
    public function offsetUnset($key): void
    {
        unset($this->instances[$key]);
    }

    /**
     * Automatically create class and register instance in container,
     * might perform methods like auto-singletons, log populations
     * and etc. Can be extended.
     *
     * @param string $class
     * @param array  $parameters
     *
     * @return object
     */
    protected function autowire(string $class, array $parameters)
    {
        try {
            $refClass = new ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new ContainerResolutionException("Undefined class or binding [$class] for autowiring");
        }

        // Resolving class not found in binding or instances.
        try {
            //Automatically create instanc
            if (Validators::isType($refClass->getName())) {
                return $this->getByType($refClass->getName());
            }
        } catch (MissingServiceException $e) {
            $instance = $this->createInstance($refClass->getName(), $parameters);
        }

        //Your code can go here (for example LoggerAwareInterface, custom hydration and etc)
        $this->callInjects($instance); // Call injectors on the new class instance.

        return $instance;
    }

    /**
     * Get the method to be bounded.
     *
     * @param array|string $method
     *
     * @return null|ReflectionType
     */
    private function parseBindMethod($method): ?ReflectionType
    {
        return Callback::toReflection($method)->getReturnType();
    }

    /**
     * Get the Closure or class to be used when building a type.
     *
     * @param mixed $abstract
     *
     * @return string
     */
    private function resolveClosure($abstract): string
    {
        if ($abstract instanceof Closure) {
            /** @var ReflectionFunction $tmp */
            if ($tmp = $this->parseBindMethod($abstract)) {
                return $tmp->getName();
            }

            return '';
        }

        return \get_class($abstract);
    }

    /**
     * Resolve callable methods.
     *
     * @param string $abstract
     * @param string $concrete
     * @param string $type
     *
     * @return null|string
     */
    private function resolveMethod(string $abstract, string $concrete, string $type): ?string
    {
        if (!$this->hasMethodBinding($concrete)) {
            return $this->types[$abstract] = $type;
        }

        if (($expectedType = $this->getServiceType($abstract)) && !\is_a($type, $expectedType, true)) {
            throw new ContainerResolutionException(
                "Service '$abstract' must be instance of $expectedType, " .
                ($type ? "$type given." : 'add typehint to closure.')
            );
        }

        return null;
    }

    /**
     * Resolve wiring classes + interfaces.
     *
     * @param string $name
     * @param mixed  $class
     */
    private function resolveWiring(string $name, $class): void
    {
        $all = [];

        foreach (\class_parents($class) + \class_implements($class) + [$class] as $class) {
            $all[$class][] = $name;
        }

        foreach ($all as $class => $names) {
            $this->wiring[$class] = \array_filter([
                \array_diff($names, $this->findByType($class) ?? [], $this->findByTag($class) ?? []),
            ]);
        }
    }

    private function autowireArguments(ReflectionFunctionAbstract $function, array $args = []): array
    {
        return Nette\DI\Resolver::autowireArguments($function, $args, function (string $type, bool $single) {
            return $single
                ? $this->getByType($type)
                : \array_map([$this, 'get'], $this->findAutowired($type));
        });
    }
}
