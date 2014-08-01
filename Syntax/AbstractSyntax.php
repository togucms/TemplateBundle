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

abstract class AbstractSyntax implements SyntaxInterface {
	protected $templates = array();
	protected $context = array();
	protected $currentContext;
	protected $innerCode = "";

	/**
	 * This method is an helper to write the needed code to call a templating method
	 *
	 * @param string $method
	 * @param array $arguments
	 */
	protected function callMethod($method, $arguments = array()) {}

	/**
	 * {@inheritdoc}
	 */
	public function getId() {
		return $this->callMethod('getId');
	}

	/**
	 * {@inheritdoc}
	 */
	public function openTag($string) {
		array_push($this->context, $this->currentContext);

		$this->write($string);
		$this->write($this->innerCode);
		$this->innerCode = "";
	}

	/**
	 * {@inheritdoc}
	 */
	public function closeTag($string) {
		$this->write($string);
		$this->currentContext = array_pop($this->context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function write($string) {
		$context = end($this->context);
		if(! $context) {
			return;
		}
		$this->templates[$context] .= $string;
	}

	/**
	 * {@inheritdoc}
	 */
	public function escapeText($text) {
		return $text;
	}

	/**
	 * {@inheritdoc}
	 */
	public function style($cssProperty, $prefix, $variable, $filter, $suffix) {
		return $this->callMethod('style', array($cssProperty, $prefix, $variable, $filter, $suffix));
	}

	/**
	 * {@inheritdoc}
	 */
	public function attr($attr, $prefix, $variable, $filter, $suffix) {
		return $this->callMethod('attr', array($attr, $prefix, $variable, $filter, $suffix));
	}

	/**
	 * {@inheritdoc}
	 */
	public function image($attr, $prefix, $variable, $filter, $suffix) {
		return $this->callMethod('image', array($attr, $prefix, $variable, $filter, $suffix));
	}

	/**
	 * {@inheritdoc}
	 */
	public function link($attr, $prefix, $variable, $filter, $suffix) {
		return $this->callMethod('link', array($attr, $prefix, $variable, $filter, $suffix));
	}

	/**
	 * {@inheritdoc}
	 */
	public function input($attr, $prefix, $variable, $filter, $suffix) {
		return $this->callMethod('input', array($attr, $prefix, $variable, $filter, $suffix));
	}

	/**
	 * {@inheritdoc}
	 */
	public function html($variable, $filter) {
		return $this->callMethod('html', array($variable, $filter));
	}

	/**
	 * {@inheritdoc}
	 */
	public function cls($variable, $filter) {
		return $this->callMethod('cls', array($variable, $filter));
	}

	/**
	 * {@inheritdoc}
	 */
	public function _var($variable) {}

	/**
	 * {@inheritdoc}
	 */
	public function _container($variable) {
		$this->innerCode .= $this->callMethod('container', array($variable));
	}

	/**
	 * {@inheritdoc}
	 */
	public function _nextsection() {
		$this->innerCode .= $this->callMethod('nextSection', array());
	}

	/**
	 * {@inheritdoc}
	 */
	public function _template($name) {
		$this->currentContext = $name;
		$this->templates[$name] = "";
	}

	/**
	 * {@inheritdoc}
	 */
	public function _on($eventName, $fire) {}

	/**
	 * {@inheritdoc}
	 */
	public function _partial($name) {
		return $this->_template($this->currentContext . '_' . $name);
	}
}