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

namespace Togu\TemplateBundle\Compiler;

use DOMException;
use Masterminds\HTML5;
use Togu\TemplateBundle\Syntax\SyntaxInterface;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinderInterface;
use Symfony\Component\Templating\Loader\LoaderInterface;


class Compiler {
	protected $styleRe = '/([\w-]+):(.*?)\{\{\s*(\w+)(?:\|(\w+))?\s*\}\}([^;]*)/';
	protected $classRe = '/\{\{\s*(\w+)(?:\|(\w+))?\s*(\}\})/';
	protected $attrRe = '/^(.*)\{\{\s*(\w+)(?:\|(\w+))?\s*\}\}(.*)$/';

	protected $mustacheRe = '/(.*?)\{\{\s*(\w+)(?:\|(\w+))?\s*(\}\})/';

	protected $specialTags = array(
		'img' => 'image',
		'a' => 'link',
		'input' => 'input'
	);

	protected $selfClosingTags = array(
		'area' => true,
		'base' => true,
		'br' => true,
		'col' => true,
		'command' => true,
		'embed' => true,
		'hr' => true,
		'img' => true,
		'input' => true,
		'link' => true,
		'meta' => true,
		'param' => true,
		'source' => true
	);

	protected $finder;
	protected $loader;

	public function __construct(TemplateFinderInterface $finder, LoaderInterface $loader) {
		$this->finder = $finder;
		$this->loader = $loader;
	}

	/**
	 *
	 * @param SyntaxInterface $syntaxClass
	 */
	public function setSyntaxClass(SyntaxInterface $syntaxClass) {
		$this->syntaxClass = $syntaxClass;
	}

	/**
	 * @return SyntaxInterface
	 */
	public function getSyntaxClass() {
		return $this->syntaxClass;
	}

	public function compileAllTemplates() {
		$syntaxClass = $this->syntaxClass;

		foreach ($this->finder->findAllTemplates() as $template) {
			if ('togu' !== $template->get('engine')) {
				continue;
			}
			$this->compileTemplate($template);
		}

		$syntaxClass->serialize();
	}

	public function compileTemplate ($name) {
		$this->compile($this->parseFragment($name));
	}

	/**
	 * Parses an HTML Togu component
	 *
	 * @param string $name
	 *
	 * @return HTML5 The parsed DOM
	 */
	protected function parseFragment($name) {
		$fileName = $this->loader->findFilename($name);
		$html5 = new HTML5();
		$dom = $html5->loadHTMLFragment(file_get_contents($fileName));

		if($html5->getErrors()) {
			$errors = "";
			foreach($html5->getErrors() as $error) {
				$errors .= "\n". $error;
			}
			throw new \Exception("Parsing error for template " . $name . " Errors: " . $errors);
		}

		return $dom;
	}

	protected function processChildNodes($node) {
		if(! $node->hasChildNodes()) {
			return;
		}
		for( $i=0; $i < $node->childNodes->length; $i++){
			$childNode = $node->childNodes->item($i);
			$this->traverse($childNode);
		}
	}

	protected function compile($dom) {
		$this->traverse($dom);
	}

	protected function getId() {
		return $this->attr('id', $this->getSyntaxClass()->getId('getId'));
	}

	protected function attr($name, $value) {
		return " " . $name . '="' . $value . '"';
	}

	protected function cmfAttribute($node) {
		$node->parentNode->needsId = true;

		$attr = $node->nodeName;
		$value = $node->nodeValue;
		$syntax = $this->getSyntaxClass();

		$splitted = preg_split('/-/', $attr);
		array_shift($splitted);
		$method = array_shift($splitted);
		array_push($splitted, $value);

		return call_user_func_array((array($syntax, "_". $method)), $splitted);
	}

	protected function styleAttribute($node) {
		$attr = $node->nodeName;
		$value = $node->nodeValue;
		$syntax = $this->getSyntaxClass();

		return preg_replace_callback($this->styleRe, function($match) use($node, $syntax){
				$node->parentNode->needsId = true;
				return $match[1] . ":" . $syntax->style($match[1], $match[2], $match[3], $match[4], $match[5]);
			},
			$value);
	}

	protected function classAttribute($node) {
		$attr = $node->nodeName;
		$syntax = $this->getSyntaxClass();

		return preg_replace_callback($this->classRe, function($match) use($node, $syntax){
				$node->parentNode->needsId = true;
				return $syntax->cls($match[1], $match[2]);
			},
			$node->nodeValue);
	}

