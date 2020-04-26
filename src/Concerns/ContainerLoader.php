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
	/** @var bool */
	private $autoRebuild;

	/** @var string */
	private $tempDirectory;


	public function __construct(string $tempDirectory, bool $autoRebuild = false)
	{
        $this->autoRebuild = $autoRebuild;
        $this->tempDirectory = $tempDirectory;

		parent::__construct($tempDirectory, $autoRebuild);
	}

    /**
     * @param string $class
     * @param callable $generator
     * @return array of (code, file[])
     */
	protected function generate(string $class, callable $generator): array
	{
		$compiler = new Compiler;
		$compiler->setClassName($class);
        $code = $generator(...[&$compiler]) ?: $compiler->compile();

		return [
			"<?php\n$code",
			serialize($compiler->exportDependencies()),
		];
	}
}
