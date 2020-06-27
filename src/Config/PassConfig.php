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

namespace BiuradPHP\DependencyInjection\Config;

use BiuradPHP\DependencyInjection\Compilers\AbstractCompilerPass;
use BiuradPHP\DependencyInjection\Compilers\Compiler;
use BiuradPHP\DependencyInjection\Interfaces\CompilerPassInterface;
use InvalidArgumentException;

/**
 * Compiler Pass Configuration.
 *
 * This class has a default configuration embedded.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PassConfig
{
    public const TYPE_BEFORE_OPTIMIZATION = 'beforeOptimization';

    public const TYPE_OPTIMIZE = 'optimization';

    private $beforeOptimizationPasses = [];

    private $optimizationPasses = [];

    private $compiler;

    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;

        //$this->beforeOptimizationPasses = [
        //    100 => [
        //        new ResolveDefinitionsPass(),
        //    ]
        //];
    }

    /**
     * Returns all passes in order to be processed.
     *
     * @return CompilerPassInterface[]
     */
    public function getPasses()
    {
        return \array_map(
            function (CompilerPassInterface $pass) {
                return $pass instanceof AbstractCompilerPass
                    ? $pass->setCompiler($this->compiler) : $pass;
            },
            \array_merge(
                $this->getBeforeOptimizationPasses(),
                $this->getOptimizationPasses()
            )
        );
    }

    /**
     * Adds a pass.
     *
     * @throws InvalidArgumentException when a pass type doesn't exist
     */
    public function addPass(
        CompilerPassInterface $pass,
        string $type = self::TYPE_BEFORE_OPTIMIZATION,
        int $priority = 0
    ): void {
        $property = $type . 'Passes';

        if (!isset($this->$property)) {
            throw new InvalidArgumentException(\sprintf('Invalid type "%s".', $type));
        }

        $passes = &$this->$property;

        if (!isset($passes[$priority])) {
            $passes[$priority] = [];
        }
        $passes[$priority][] = $pass;
    }

    /**
     * Gets all passes for the BeforeOptimization pass.
     *
     * @return CompilerPassInterface[]
     */
    public function getBeforeOptimizationPasses()
    {
        return $this->sortPasses($this->beforeOptimizationPasses);
    }

    /**
     * Gets all passes for the Optimization pass.
     *
     * @return CompilerPassInterface[]
     */
    public function getOptimizationPasses()
    {
        return $this->sortPasses($this->optimizationPasses);
    }

    /**
     * Sets the BeforeOptimization passes.
     *
     * @param CompilerPassInterface[] $passes
     */
    public function setBeforeOptimizationPasses(array $passes): void
    {
        $this->beforeOptimizationPasses = [$passes];
    }

    /**
     * Sets the Optimization passes.
     *
     * @param CompilerPassInterface[] $passes
     */
    public function setOptimizationPasses(array $passes): void
    {
        $this->optimizationPasses = [$passes];
    }

    /**
     * Sort passes by priority.
     *
     * @param array $passes CompilerPassInterface instances with their priority as key
     *
     * @return CompilerPassInterface[]
     */
    private function sortPasses(array $passes): array
    {
        if (0 === \count($passes)) {
            return [];
        }

        \krsort($passes);

        // Flatten the array
        return \array_merge(...$passes);
    }
}