	protected function processGenericAttribute($node, $nodeName) {
		$attr = $node->nodeName;
		$value = $node->nodeValue;
		$syntax = $this->getSyntaxClass();

		preg_match($this->attrRe, $value, $matches);

		if(count($matches) == 0) {
			return $value;
		}

		$node->parentNode->needsId = true;

		$method = 'attr';
		if(isset($this->specialTags[$nodeName])) {
			$method = $this->specialTags[$nodeName];
		}

		return call_user_func_array(array($syntax,$method), array($attr, $matches[1], $matches[2], $matches[3], $matches[4]));
	}

	protected function processAttributeNode($node, $nodeName) {
		$attr = $node->nodeName;

		if(substr($attr, 0, 4) == "cmf-") {
			return $this->cmfAttribute($node);
		}

		if($attr == "style") {
			return $this->attr('style', $this->styleAttribute($node));
		}

		if($attr == "class") {
			return $this->attr('class', $this->classAttribute($node));
		}

		return $this->attr($attr, $this->processGenericAttribute($node, $nodeName));
	}

	protected function processAttributes($node) {
		$output = "";
		if(! $node->hasAttributes()) {
			return $output;
		}

		$nodeName = $node->nodeName;

		for($i = 0; $i < $node->attributes->length; $i++) {
			$attr = $node->attributes->item($i);
			$attrName = $attr->nodeName;
			$output .= $this->processAttributeNode($attr, $nodeName);

		}

		if(isset($node->needsId) && $node->needsId == true) {
			$output = $this->getId() . $output;
		}

		return $output;
	}

	protected function processDomElementNode($node) {
		$syntax = $this->getSyntaxClass();

		$output = "<" . $node->nodeName;

		$output .= $this->processAttributes($node);

		if(isset($this->selfClosingTags[$node->nodeName]) && $this->selfClosingTags[$node->nodeName] === true) {
			$output .= " />";
			$syntax->write($output);
			return;
		}

		$output .= ">";

		$syntax->openTag($output);

		$this->processChildNodes($node);

		$syntax->closeTag("</" . $node->nodeName . ">");
	}

	protected function replaceText($match) {
		$prefix = $match[1];
		$variable = $match[2];
		$filter = $match[3];

		return $prefix . '<span' . $this->getId() . '>' . $this->getSyntaxClass()->html($variable, $filter) . '</span>';
	}

	protected function processTextNode($node) {
		$syntax = $this->getSyntaxClass();

		$text = preg_replace('/(\n|\s+)/', ' ', $node->wholeText);
		$text = $syntax->escapeText($text);
		$syntax->write(preg_replace_callback($this->mustacheRe, array($this, 'replaceText'), $text));
	}

	protected function processCommentNode($node) {
		$this->getSyntaxClass()->write('<!-- ' . $node->data . ' -->');
	}

	protected function processDocumentFragmentNode($node) {
		if(! $node->hasChildNodes()) {
			throw new DomException('Document Fragment has not child nodes');
		}
		return $this->processChildNodes($node);
	}

	public function traverse($node) {
		switch ($node->nodeType) {
			case 1: //XML_ELEMENT_NODE
				return $this->processDomElementNode($node);
			case 2: //XML_ATTRIBUTE_NODE
				throw new DomException("Attribute found");
			case 3: //XML_TEXT_NODE
				return $this->processTextNode($node);
			case 4:// XML_CDATA_SECTION_NODE
				throw new DomException("CDATA Nodes are not allowed");
			case 5: //XML_ENTITY_REFERENCE_NODE
				throw new DomException("Entity Reference Nodes are not allowed");
			case 6: //XML_ENTITY_NODE
				throw new DomException("Entity Nodes are not allowed");
			case 7: //XML_PROCESSING_INSTRUCTION_NODE
				throw new DomException("Processing Instructions are not allowed");
			case 8: //XML_COMMENT_NODE
				return $this->processCommentNode($node);
			case 9: //XML_DOCUMENT_NODE
				throw new DomException("<html> nodes are not allowed");
			case 10: //XML_DOCUMENT_TYPE_NODE
				throw new DomException("DOCTYPEs are not allowed");
			case 11: //XML_DOCUMENT_FRAGMENT_NODE
				return $this->processDocumentFragmentNode($node);
			case 12: //XML_NOTATION_NODE
				throw new DomException("Notation Nodes are not allowed");
		}
		throw new DomException("Invalid node type " . $node->nodeType);
	}

}