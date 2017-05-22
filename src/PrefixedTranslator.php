<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

use Latte\Runtime\Template;
use Nette\Utils\Strings;

class PrefixedTranslator implements \Kdyby\Translation\ITranslator
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var \Kdyby\Translation\ITranslator|\Kdyby\Translation\Translator|\Kdyby\Translation\PrefixedTranslator
	 */
	private $translator;

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @param string $prefix
	 * @param \Kdyby\Translation\ITranslator $translator
	 * @throws \Kdyby\Translation\InvalidArgumentException
	 */
	public function __construct($prefix, ITranslator $translator)
	{
		if (!$translator instanceof Translator && !$translator instanceof PrefixedTranslator) {
			throw new \Kdyby\Translation\InvalidArgumentException(sprintf(
				'The given translator must be instance of %s or %s, bug %s was given',
				Translator::class,
				self::class,
				get_class($translator)
			));
		}

		if ($translator instanceof PrefixedTranslator) {
			$translator = $translator->unwrap();
		}

		$this->translator = $translator;
		$this->prefix = rtrim($prefix, '.');
	}

	/**
	 * @param string|\Kdyby\Translation\Phrase $message
	 * @param int|array|NULL $count
	 * @param array|string|NULL $parameters
	 * @param string|NULL $domain
	 * @param string|NULL $locale
	 * @return string|\Nette\Utils\IHtmlString|\Latte\Runtime\IHtmlString
	 */
	public function translate($message, $count = NULL, $parameters = [], $domain = NULL, $locale = NULL)
	{
		$translationString = ($message instanceof Phrase ? $message->message : $message);
		$prefix = $this->prefix . '.';

		if (Strings::startsWith($message, '//')) {
			$prefix = NULL;
			$translationString = Strings::substring($translationString, 2);
		}

		if ($message instanceof Phrase) {
			return $this->translator->translate(new Phrase($prefix . $translationString, $message->count, $message->parameters, $message->domain, $message->locale));
		}

		if (is_array($count)) {
			$locale = $domain !== NULL ? (string) $domain : NULL;
			$domain = $parameters !== NULL && !empty($parameters) ? (string) $parameters : NULL;
			$parameters = $count;
			$count = NULL;
		}

		return $this->translator->translate($prefix . $translationString, $count, (array) $parameters, $domain, $locale);
	}

	/**
	 * @return \Kdyby\Translation\ITranslator
	 */
	public function unwrap()
	{
		return $this->translator;
	}

	/**
	 * @param \Latte\Runtime\Template $template
	 * @return \Kdyby\Translation\ITranslator
	 */
	public function unregister(Template $template)
	{
		$translator = $this->unwrap();
		self::overrideTemplateTranslator($template, $translator);
		return $translator;
	}

	/**
	 * @param \Latte\Runtime\Template $template
	 * @param string $prefix
	 * @throws \Kdyby\Translation\InvalidArgumentException
	 * @return \Kdyby\Translation\ITranslator
	 */
	public static function register(Template $template, $prefix)
	{
		$translator = new static($prefix, $template->global->translator);
		self::overrideTemplateTranslator($template, $translator);
		return $translator;
	}

	private static function overrideTemplateTranslator(Template $template, ITranslator $translator)
	{
		$template->getEngine()->addFilter('translate', [new TemplateHelpers($translator), 'translate']);
	}

}
