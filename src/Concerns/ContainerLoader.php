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

use Nette\DI\ContainerLoader as NetteContainerLoader;

/**
 * DI container loader.
 *
 * @author David Grudl <https://davidgrudl.com>
 * @license BSD-3-Clause
 */
class ContainerLoader extends NetteContainerLoader
{
    public function __construct(string $tempDirectory, bool $autoRebuild = false)
    {
        parent::__construct($tempDirectory, $autoRebuild);
    }

    /**
     * @param string   $class
     * @param callable $generator
     *
     * @return array of (code, file[])
     */
    protected function generate(string $class, callable $generator): array
    {
        $compiler = new Compiler();
        $compiler->setClassName($class);
        $code = $generator(...[&$compiler]) ?: $compiler->compile();

        return [
            "<?php\n$code",
            \serialize($compiler->exportDependencies()),
        ];
    }
}
