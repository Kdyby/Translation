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
use Kdyby\Translation\Diagnostics\Panel;
use Nette;
use Nette\Caching\Cache;
use Nette\DI\Container;
use Nette\PhpGenerator as Code;
use Nette\Utils\LimitedScope;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator as BaseTranslator;



/**
 * Translator.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class Translator extends BaseTranslator implements Nette\Localization\ITranslator
{

	/**
	 * @var \Nette\DI\Container
	 */
	private $container;

	/**
	 * @var array
	 */
	private $loaderIds;

	/**
	 * @var IUserLocaleResolver
	 */
	private $localeResolver;

	/**
	 * @var \Nette\Caching\Cache
	 */
	private $cache;

	/**
	 * @var Panel
	 */
	private $panel;

	/**
	 * @var array
	 */
	private $availableResourceLocales = array();



	/**
	 * Constructor.
	 *
	 * Available options:
	 *
	 *   * cache_dir: The cache directory (or null to disable caching)
	 *   * debug:     Whether to enable debugging or not (false by default)
	 *
	 * @param Container $container A ContainerInterface instance
	 * @param IUserLocaleResolver $localeResolver
	 * @param MessageSelector $selector  The message selector for pluralization
	 * @param \Nette\Caching\IStorage $cacheStorage
	 * @param array $loaderIds An array of loader Ids
	 */
	public function __construct(Container $container, IUserLocaleResolver $localeResolver, MessageSelector $selector,
		Nette\Caching\IStorage $cacheStorage, $loaderIds = array())
	{
		$this->container = $container;
		$this->localeResolver = $localeResolver;
		$this->loaderIds = $loaderIds;
		$this->cache = new Cache($cacheStorage, str_replace('\\', '.', __CLASS__));

		parent::__construct(NULL, $selector);
	}



	/**
	 * @internal
	 * @param Panel $panel
	 */
	public function injectPanel(Panel $panel)
	{
		$this->panel = $panel;
	}



	/**
	 * Replaces cache storage with simple memory storage (per-request).
	 */
	public function enableDebugMode()
	{
		$this->cache = new Cache(new Nette\Caching\Storages\MemoryStorage());
	}



	/**
	 * Translates the given string.
	 *
	 * @param string  $message    The message id
	 * @param integer $count      The number to use to find the indice of the message
	 * @param array   $parameters An array of parameters for the message
	 * @param string  $domain     The domain for the message
	 * @param string  $locale     The locale
	 *
	 * @return string
	 */
	public function translate($message, $count = NULL, array $parameters = array(), $domain = NULL, $locale = NULL)
	{
		if (empty($message)) {
			return $message;
		}

		if ($domain === NULL) {
			if (strpos($message, '.') !== FALSE && strpos($message, ' ') === FALSE) {
				list($domain, $message) = explode('.', $message, 2);

			} else {
				$domain = 'messages';
			}
		}

		$tmp = array();
		foreach ($parameters as $key => $val) {
			$tmp['%' . trim($key, '%') . '%'] = $val;
		}
		$parameters = $tmp;

		if ($count !== NULL) {
			return $this->transChoice($message, $count, $parameters + array('%count%' => $count), $domain, $locale);
		}

		return $this->trans($message, $parameters, $domain, $locale);
	}



	/**
	 * {@inheritdoc}
	 */
	public function trans($id, array $parameters = array(), $domain = 'messages', $locale = NULL)
	{
		$result = parent::trans($id, $parameters, $domain, $locale);
		if ($this->panel !== NULL && $id === $result) { // probably untranslated
			$this->panel->markUntranslated($id);
		}

		return $result;
	}



	/**
	 * {@inheritdoc}
	 */
	public function transChoice($id, $number, array $parameters = array(), $domain = 'messages', $locale = NULL)
	{
		try {
			$result = parent::transChoice($id, $number, $parameters, $domain, $locale);

		} catch (\Exception $e) {
			$result = $id;
			if ($this->panel !== NULL) {
				$this->panel->choiceError($e);
			}
		}

		if ($this->panel !== NULL && $id === $result) { // probably untranslated
			$this->panel->markUntranslated($id);
		}

		return $result;
	}



	/**
	 * {@inheritdoc}
	 */
	public function addResource($format, $resource, $locale, $domain = 'messages')
	{
		parent::addResource($format, $resource, $locale, $domain);

		if (!in_array($locale, $this->availableResourceLocales, TRUE)) {
			$this->availableResourceLocales[] = $locale;
		}
	}



	/**
	 * Returns array of locales from given resources
	 *
	 * @return array
	 */
	public function getAvailableLocales()
	{
		return $this->availableResourceLocales;
	}



	/**
	 * {@inheritdoc}
	 */
	protected function computeFallbackLocales($locale)
	{
		$fallback = parent::computeFallbackLocales($locale);

		foreach ($this->getAvailableLocales() as $available) {
			if ($available === $locale) {
				continue;
			}

			if (substr($available, 0, 2) === substr($locale, 0, 2)) {
				array_unshift($fallback, $available);
				break;
			}
		}

		return array_unique($fallback);
	}



	/**
	 * {@inheritdoc}
	 */
	public function getLocale()
	{
		if ($this->locale === NULL) {
			$this->locale = $this->localeResolver->resolve($this);
		}

		return $this->locale;
	}



	/**
	 * @return TemplateHelpers
	 */
	public function createTemplateHelpers()
	{
		return new TemplateHelpers($this);
	}



	/**
	 * {@inheritdoc}
	 */
	protected function loadCatalogue($locale)
	{
		if (empty($locale)) {
			throw new InvalidArgumentException("Invalid locale.");
		}

		if (isset($this->catalogues[$locale])) {
			return;
		}

		$storage = $this->cache->getStorage();
		if (!$storage instanceof Nette\Caching\Storages\PhpFileStorage) {
			if (($messages = $this->cache->load($locale)) !== NULL) {
				$this->catalogues[$locale] = new MessageCatalogue($locale, $messages);
				return;
			}

			$this->initialize();
			parent::loadCatalogue($locale);
			$this->cache->save($locale, $this->catalogues[$locale]->all());
			return;
		}

		$storage->hint = $locale;

		$cached = $compiled = $this->cache->load($locale);
		if ($compiled === NULL) {
			$this->initialize();
			parent::loadCatalogue($locale);
			$this->cache->save($locale, $compiled = $this->compilePhpCache($locale));
			$cached = $this->cache->load($locale);
		}

		$this->catalogues[$locale] = LimitedScope::load($cached['file']);
	}



	protected function compilePhpCache($locale)
	{
		$fallbackContent = '';
		$current = '';
		foreach ($this->computeFallbackLocales($locale) as $fallback) {
			$fallbackContent .= Code\Helpers::format(<<<EOF
\$catalogue? = new MessageCatalogue(?, ?);
\$catalogue?->addFallbackCatalogue(\$catalogue?);

EOF
				, new Code\PhpLiteral($fallback), $fallback, $this->catalogues[$fallback]->all(), new Code\PhpLiteral($current), new Code\PhpLiteral($fallback)
			);
			$current = $fallback;
		}

		$content = Code\Helpers::format(<<<EOF
use Symfony\Component\Translation\MessageCatalogue;

\$catalogue = new MessageCatalogue(?, ?);

?
return \$catalogue;

EOF
			, $locale, $this->catalogues[$locale]->all(), new Code\PhpLiteral($fallbackContent)
		);

		return '<?php' . "\n\n" . $content;
	}



	protected function initialize()
	{
		foreach ($this->loaderIds as $serviceId => $aliases) {
			foreach ($aliases as $alias) {
				$loader = $this->container->getService($serviceId);
				/** @var LoaderInterface $loader */
				$this->addLoader($alias, $loader);
			}
		}
	}

}
