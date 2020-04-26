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

namespace BiuradPHP\DependencyInjection\Config;

use Nette;
use Nette\DI\Config\Adapter as AdapterInterface;
use Nette\DI\Config\Adapters;
use Nette\DI\Config\Loader as ConfigLoader;
use Nette\Utils\Validators;

class Loader extends ConfigLoader
{
    private const INCLUDES_KEY = 'includes';
    public const ENV_REGEX = '/%env\([a-zA-Z0-9\|\-:_]+\)%/';

	private $adapters = [
		'php'   => Adapters\PhpAdapter::class,
		'neon'  => Adapter\NeonAdapter::class,
		'yaml'  => Adapter\NeonAdapter::class,
        'yml'   => Adapter\NeonAdapter::class,
        'json'  => Adapter\NeonAdapter::class,
	];

	private $dependencies = [];

	private $loadedFiles = [];

	private $parameters = [];


	/**
	 * Reads configuration from file.
	 */
	public function load(string $file, ?bool $merge = true): array
	{
		if (!is_file($file) || !is_readable($file)) {
			throw new Nette\FileNotFoundException("File '$file' is missing or is not readable.");
		}

		if (isset($this->loadedFiles[$file])) {
			throw new Nette\InvalidStateException("Recursive included file '$file'");
		}
		$this->loadedFiles[$file] = true;

		$this->dependencies[] = $file;
		$data = $this->getAdapter($file)->load($file);

		$res = [];
		if (isset($data[self::INCLUDES_KEY])) {
			Validators::assert($data[self::INCLUDES_KEY], 'list', "section 'includes' in file '$file'");
			$includes = Nette\DI\Helpers::expand($data[self::INCLUDES_KEY], $this->parameters);
			foreach ($includes as $include) {
				$include = $this->expandIncludedFile($include, $file);
				$res = Nette\Schema\Helpers::merge($this->load($include, $merge), $res);
			}
		}
		unset($data[self::INCLUDES_KEY], $this->loadedFiles[$file]);

		if ($merge === false) {
			$res[] = $data;
		} else {
			$res = Nette\Schema\Helpers::merge($data, $res);
		}
		return $res;
	}


	/**
	 * Save configuration to file.
	 */
	public function save(array $data, string $file): void
	{
		if (file_put_contents($file, $this->getAdapter($file)->dump($data)) === false) {
			throw new Nette\IOException("Cannot write file '$file'.");
		}
	}


	/**
	 * Returns configuration files.
	 */
	public function getDependencies(): array
	{
		return array_unique($this->dependencies);
	}


	/**
	 * Registers adapter for given file extension.
	 * @param  string|Adapter  $adapter
	 * @return static
	 */
	public function addAdapter(string $extension, $adapter)
	{
		$this->adapters[strtolower($extension)] = $adapter;
		return $this;
	}


	private function getAdapter(string $file): AdapterInterface
	{
		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (!isset($this->adapters[$extension])) {
			throw new Nette\InvalidArgumentException("Unknown file extension '$file'.");
        }

		return is_object($this->adapters[$extension]) ? $this->adapters[$extension] : new $this->adapters[$extension];
	}


	/** @return static */
	public function setParameters(array $params)
	{
        $this->parameters = $params;

		return $this;
	}
}
