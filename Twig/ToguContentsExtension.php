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

use Togu\TemplateBundle\ToguEngine;

class ToguContentsExtension extends \Twig_Extension {

	/**
	 *
	 * @param ToguEngine $engine
	 */
	public function __construct(ToguEngine $engine) {
		$this->engine = $engine;
	}

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'togu';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('toguContents', array($this, 'renderContents'), array('is_safe' => array('all'))),
        );
    }

    /**
     * Renders Togu contents
     *
     * @param unknown $contents
     * @return string
     */
    public function renderContents($contents) {
        return $this->engine->render($contents);
    }

}