<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\LocaleResolver;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AcceptHeaderResolver extends Nette\Object implements Kdyby\Translation\IUserLocaleResolver
{

	/**
	 * @var Nette\Http\Request
	 */
	private $httpRequest;



	/**
	 * @param Nette\Http\IRequest $httpRequest
	 */
	public function __construct(Nette\Http\IRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}



	/**
	 * Detects language from the Accept-Language header.
	 * This method uses the code from Nette\Http\Request::detectLanguage.
	 * @link https://github.com/nette/http/blob/0d9ef49051fba799148ef877dd32928a68731766/src/Http/Request.php#L294-L326
	 * @author David Grudl
	 * @param Kdyby\Translation\Translator $translator
	 * @return string|NULL
	 */
	public function resolve(Kdyby\Translation\Translator $translator)
	{
		$header = $this->httpRequest->getHeader('Accept-Language');
		if (!$header) {
			return NULL;
		}

		$langs = [];
		foreach ($translator->getAvailableLocales() as $locale) {
			$langs[] = $locale;
			if (strlen($locale) > 2) {
				$langs[] = substr($locale, 0, 2);
			}
		}

		if (!$langs) {
			return NULL;
		}

		$s = strtolower($header);  // case insensitive
		$s = strtr($s, '_', '-');  // cs_CZ means cs-CZ
		rsort($langs);             // first more specific
		preg_match_all('#(' . implode('|', $langs) . ')(?:-[^\s,;=]+)?\s*(?:;\s*q=([0-9.]+))?#', $s, $matches);

		if (!$matches[0]) {
			return NULL;
		}

		$max = 0;
		$lang = NULL;
		foreach ($matches[1] as $key => $value) {
			$q = $matches[2][$key] === '' ? 1.0 : (float) $matches[2][$key];
			if ($q > $max) {
				$max = $q;
				$lang = $value;
			}
		}

		return $lang;
	}

}
