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

class CatalogueFactory
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Kdyby\Translation\FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var \Kdyby\Translation\IResourceLoader
	 */
	private $loader;

	/**
	 * @var array[]
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
	public function addResource($format, $resource, $locale, $domain = 'messages'): void
	{
		$this->resources[$locale][] = [$format, $resource, $domain];
	}

	/**
	 * @return array
	 */
	public function getResources(): array
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
	 * @param \Kdyby\Translation\Translator $translator
	 * @param \Symfony\Component\Translation\MessageCatalogueInterface[] $availableCatalogues
	 * @param string $locale
	 * @throws \Symfony\Component\Translation\Exception\NotFoundResourceException
	 * @return \Symfony\Component\Translation\MessageCatalogueInterface
	 */
	public function createCatalogue(Translator $translator, array &$availableCatalogues, $locale)
	{
		try {
			$this->doLoadCatalogue($availableCatalogues, $locale);

		} catch (\Symfony\Component\Translation\Exception\NotFoundResourceException $e) {
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
			$newFallbackFallback = $newFallback->getFallbackCatalogue();
			if ($newFallbackFallback !== NULL && isset($chain[$newFallbackFallback->getLocale()])) {
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
