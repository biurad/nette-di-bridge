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

namespace BiuradPHP\DependencyInjection\Config\Adapter;

use BiuradPHP\Loader\Adapters\IniAdapter as BiuradPHPIniAdapter;
use Nette;

/**
 * Reading and generating INI files.
 */
final class IniAdapter implements Nette\DI\Config\Adapter
{
    use Nette\SmartObject;

    private $factory;

    public function __construct()
    {
        $this->factory = new BiuradPHPIniAdapter();
    }

    /**
     * Reads configuration from PHP file.
     */
    public function load(string $file): array
    {
        return $this->factory->fromFile($file);
    }

    /**
     * Generates configuration in PHP format.
     */
    public function dump(array $data): string
    {
        return $this->factory->dump($data);
    }
}
