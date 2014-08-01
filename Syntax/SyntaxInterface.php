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

interface SyntaxInterface {

	/**
	 * Serializes the template in a file
	 */
	public function serialize();

	/**
	 * Returns an unique id for the HTML element
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * This method is called when a new tag is found
	 *
	 * @param string $string The tag name
	 */
	public function openTag($string);

	/**
	 * This method is called when a tag is closed
	 *
	 * @param string $string The tag name
	 */
	public function closeTag($string);

	/**
	 * This method writes raw text into the template
	 *
	 * @param string $string The text to be written
	 */
	public function write($string);

	/**
	 * This method escapes the given text
	 *
	 * @param string $text
	 * @return string
	 */
	public function escapeText($text);

	/**
	 * This method is called when a variable has been found inside a style attribute
	 *
	 * @param string $cssProperty
	 * @param string $prefix
	 * @param string $variable
	 * @param string $filter
	 * @param string $suffix
	 */
	public function style($cssProperty, $prefix, $variable, $filter, $suffix);

	/**
	 * This method is called when a variable has been found inside an attribute
	 *
	 * @param string $attr
	 * @param string $prefix
	 * @param string $variable
	 * @param string $filter
	 * @param string $suffix
	 */
	public function attr($attr, $prefix, $variable, $filter, $suffix);

	/**
	 * This method is called when a variable has been found inside an attribute of an 'img' tag
	 *
	 * @param string $attr
	 * @param string $prefix
	 * @param string $variable
	 * @param string $filter
	 * @param string $suffix
	 */
	public function image($attr, $prefix, $variable, $filter, $suffix);

	/**
	 * This method is called when a variable has been found inside an attribute of an 'a' tag
	 *
	 * @param string $attr
	 * @param string $prefix
	 * @param string $variable
	 * @param string $filter
	 * @param string $suffix
	 */
	public function link($attr, $prefix, $variable, $filter, $suffix);

	/**
	 * This method is called when a variable has been found inside an attribute of an 'input' tag
	 *
	 * @param string $attr
	 * @param string $prefix
	 * @param string $variable
	 * @param string $filter
	 * @param string $suffix
	 */
	public function input($attr, $prefix, $variable, $filter, $suffix);

	/**
	 * This method is called when a variable has been found inside a class attribute
	 *
	 * @param string $variable
	 * @param string $filter
	 */
	public function cls($variable, $filter);

	/**
	 * This method is called when a cmf-var attribute has been found
	 *
	 * @param string $variable
	 */
	public function _var($variable);

	/**
	 * This method is called when a cmf-container attribute has been found
	 *
	 * @param string $variable
	 */
	public function _container($variable);

	/**
	 * This method is called when a cmf-nextsection attribute has been found
	 */
	public function _nextsection();

	/**
	 * This method is called when a cmf-template attribute has been found
	 *
	 * @param string $name
	 */
	public function _template($name);

	/**
	 * This method is called when a cmf-on-$event attribute has been found
	 *
	 * @param string $eventName The name of the native event
	 * @param string $fire The event to be fired
	 */
	public function _on($eventName, $fire);

	/**
	 * This method is called when a cmf-partial attribute has been found
	 * @param string $name
	 */
	public function _partial($name);
}