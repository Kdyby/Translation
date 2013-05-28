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
use Kdyby\Translation\Translator;
use Nette;



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
	 * @param Translator $translator
	 */
	public function __construct(Translator $translator)
	{
		$this->translator = $translator;
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

		return empty($this->untranslated) ? '' :
			'<h1>Missing translations: ' .  count($unique) . '</h1>' .
			'<div class="nette-inner kdyby-TranslationPanel"><table><tr><th>Message</th></tr>' . $s . '</table></div>';
	}



	public function markUntranslated($id)
	{
		$this->untranslated[] = $id;
	}



	public function choiceError(\Exception $e)
	{
		$this->untranslated[] = $e;
	}



	/**
	 * @param Translator $translator
	 * @return Panel
	 */
	public static function register(Translator $translator)
	{
		$panel = new static($translator);
		/** @var Panel $panel */
		$translator->injectPanel($panel);
		Nette\Diagnostics\Debugger::$bar->addPanel($panel, 'kdyby.translation');
		return $panel;
	}

}
