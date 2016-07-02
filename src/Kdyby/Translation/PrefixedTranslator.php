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
use Latte;
use Nette;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PrefixedTranslator extends Nette\Object implements ITranslator
{

	/**
	 * @var \Nette\Localization\ITranslator
	 */
	private $translator;

	/**
	 * @var string
	 */
	private $prefix;



	/**
	 * @param string $prefix
	 * @param ITranslator $translator
	 */
	public function __construct($prefix, ITranslator $translator)
	{
		if ($translator instanceof PrefixedTranslator) { // todo: this is just an experiment
			$translator = $translator->unwrap();
		}

		$this->translator = $translator;
		$this->prefix = rtrim($prefix, '.');
	}



	/**
	 * @param string $message
	 * @param int|array|NULL $count
	 * @param array|string|NULL $parameters
	 * @param string|NULL $domain
	 * @param string|NULL $locale
	 * @return string
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
			$locale = $domain ?: NULL;
			$domain = $parameters ?: NULL;
			$parameters = $count ?: [];
			$count = NULL;
		}

		return $this->translator->translate($prefix . $translationString, $count, (array) $parameters, $domain, $locale);
	}



	/**
	 * @return ITranslator
	 */
	public function unwrap()
	{
		return $this->translator;
	}



	/**
	 * @param $template
	 * @return ITranslator
	 */
	public function unregister($template)
	{
		return self::overrideTemplateTranslator($template, $this->unwrap());
	}



	/**
	 * @param Latte\Template|\Nette\Bridges\ApplicationLatte\Template|\Nette\Templating\Template $template
	 * @param string $prefix
	 * @return ITranslator
	 * @throws InvalidArgumentException
	 */
	public static function register($template, $prefix)
	{
		$translator = new static($prefix, $template->global->translator);
		return self::overrideTemplateTranslator($template, $translator);
	}



	/**
	 * @param Latte\Template|\Nette\Bridges\ApplicationLatte\Template|\Nette\Templating\Template $template
	 * @param string $prefix
	 * @return ITranslator
	 * @throws InvalidArgumentException
	 */
	public static function register23($template, $prefix)
	{
		try {
			$translator = $template->getTranslator();

		} catch (\LogicException $e) {
			throw new InvalidArgumentException('Please register helpers from \Kdyby\Translation\TemplateHelpers before using translator prefixes.', 0, $e);
		}

		/** @var ITranslator $translator */
		$translator = new static($prefix, $translator);
		return self::overrideTemplateTranslator($template, $translator);
	}



	/**
	 * @param Latte\Template|Latte\Runtime\Template|\Nette\Bridges\ApplicationLatte\Template|\Nette\Templating\Template $template
	 * @param ITranslator $translator
	 */
	private static function overrideTemplateTranslator($template, ITranslator $translator)
	{
		if ($template instanceof Latte\Runtime\Template || $template instanceof Latte\Template) {
			$template->getEngine()->addFilter('translate', [new TemplateHelpers($translator), 'translate']);

		} elseif ($template instanceof \Nette\Bridges\ApplicationLatte\Template) {
			$template->getLatte()->addFilter('translate', [new TemplateHelpers($translator), 'translate']);

		} elseif ($template instanceof \Nette\Templating\Template) {
			$template->registerHelper('translate', [new TemplateHelpers($translator), 'translate']);
		}

		return $translator;
	}

}
