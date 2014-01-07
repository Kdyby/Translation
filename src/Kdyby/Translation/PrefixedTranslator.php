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
use Nette\Localization\ITranslator;
use Nette\Utils\Callback;



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
	 * @param Nette\Templating\Template $template
	 * @return ITranslator
	 */
	public function unregister(Nette\Templating\Template $template)
	{
		$translator = $this->unwrap();
		$template->registerHelper('translator', array(new TemplateHelpers($translator), 'translate'));
		return $translator;
	}



	/**
	 * @param Nette\Templating\Template $template
	 * @param string $prefix
	 * @return ITranslator
	 * @throws InvalidArgumentException
	 */
	public static function register(Nette\Templating\Template $template, $prefix)
	{
		if (!$translator = self::findTranslator($template)) {
			throw new InvalidArgumentException("You have to pass the Translator the the template using `\$template->setTranslator(\$translator);`, or register the helper loader.");
		}

		/** @var ITranslator $translator */
		$translator = new static($prefix, $translator);
		$template->setTranslator($translator);

		return $translator;
	}



	/**
	 * @param Nette\Templating\Template $template
	 * @return ITranslator
	 */
	private static function findTranslator(Nette\Templating\Template $template)
	{
		$helpers = $template->getHelpers();
		if (isset($helpers['translate'])) {
			$helper = $helpers['translate'] instanceof Nette\Callback ? $helpers['translate']->getNative() : $helpers['translate'];
			$obj = isset($helper[0]) ? $helper[0] : NULL;
			if ($obj instanceof ITranslator) {
				return $obj;

			} elseif ($obj instanceof TemplateHelpers) {
				return $obj->getTranslator();
			}
		}

		foreach ($template->getHelperLoaders() as $loader) {
			$loader = $loader instanceof Nette\Callback ? $loader->getNative() : $loader;
			$obj = is_array($loader) && isset($loader[0]) ? $loader[0] : NULL;
			if ($obj instanceof TemplateHelpers) {
				$template->setTranslator($translator = $obj->getTranslator());
				return $translator;
			}
		}

		return NULL;
	}

}

