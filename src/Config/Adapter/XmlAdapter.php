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

namespace BiuradPHP\DependencyInjection\Config\Adapter;

use BiuradPHP\Loader\Files\Adapters\XmlFileAdapter as BiuradPHPXmlAdapter;
use Nette;

/**
 * Reading and generating Xml files.
 */
final class XmlAdapter implements Nette\DI\Config\Adapter
{
    use Nette\SmartObject;

    private $factory;

    public function __construct()
    {
        $this->factory = new BiuradPHPXmlAdapter();
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
