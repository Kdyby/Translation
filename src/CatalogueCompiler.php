<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Kdyby\Translation\Caching\PhpFileStorage;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Caching\Storages\MemoryStorage;
use Nette\PhpGenerator\Helpers as GeneratorHelpers;
use Nette\PhpGenerator\PhpLiteral;

class CatalogueCompiler
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Nette\Caching\Cache
	 */
	private $cache;

	/**
	 * @var \Kdyby\Translation\FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var \Kdyby\Translation\CatalogueFactory
	 */
	private $catalogueFactory;

	public function __construct(
		IStorage $cacheStorage,
		FallbackResolver $fallbackResolver,
		CatalogueFactory $catalogueFactory
	)
	{
		$this->cache = new Cache($cacheStorage, Translator::class);
		$this->fallbackResolver = $fallbackResolver;
		$this->catalogueFactory = $catalogueFactory;
	}

	/**
	 * Replaces cache storage with simple memory storage (per-request).
	 */
	public function enableDebugMode()
	{
		$this->cache = new Cache(new MemoryStorage());
	}

	public function invalidateCache()
	{
		$this->cache->clean([Cache::ALL => TRUE]);
	}

	/**
	 * @param string $format
	 * @param string $resource
	 * @param string $locale
	 * @param string|NULL $domain
	 */
	public function addResource($format, $resource, $locale, $domain = NULL)
	{
		$this->catalogueFactory->addResource($format, $resource, $locale, $domain);
	}

	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @param \Symfony\Component\Translation\MessageCatalogueInterface[] $availableCatalogues
	 * @param string $locale
	 * @throws \Kdyby\Translation\InvalidArgumentException
	 * @return \Symfony\Component\Translation\MessageCatalogueInterface[]
	 */
	public function compile(Translator $translator, array &$availableCatalogues, $locale)
	{
		if (empty($locale)) {
			throw new \Kdyby\Translation\InvalidArgumentException('Invalid locale');
		}

		if (isset($availableCatalogues[$locale])) {
			return $availableCatalogues;
		}
		$cacheKey = [$locale, $translator->getFallbackLocales()];

		$storage = $this->cache->getStorage();
		if (!$storage instanceof PhpFileStorage) {
			$messages = $this->cache->load($cacheKey);
			if ($messages !== NULL) {
				$availableCatalogues[$locale] = new MessageCatalogue($locale, $messages);
				return $availableCatalogues;
			}

			$this->catalogueFactory->createCatalogue($translator, $availableCatalogues, $locale);
			$this->cache->save($cacheKey, $availableCatalogues[$locale]->all());
			return $availableCatalogues;
		}

		$storage->hint = $locale;

		$cached = $compiled = $this->cache->load($cacheKey);
		if ($compiled === NULL) {
			$this->catalogueFactory->createCatalogue($translator, $availableCatalogues, $locale);
			$this->cache->save($cacheKey, $compiled = $this->compilePhpCache($translator, $availableCatalogues, $locale));
			$cached = $this->cache->load($cacheKey);
		}

		$availableCatalogues[$locale] = self::load($cached['file']);

		return $availableCatalogues;
	}

	/**
	 * @param \Kdyby\Translation\Translator $translator
	 * @param \Symfony\Component\Translation\MessageCatalogueInterface[] $availableCatalogues
	 * @param string $locale
	 * @return string
	 */
	protected function compilePhpCache(Translator $translator, array &$availableCatalogues, $locale)
	{
		$fallbackContent = '';
		$current = new PhpLiteral('');
		foreach ($this->fallbackResolver->compute($translator, $locale) as $fallback) {
			$fallbackSuffix = new PhpLiteral(ucfirst(preg_replace('~[^a-z0-9_]~i', '_', $fallback)));

			$fallbackContent .= GeneratorHelpers::format(<<<EOF
\$catalogue? = new MessageCatalogue(?, ?);
\$catalogue?->addFallbackCatalogue(\$catalogue?);

EOF
				, $fallbackSuffix, $fallback, $availableCatalogues[$fallback]->all(), $current, $fallbackSuffix);
			$current = $fallbackSuffix;
		}

		$content = GeneratorHelpers::format(<<<EOF
use Kdyby\\Translation\\MessageCatalogue;

\$catalogue = new MessageCatalogue(?, ?);

?
return \$catalogue;

EOF
			, $locale, $availableCatalogues[$locale]->all(), new PhpLiteral($fallbackContent));

		return '<?php' . "\n\n" . $content;
	}

	/**
	 * @return \Symfony\Component\Translation\MessageCatalogueInterface
	 */
	protected static function load()
	{
		return include func_get_arg(0);
	}

}
