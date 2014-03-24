<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Diagnostics;

use Kdyby;
use Kdyby\Translation\InvalidResourceException;
use Kdyby\Translation\Translator;
use Nette;
use Symfony\Component\Yaml;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Panel extends Nette\Object implements Nette\Diagnostics\IBarPanel
{

	/**
	 * @var \Kdyby\Translation\Translator
	 */
	private $translator;

	/**
	 * @var array
	 */
	private $untranslated = array();

	/**
	 * @var array
	 */
	private $resources = array();

	/**
	 * @var string
	 */
	private $rootDir;



	/**
	 * @param string $rootDir
	 * @param Translator $translator
	 */
	public function __construct($rootDir, Translator $translator)
	{
		$this->translator = $translator;
		$this->rootDir = $rootDir;
	}



	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		return '<span title="Translation"><img width="16px" height="16px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB90DExAuL9uIsPUAAAQ2SURBVFjD7Ze9bxxVFMV/583skjgOIXEaKCNFAgmQ+ExS8qEUCEHH30BBScG/QM1fEmgoAAkoAigSKKFASElHESEFbGcdez7uoXhvZteW7ewmQjRMsTs7s/Pufeece+4d+P/4jw8ddvHq1avv2b5mm02Lr9Y7/gpQBCFDD5YhgsAoRLhHMtHnRYNAAb3yt92//9y3v3x+MFY6LIGIuGYbKedngzAGsPK5Kb8BGRAu9xiuL+zQ6NphsQ5NYB7YhA1jQCObyOHG/3sMlpM0hmGNcmXh7w9PYL5wfsphbCMPaOR7BipEJahV0FHZvZ2D+1i2qY+gYERCZTGZzLdFkLNIht/v72AHGM5Pa85MqowUc4p81PaPo2CgIa9RxOZ8jowDZm0WXo2oBdttN8ihIABC5dPLI7BIQQA5prEGIeYdbnVtSSpD3WCaCCqpMB8jXSshEBFEROZ9TCTvepC/be63HWA2ppNRoJttP1flvkp5BArmNBQl5zSIMJtNRxJEwHqVOF1XGLHVdSQp+0CpAi1UytJVYHteBQ7cm7AgIKnAH7CWhBDrVRqfud91UERLFNPyihQMwaOUIAI5I9D0PU2Xz9frCjs4WVVUMhJstR2SwaUWgvx7VQqyG2b4IoIoO9xsWipllJ6sK1JB7Exd4whmbUcYlCVcRKrVq0DSqCIVRlPZ4SCqO7MHC0Ey1Epiu+k5XafMvbRaFXCgdBxBOIgw202/TyNBtmuHibJRYba6LjuAsgjjCA3UxwWPCEwanRCJ7bYd/eHspFootdwhdvqeJsyegyaCSVK271US2E9BNp8otbTd9STlB89NJvRFmJREJ4K7e0El2Op6NiY1x7GQlpoawihgq+lIZF2s1xWdu9GAcO6Yp6rcE4OcwGDGR7nhUglYRjL32pbOQdMH61VCzgiFc8eUs+2cTIkuoI2MmH20D9TLzEzuTafgwtp0dNXO5FlhsOvSh23zzBPTBfDm4ny0BBBWAInWpSmVmSDzqrEko0TpBwct9W+xGgKL41hWoMeJB/LQsa/JlOYkVDrmAHlpwnF0M6of2gdIC7vUvDUNk1HMbwVRbmo+jA3tOlaYByTYbRJdiG1E3UFFg2IvLxqm67qR1yg71kLflDXvwhHhsxtbSycw2xWffXiPP+/NmFTiGyX21t9m99RbWDCZ1Fy5dAlJeXyz6aOfz48Mzli6aPTp8rMXz/585UVeun7z4WW418Kll0/w9Pk1/t5JTHdh2otpElMl1PX8eP06fdus9ObhQ2g4VBrVxpvvdl/Ovmgacev2Dnf+2GXnxi6zGw/KMJrNJUXPS6fXmCaNw+d8GNV8KHXgp87xyg83tVQCALPvLlvCVUK/3tnh9t3mkw8+/u3Tx3kNu/H6C7z6063lnLBU4dd98NHzF9Z47eKpE4/7Hngw+LHHzveX3zmAyBv/xsvpP+li/lm3bxkuAAAAAElFTkSuQmCC" />'
			. $this->translator->getLocale() . ($this->untranslated ? ' <b>(' . count(array_unique($this->untranslated)) . ' errors)</b>' : '')
			. '</span>';
	}



	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$s = '';
		$h = 'htmlSpecialChars';

		foreach ($unique = array_unique($this->untranslated) as $message) {
			$s .= '<tr><td>';

			if ($message instanceof \Exception) {
				$s .= '<span style="color:red">' . $h($message->getMessage()) . '</span>';

			} elseif ($message instanceof Nette\Utils\Html) {
				$s .= '<span style="color:red">Nette\Utils\Html(' . $h((string) $message) . ')</span>';

			} else {
				$s .= $h($message);
			}

			$s .= '</td></tr>';
		}

		$untranslated = '<table style="width:100%"><tr><th>Untranslated message</th></tr>' . $s . '</table>';

		$s = '';
		ksort($this->resources);
		foreach ($this->resources as $locale => $resources) {
			foreach ($resources as $resourcePath => $domain) {
				$s .= '<tr>';
				$s .= '<td>' . $h($locale) . '</td>';
				$s .= '<td>' . $h($domain) . '</td>';

				$relativePath = str_replace(rtrim($this->rootDir, '/') . '/', '', $resourcePath);
				if (Nette\Utils\Strings::startsWith($relativePath, 'vendor/')) {
					$parts = explode('/', $relativePath, 4);
					$left = array_pop($parts);
					$relativePath = implode('/', $parts) . '/.../' . basename($left);
				}

				$s .= '<td>' . Nette\Diagnostics\Helpers::editorLink($resourcePath, 1)->setText($relativePath) . '</td>';

				$s .= '</tr>';
			}
		}

		$resources = '<table style="width:100%"><tr><th>Locale</th><th>Domain</th><th>Resource filename</th></tr>' . $s . '</table>';

		$panel = array();

		if (!empty($this->untranslated)) {
			$panel[] = $untranslated;
		}

		if (!empty($this->resources)) {
			if (!empty($this->untranslated)) {
				$panel[] = '<br><br><h1>Loaded resources</h1>';
			}

			$panel[] = $resources;
		}

		return empty($panel) ? '' :
			'<h1>Missing translations: ' .  count($unique) . ', Resources: ' . count(Nette\Utils\Arrays::flatten($this->resources)) . '</h1>' .
			'<div class="nette-inner kdyby-TranslationPanel" style="min-width:500px">' . implode($panel) . '</div>';
	}



	public function markUntranslated($id)
	{
		$this->untranslated[] = $id;
	}



	public function choiceError(\Exception $e)
	{
		$this->untranslated[] = $e;
	}



	public function addResource($format, $resource, $locale, $domain)
	{
		$this->resources[$locale][$resource] = $domain;
	}



	/**
	 * @param Translator $translator
	 * @param string $rootDir
	 * @return Panel
	 */
	public static function register(Translator $translator, $rootDir)
	{
		$panel = new static($rootDir, $translator);
		/** @var Panel $panel */
		$translator->injectPanel($panel);

		$bar = method_exists('Nette\Diagnostics\Debugger', 'getBar')
			? Nette\Diagnostics\Debugger::getBar()
			: Nette\Diagnostics\Debugger::$bar;

		$bar->addPanel($panel, 'kdyby.translation');

		return $panel;
	}



	public static function renderException(\Exception $e = NULL)
	{
		if (!$e instanceof InvalidResourceException || !($previous = $e->getPrevious())) {
			return NULL;
		}

		$previous = $previous->getPrevious();
		if (!$previous instanceof Yaml\Exception\ParseException) {
			return NULL;
		}

		$method = 'Symfony\Component\Translation\Loader\YamlFileLoader::load';
		if ($call = Nette\Diagnostics\Helpers::findTrace($e->getPrevious()->getTrace(), $method)) {
			return array(
				'tab' => 'YAML dictionary',
				'panel' => '<p><b>File:</b> ' . Nette\Diagnostics\Helpers::editorLink($call['args'][0], $previous->getParsedLine()) . '</p>'
					. ($previous->getParsedLine() ? Nette\Diagnostics\BlueScreen::highlightFile($call['args'][0], $previous->getParsedLine()) : '')
					. '<p>' . $previous->getMessage() . ' </p>'
			);
		}
	}

}
