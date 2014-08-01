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

namespace Togu\TemplateBundle\Twig;

use Symfony\Component\Yaml\Yaml;

class SourceFilesExtension extends \Twig_Extension {
	protected $basePath;
	protected $cache = array();
	protected $environment;

	/**
	 *
	 * @param string $basePath
	 * @param string $environment
	 */
	public function __construct($basePath, $environment) {
		$this->basePath = $basePath;
		$this->environment = $environment;
	}

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'sourceFiles';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('jsFiles', array($this, 'jsFiles'), array('is_safe' => array('all'))),
            new \Twig_SimpleFunction('cssFiles', array($this, 'cssFiles'), array('is_safe' => array('all'))),
        );
    }

    protected function parseFile($filename) {
    	if(! isset($this->cache[$filename])) {
    		$this->cache[$filename] = Yaml::parse(file_get_contents($this->basePath . $filename));
			if($this->environment == "dev") {
				foreach ($this->cache[$filename] as $idx => $file) {
					$this->cache[$filename][$idx] = $this->cache[$filename][$idx] . '?v=' . microtime();
				}
			}
    	}
    	return $this->cache[$filename];
    }

    /**
     * Sends to the template the contents of the file app/{env}/js.json
     *
     * @return string
     */
    public function jsFiles() {
        return $this->parseFile('/js.json');
    }

    /**
     * Sends to the template the contents of the file app/{env}/css.json
     *
     * @return string
     */
    public function cssFiles() {
    	return $this->parseFile('/css.json');
    }

}