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
use Nette\Utils\PhpGenerator as Code;
use Nette\Utils\LimitedScope;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CatalogueCompiler extends Nette\Object
{

	/**
	 * @var \Nette\Caching\Cache
	 */
	private $cache;

	/**
	 * @var FallbackResolver
	 */
	private $fallbackResolver;

	/**
	 * @var LoadersInitializer
	 */
	private $loadersInitializer;

	/**
	 * @var CatalogueFactory
	 */
	private $catalogueFactory;



	public function __construct(Nette\Caching\IStorage $cacheStorage, FallbackResolver $fallbackResolver,
		CatalogueFactory $catalogueFactory, LoadersInitializer $loadersInitializer)
	{
		$this->cache = new Cache($cacheStorage, 'Kdyby\\Translation\\Translator');
		$this->fallbackResolver = $fallbackResolver;
		$this->catalogueFactory = $catalogueFactory;
		$this->loadersInitializer = $loadersInitializer;
	}



	/**
	 * Replaces cache storage with simple memory storage (per-request).
	 */
	public function enableDebugMode()
	{
		$this->cache = new Cache(new Nette\Caching\Storages\MemoryStorage());
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

		$storage = $this->cache->getStorage();
		if (!$storage instanceof Nette\Caching\Storages\PhpFileStorage) {
			if (($messages = $this->cache->load($locale)) !== NULL) {
				$availableCatalogues[$locale] = new MessageCatalogue($locale, $messages);
				return $availableCatalogues;
			}

			$this->loadersInitializer->initialize($translator);
			$this->catalogueFactory->createCatalogue($translator, $availableCatalogues, $locale);
			$this->cache->save($locale, $availableCatalogues[$locale]->all());
			return $availableCatalogues;
		}

		$storage->hint = $locale;

		$cached = $compiled = $this->cache->load($locale);
		if ($compiled === NULL) {
			$this->loadersInitializer->initialize($translator);
			$this->catalogueFactory->createCatalogue($translator, $availableCatalogues, $locale);
			$this->cache->save($locale, $compiled = $this->compilePhpCache($translator, $availableCatalogues, $locale));
			$cached = $this->cache->load($locale);
		}

		$availableCatalogues[$locale] = LimitedScope::load($cached['file']);

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
			$fallbackSuffix = new Code\PhpLiteral(ucfirst(str_replace('-', '_', $fallback)));

			$fallbackContent .= Code\Helpers::format(<<<EOF
\$catalogue? = new MessageCatalogue(?, ?);
\$catalogue?->addFallbackCatalogue(\$catalogue?);

EOF
				, $fallbackSuffix, $fallback, $availableCatalogues[$fallback]->all(), $current, $fallbackSuffix
			);
			$current = $fallbackSuffix;
		}

		$content = Code\Helpers::format(<<<EOF
use Symfony\Component\Translation\MessageCatalogue;

\$catalogue = new MessageCatalogue(?, ?);

?
return \$catalogue;

EOF
			, $locale, $availableCatalogues[$locale]->all(), new Code\PhpLiteral($fallbackContent)
		);

		return '<?php' . "\n\n" . $content;
	}

}
