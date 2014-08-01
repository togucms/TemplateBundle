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

namespace Togu\TemplateBundle\Processor;

use Togu\TemplateBundle\Component\ComponentInterface;
use Application\Togu\ApplicationModelsBundle\Document\App\RootModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sonata\MediaBundle\Model\MediaManagerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait TemplateProcessor {
	private $sections;
	private $ids;

	private $container;
	private $componentConfig;
	private $metadataProcessor;
	private $currentComponentConfig;
	private $urlGenerator;
	private $mediaManager;
	private $mediaPool;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;

		$this->componentConfig = $container->get('togu.generator.component.config');
		$this->metadataProcessor = $container->get('togu.annotation.processor');
		$this->urlGenerator = $container->get('cmf_routing.generator');
		$this->mediaManager = $container->get('sonata.media.manager.media');
		$this->mediaPool = $container->get('sonata.media.pool');
	}

	public function render($page) {
		$this->ids = 0;
		$this->sections = $page->getAllSections();
		array_shift($this->sections);

		$rootModel = new RootModel();
		$rootComponentConfig = $this->componentConfig->getConfig('rootComponent');
		$this->renderComponent($rootComponentConfig, $rootModel);
	}

	protected function renderComponent($componentConfig, $model) {
		$template = $componentConfig['template'];
		if(! isset($this->mapping[$template])) {
			throw new \Exception(sprintf('Template %s is not defined', $template));
		}
		$parentComponentConfig = $this->currentComponentConfig;
		$this->currentComponentConfig = $componentConfig;

		$mappedMethod = $this->mapping[$template];
		$result = $this->{$mappedMethod}($model);

		$this->currentComponentConfig = $parentComponentConfig;
		return $result;
	}

	protected function getContainerConfig($container) {
		return $this->currentComponentConfig['containers'][$container];
	}

	protected function getChildComponentConfig($containerConfig, $model) {
		$modelType = $this->metadataProcessor->getModelOfClass($model);
		foreach($containerConfig['types'] as $type) {
			if(isset($type['modelType']) && $type['modelType'] != $modelType) {
				continue;
			}
			if(isset($type['partial'])) {
				return array(
					"template" => $this->currentComponentConfig['template'] . '_' . $type['partial']
				);
			}
			return $this->componentConfig->getConfig($type['component']);
		}
		throw new \InvalidArgumentException(sprintf('Impossible to find component for model %s', $modelType));
	}

	protected function nextSection() {
		$containerConfig = $this->getContainerConfig('nextSection');

		$section = array_shift($this->sections);
		if(! $section) {
			return;
		}
		$childComponentConfig = $this->getChildComponentConfig($containerConfig, $section);

		return $this->renderComponent($childComponentConfig, $section);
	}

	protected function getField($model, $name) {
		$method = 'get'. ucfirst($name);
		if(! method_exists($model, $method)) {
			throw new \InvalidArgumentException(sprintf('Model %s has not the getter %s', get_class($model), $method));
		}
		return call_user_func(array($model, $method));
	}

	protected function container($model, $name) {
		$containerConfig = $this->getContainerConfig($name);

		$field = $containerConfig['field'];
		$childModels = $this->getField($model, $field);

		$html = "";
		foreach($childModels as $childModel) {
			$childComponentConfig = $this->getChildComponentConfig($containerConfig, $childModel);
			$html .= $this->renderComponent($childComponentConfig, $childModel);
		}

		return $html;
	}

	protected function getId() {
		return 'cmf-' . $this->ids++;
	}

	protected function getLink($link) {
		return $this->urlGenerator->generate($link);
	}

	protected function getImage($attr, $image, $type) {
		if($type == "") {
			$type = "reference";
		}
		$provider = $this->mediaPool->getProvider($image->getProviderName());
		if($attr == "src") {
			return $provider->generatePublicUrl($image, $type);
		}
		$referenceProperties = $provider->getHelperProperties($image, $type);
		return $referenceProperties[$attr];
	}

	protected function cls($model, $variable, $filter) {
		return $this->getField($model, $variable);
	}

	protected function style($model, $cssProperty, $prefix, $variable, $filter, $suffix) {
		return $this->getField($model, $variable);
	}

	protected function attr($model, $attr, $prefix, $variable, $filter, $suffix) {
		return $this->getField($model, $variable);
	}

	protected function link($model, $attr, $prefix, $variable, $filter, $suffix) {
		if($attr !== 'href' || $filter == "external") {
			return $this->attr($model, $attr, $prefix, $variable, $filter, $suffix);
		}
		return $prefix . $this->getLink($this->getField($model, $variable)) . $suffix;
	}

	protected function image($model, $attr, $prefix, $variable, $filter, $suffix) {
		if($filter == "external") {
			return $this->attr($model, $attr, $prefix, $variable, $filter, $suffix);
		}
		return $this->getImage($attr, $this->getField($model, $variable), $filter);
	}

	protected function input($model, $attr, $prefix, $variable, $filter, $suffix) {
		return $this->attr($model, $attr, $prefix, $variable, $filter, $suffix);
	}

	protected function html($model, $variable) {
		return $this->getField($model, $variable);
	}
}