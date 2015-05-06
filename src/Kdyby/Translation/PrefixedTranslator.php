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
use Tracy;



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



	public function __construct($prefix, ITranslator $translator)
	{
		if ($translator instanceof PrefixedTranslator) { // todo: this is just an experiment
			$translator = $translator->unwrap();
		}

		$this->translator = $translator;
		$this->prefix = rtrim($prefix, '.');
	}



	public function translate($message, $count = NULL, $parameters = array(), $domain = NULL, $locale = NULL)
	{
		if ($message instanceof Phrase) {
			return $this->translator->translate(new Phrase($this->prefix . '.' . $message->message, $message->count, $message->parameters, $message->domain, $message->locale));
		}

		if (is_array($count)) {
			$locale = $domain ? : NULL;
			$domain = $parameters ? : NULL;
			$parameters = $count ? : array();
			$count = NULL;
		}

		return $this->translator->translate($this->prefix . '.' . $message, $count, (array) $parameters, $domain, $locale);
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
	 * @param Latte\Template|\Nette\Bridges\ApplicationLatte\Template|\Nette\Templating\Template $template
	 * @param ITranslator $translator
	 */
	private static function overrideTemplateTranslator($template, ITranslator $translator)
	{
		if ($template instanceof Latte\Template) {
			$template->getEngine()->addFilter('translate', array(new TemplateHelpers($translator), 'translate'));

		} elseif ($template instanceof \Nette\Bridges\ApplicationLatte\Template) {
			$template->getLatte()->addFilter('translate', array(new TemplateHelpers($translator), 'translate'));

		} elseif ($template instanceof \Nette\Templating\Template) {
			$template->registerHelper('translate', array(new TemplateHelpers($translator), 'translate'));
		}

		return $translator;
	}

}
