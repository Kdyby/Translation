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
use Nette\Caching\Cache;
use Nette\PhpGenerator as Code;
use Symfony\Component\Translation\MessageCatalogueInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CatalogueCompiler extends Nette\Object
{

	const TAG_TRANSLATION = 'Kdyby\\Translation\\Translator';

	/**
	 * @var \Nette\Caching\Cache
	 */
	private $cache;

	/**
	 * @var FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var CatalogueFactory
	 */
	private $catalogueFactory;

	/**
	 * @var Nette\Caching\IStorage
	 */
	private $cacheStorage;


	public function __construct(Nette\Caching\IStorage $cacheStorage, FallbackResolver $fallbackResolver,
		CatalogueFactory $catalogueFactory)
	{
		$this->cacheStorage = $cacheStorage;
		$this->cache = new Cache($cacheStorage, 'Kdyby\\Translation\\Translator');
		$this->fallbackResolver = $fallbackResolver;
		$this->catalogueFactory = $catalogueFactory;
	}



	/**
	 * Replaces cache storage with simple memory storage (per-request).
	 */
	public function enableDebugMode()
	{
		$this->cache = new Cache(new Nette\Caching\Storages\MemoryStorage());
	}


	/**
	 * Replaces cache storage with simple memory storage (per-request).
	 */
	public function disableDebugMode()
	{
		$this->cache = new Cache($this->cacheStorage, 'Kdyby\\Translation\\Translator');
	}


	public function invalidateCache($onlyTranslation = FALSE)
	{
		if ($onlyTranslation) {
			$this->cache->clean(array(Cache::TAGS => static::TAG_TRANSLATION));
		} else {
			$this->cache->clean(array(Cache::ALL => TRUE));
		}
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
	 * @param Translator $translator
	 * @param MessageCatalogueInterface[] $availableCatalogues
	 * @param string $locale
	 * @throws InvalidArgumentException
	 * @return MessageCatalogueInterface|NULL
	 */
	public function compile(Translator $translator, array &$availableCatalogues, $locale)
	{
		if (empty($locale)) {
			throw new InvalidArgumentException("Invalid locale.");
		}

		if (isset($availableCatalogues[$locale])) {
			return $availableCatalogues;
		}
		$cacheKey = array($locale, $translator->getFallbackLocales());

		$storage = $this->cache->getStorage();
		if (!$storage instanceof Kdyby\Translation\Caching\PhpFileStorage) {
			if (($messages = $this->cache->load($cacheKey)) !== NULL) {
				$availableCatalogues[$locale] = new MessageCatalogue($locale, $messages);
				return $availableCatalogues;
			}

			$this->catalogueFactory->createCatalogue($translator, $availableCatalogues, $locale);
			$this->cache->save($cacheKey, $availableCatalogues[$locale]->all(), array(Cache::TAGS => array(static::TAG_TRANSLATION)));
			return $availableCatalogues;
		}

		$storage->hint = $locale;

		$cached = $compiled = $this->cache->load($cacheKey);
		if ($compiled === NULL) {
			$this->catalogueFactory->createCatalogue($translator, $availableCatalogues, $locale);
			$this->cache->save($cacheKey, $compiled = $this->compilePhpCache($translator, $availableCatalogues, $locale),
				array(Cache::TAGS => array(static::TAG_TRANSLATION)));
			$cached = $this->cache->load($cacheKey);
		}

		$availableCatalogues[$locale] = self::load($cached['file']);

		return $availableCatalogues;
	}



	/**
	 * @param Translator $translator
	 * @param MessageCatalogueInterface[] $availableCatalogues
	 * @param string $locale
	 * @return string
	 */
	protected function compilePhpCache(Translator $translator, array &$availableCatalogues, $locale)
	{
		$fallbackContent = '';
		$current = new Code\PhpLiteral('');
		foreach ($this->fallbackResolver->compute($translator, $locale) as $fallback) {
			$fallbackSuffix = new Code\PhpLiteral(ucfirst(preg_replace('~[^a-z0-9_]~i', '_', $fallback)));

			$fallbackContent .= Code\Helpers::format(<<<EOF
\$catalogue? = new MessageCatalogue(?, ?);
\$catalogue?->addFallbackCatalogue(\$catalogue?);

EOF
				, $fallbackSuffix, $fallback, $availableCatalogues[$fallback]->all(), $current, $fallbackSuffix
			);
			$current = $fallbackSuffix;
		}

		$content = Code\Helpers::format(<<<EOF
use Kdyby\\Translation\\MessageCatalogue;

\$catalogue = new MessageCatalogue(?, ?);

?
return \$catalogue;

EOF
			, $locale, $availableCatalogues[$locale]->all(), new Code\PhpLiteral($fallbackContent)
		);

		return '<?php' . "\n\n" . $content;
	}



	protected static function load(/*$file*/)
	{
		return include func_get_arg(0);
	}

}
