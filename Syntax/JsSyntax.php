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

namespace Togu\TemplateBundle\Syntax;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class JsSyntax extends AbstractSyntax {
	protected $cacheFile;

	/**
	 * @param string $cacheFile
	 */
	public function __construct($cacheFile) {
		$this->cacheFile = $cacheFile;
		$fs = new Filesystem();
		$fs->mkdir(dirname($cacheFile));
	}

	/**
	 * {@inheritdoc}
	 */
	public function serialize() {
		$methods = array();
		$class = 'App.Template.compiled = {';

		foreach($this->templates as $name => $template) {
			$methods[] = "'$name' : function(c,o) { var me=this,i;o.push('" . $template . "');}";
		}
		$class .= implode($methods, ",");
		$class .= '};';

		file_put_contents($this->cacheFile, $class);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function callMethod($method, $arguments = array()) {
		if(count($arguments) == 0) {
			return "', me.$method(c,i),'";
		}
		return "', me.$method(c,'" . join("','", $arguments) ."', i),'" ;
	}

	/**
	 * {@inheritdoc}
	 */
	public function escapeText($text) {
		return preg_replace("/'/", "\'", $text);
	}


	/**
	 * {@inheritdoc}
	 */
	public function getId() {
		return "',i=me.getId(),'";
	}

	/**
	 * {@inheritdoc}
	 */
	public function _var($variable) {
		return $this->callMethod('_var', array($variable));
	}

	/**
	 * {@inheritdoc}
	 */
	public function _on($eventName, $fire) {
		return $this->callMethod('bind', array($eventName, $fire));
	}

	/**
	 * {@inheritdoc}
	 */
	public function _template($name) {
		$this->currentContext = $name;
		$this->templates[$name] = "";
		return $this->callMethod('dbg', array($name)) . $this->callMethod('_var', array('element'));
	}

}
