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

use BiuradPHP\DependencyInjection\Container;
use BiuradPHP\DependencyInjection\Exceptions\ContainerResolutionException;
use BiuradPHP\DependencyInjection\Exceptions\MissingServiceException;
use BiuradPHP\DependencyInjection\Exceptions\ParameterNotFoundException;
use Psr\Container\ContainerInterface;
use Throwable;

interface FactoryInterface extends ContainerInterface
{
    /**
     * Get the constructor parameters.
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Gets a parameter.
     *
     * @param string $name The parameter name
     *
     * @throws ParameterNotFoundException if the parameter is not defined
     *
     * @return mixed The parameter value
     */
    public function getParameter(string $name);

    /**
     * Checks if a parameter exists.
     *
     * @param string $name The parameter name
     *
     * @return bool The presence of parameter in container
     */
    public function hasParameter($name);

    /**
     * Removes the service from the container.
     */
    public function removeService(string $name): void;

    /**
     * Adds the service to the container.
     *
     * @param object $service service or its factory
     *
     * @return static
     */
    public function addService(string $name, $service);

    /**
     * Gets the service type by name.
     *
     * @throws MissingServiceException
     */
    public function getServiceType(string $name): string;

    /**
     * Is the service created?
     */
    public function isCreated(string $name): bool;

    /**
     * Creates new instance of the service.
     *
     * @throws MissingServiceException
     *
     * @return object
     */
    public function createService(string $name, array $args = []);

    /**
     * Resolves service by type.
     *
     * @param bool $throw exception if service doesn't exist?
     *
     * @throws MissingServiceException
     *
     * @return null|object service
     */
    public function getByType(string $type, bool $throw = true);

    /**
     * Gets the autowired service names of the specified type.
     *
     * @return string[]
     *
     * @internal
     */
    public function findAutowired(string $type): array;

    /**
     * Gets the service names of the specified type.
     *
     * @return string[]
     */
    public function findByType(string $type): array;

    /**
     * Gets the service names of the specified tag.
     *
     * @return array of [service name => tag attributes]
     */
    public function findByTag(string $tag): array;

    /**
     * Creates new instance using autowiring.
     *
     * @throws ContainerResolutionException
     *
     * @return object
     */
    public function createInstance(string $class, array $args = []);

    /**
     * Calls all methods starting with with "inject" using autowiring.
     *
     * @param object $service
     */
    public function callInjects($service): void;

    /**
     * Calls method using autowiring.
     *
     * @return mixed
     */
    public function callMethod(callable $function, array $args = []);

    /**
     * Register a binding with the container.
     *
     * Bind value resolver to container alias. Resolver can be class name (will be constructed
     * for each method call), function array or Closure (executed every call). Only object resolvers
     * supported by this method.
     *
     * @param string              $abstract
     * @param null|Closure|string $concrete
     */
    public function bind(string $abstract, $concrete = null): Container;

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param null|string     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null);

    /**
     * Create instance of requested class using binding class aliases and set of parameters provided
     * by user, rest of constructor parameters must be filled by container. Method might return
     * pre-constructed singleton!
     *
     * @param string $alias
     * @param array  $parameters parameters to construct new class
     *
     * @return null|mixed|object
     */
    public function make(string $alias, ...$parameters);

    /**
     * Invokes given closure or function withing specific container scope.
     * By default, container is passed into callback arguments
     *
     * Example:
     * ```php
     * $container->runScope(['actor' => new Actor()], function ($container) {
     *    return $container->get('actor');
     * });
     * ```
     *
     * This makes the service private and canmot be use elsewhere in codebase.
     *
     * @param array    $bindings
     * @param callable $scope
     *
     * @throws Throwable
     *
     * @return mixed
     */
    public function runScope(array $bindings, callable $scope);
}
