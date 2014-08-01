<?php

/*
 * Copyright (c) 2012-2014 Alessandro Siragusa <alessandro@togu.io>
 *
 * This file is part of the Togu CMS.
 *
 * Togu is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Togu is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Togu.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Togu\TemplateBundle\Loader;

use Symfony\Component\Templating\Loader\FilesystemLoader as Loader;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;


/**
 * FilesystemLoader is a loader that read templates from the filesystem.
 */
class FilesystemLoader extends Loader
{
	protected $locator;
	protected $parser;

	/**
	 * Constructor.
	 *
	 * @param FileLocatorInterface        $locator A FileLocatorInterface instance
	 * @param TemplateNameParserInterface $parser  A TemplateNameParserInterface instance
	 */
	public function __construct(FileLocatorInterface $locator, TemplateNameParserInterface $parser)
	{
		$this->locator = $locator;
		$this->parser = $parser;
		$this->cache = array();
	}

	/**
     * Helper function for getting a template file name.
     *
     * @param string $name
     *
     * @return string Template file name
     */
     public function findFilename($name) {
     	$logicalName = (string) $name;
     	if(isset($this->cache[$logicalName])) {
     		return $this->cache[$logicalName];
     	}

     	try {
     		$template = $this->parser->parse($name);
     		$file = $this->locator->locate($template);
     	} catch (\Exception $e) {
     		throw new \InvalidArgumentException(sprintf('Unable to find template "%s".', $logicalName));
     	}

     	return $this->cache[$logicalName] = $file;
     }


}