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

namespace Togu\TemplateBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Togu\TemplateBundle\Syntax\RendererSyntaxInterface;
use Togu\TemplateBundle\Compiler\Compiler;

class ToguEngine {
	protected $container;
	protected $compiler;
	protected $compiled = false;
	protected $rendererSyntax;

	protected $syntaxes;

	/**
	 * Constructor
	 *
	 * @param ContainerInterface $container
	 * @param TemplateFinderInterface $finder
	 * @param LoaderInterface $loader
	 */
	public function __construct(ContainerInterface $container, Compiler $compiler, RendererSyntaxInterface $rendererSyntax) {
		$this->container = $container;
		$this->compiler = $compiler;
		$this->rendererSyntax = $rendererSyntax;

		$this->syntaxes = array();
	}

	public function addSyntax($syntax) {
		$this->syntaxes[] = $syntax;
	}

	public function getSyntaxes() {
		return $this->syntaxes;
	}

	public function render($data) {
		if('dev' == $this->container->get('kernel')->getEnvironment()) {
			$this->compileAllTemplates();
		}
		return $this->rendererSyntax->render($data);
	}

	public function compileAllTemplates() {
		if($this->compiled === true) {
			return;
		}

		foreach ($this->getSyntaxes() as $syntaxClass) {
			$this->compiler->setSyntaxClass($syntaxClass);
			$this->compiler->compileAllTemplates();
		}

		$this->compiled = true;
	}

}