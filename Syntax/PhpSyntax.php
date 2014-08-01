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

class PhpSyntax extends AbstractSyntax implements RendererSyntaxInterface {
	protected $container;
	protected $cacheFile;

	/**
	 * @param ContainerInterface $container
	 * @param string $cacheFile
	 */
	public function __construct(ContainerInterface $container, $cacheFile) {
		$this->container = $container;
		$this->cacheFile = $cacheFile;
	}

	/**
	 * {@inheritdoc}
	 */
	public function serialize() {
		$methods = array();
		$mappings = array();
		$class = '<?php class Togu_Template { use Togu\TemplateBundle\Processor\TemplateProcessor; private $mapping = ';
		$i = 0;

		foreach($this->templates as $name => $template) {
			$mappings[$name] = 'tpl'.$i;
			array_push($methods, 'protected function tpl' . $i++ . '($model) { ?>' . $template. "<?php }");
		}
		$class .= var_export($mappings, true) . ";";
		$class .= implode($methods, "\n");
		$class .= '}';

		file_put_contents($this->cacheFile, $class);
	}

	/**
	 * {@inheritdoc}
	 */
	public function render($data) {
		require_once $this->cacheFile;
		$templateInstance = new \Togu_Template($this->container);
		return $templateInstance->render($data);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function callMethod($method, $arguments = array()) {
		if(count($arguments) == 0) {
			return '<?php echo $this->' . $method . '($model); ?>' ;
		}
		return '<?php echo $this->' . $method . '($model,\'' . join("','", $arguments) ."'); ?>" ;
	}
}