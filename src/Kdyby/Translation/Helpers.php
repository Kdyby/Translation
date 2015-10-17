<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation;

/**
 * @author Azathoth <memnarch@seznam.cz>
 */
class Helpers
{

	public static function flatten(array &$messages, array $subNode = NULL, $path = NULL)
	{
		if (NULL === $subNode) {
			$subNode = &$messages;
		}

		foreach ($subNode as $key => $value) {
			if (is_array($value)) {
				$nodePath = $path ? $path . '.' . $key : $key;
				static::flatten($messages, $value, $nodePath);
				if ($path === NULL) {
					unset($messages[$key]);
				}

			} elseif ($path !== NULL) {
				$messages[$path . '.' . $key] = $value;
			}
		}
	}



	/**
	 * @internal just because of PHP 5.4, where function array_column is not present. Fuck you, PHP 5.4
	 * @param array $input
	 * @param string $columnKey
	 * @param string $indexKey
	 * @return array
	 */
	public static function arrayColumn(array $input, $columnKey, $indexKey = NULL)
	{
		$result = array();
		foreach ($input as $k => $v) {
			$result[$indexKey ? $v[$indexKey] : $k] = $v[$columnKey];
		}
		return $result;
	}



	/**
	 * @param string $message
	 * @return array [domain, message]
	 */
	public static function extractMessageDomain($message)
	{
		if (strpos($message, '.') !== FALSE && strpos($message, ' ') === FALSE) {
			list($domain, $message) = explode('.', $message, 2);
		} else {
			$domain = 'messages';
		}

		return array($domain, $message);
	}

}
