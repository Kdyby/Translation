<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Kdyby;
use Nette;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\MessageCatalogueInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CatalogueFactory extends Nette\Object
{

	/**
	 * @var FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var IResourceLoader
	 */
	private $loader;

	/**
	 * @var array
	 */
	private $resources = [];



	public function __construct(FallbackResolver $fallbackResolver, IResourceLoader $loader)
	{
		$this->fallbackResolver = $fallbackResolver;
		$this->loader = $loader;
	}



	/**
	 * {@inheritdoc}
	 */
	public function addResource($format, $resource, $locale, $domain = 'messages')
	{
		$this->resources[$locale][] = [$format, $resource, $domain];
	}



	/**
	 * @return array
	 */
	public function getResources()
	{
		$list = [];
		foreach ($this->resources as $locale => $resources) {
			foreach ($resources as $meta) {
				$list[] = $meta[1]; // resource file
			}
		}

		return $list;
	}



	/**
	 * @param Translator $translator
	 * @param MessageCatalogueInterface[] $availableCatalogues
	 * @param string $locale
	 * @throws NotFoundResourceException
	 * @return MessageCatalogueInterface
	 */
	public function createCatalogue(Translator $translator, array &$availableCatalogues, $locale)
	{
		try {
			$this->doLoadCatalogue($availableCatalogues, $locale);

		} catch (NotFoundResourceException $e) {
			if (!$this->fallbackResolver->compute($translator, $locale)) {
				throw $e;
			}
		}

		$current = $availableCatalogues[$locale];

		$chain = [$locale => TRUE];
		foreach ($this->fallbackResolver->compute($translator, $locale) as $fallback) {
			if (!isset($availableCatalogues[$fallback])) {
				$this->doLoadCatalogue($availableCatalogues, $fallback);
			}

			$newFallback = $availableCatalogues[$fallback];
			if (($newFallbackFallback = $newFallback->getFallbackCatalogue()) && isset($chain[$newFallbackFallback->getLocale()])) {
				break;
			}

			$current->addFallbackCatalogue($newFallback);
			$current = $newFallback;
			$chain[$fallback] = TRUE;
		}

		return $availableCatalogues[$locale];
	}



	private function doLoadCatalogue(array &$availableCatalogues, $locale)
	{
		$availableCatalogues[$locale] = $catalogue = new MessageCatalogue($locale);

		if (isset($this->resources[$locale])) {
			foreach ($this->resources[$locale] as $resource) {
				$this->loader->loadResource($resource[0], $resource[1], $resource[2], $catalogue);
			}
		}

		return $catalogue;
	}

}
