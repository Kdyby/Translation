<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Latte;

use Kdyby;
use Nette;
use Nette\Latte;
use Nette\Latte\MacroNode;
use Nette\Latte\PhpWriter;
use Nette\Latte\Macros\MacroSet;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslateMacros extends MacroSet
{

	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);
		/** @var TranslateMacros $me */

		$me->addMacro('_', array($me, 'macroTranslate'), array($me, 'macroTranslate'));
                $me->addMacro(
                    'translatePrefix',
                    '$presenter->translator->setPrefix(%node.args)',
                    '$presenter->translator->resetPrefix()'
                );

		return $me;
	}



	/**
	 * {_$var |modifiers}
	 * {_$var, $count |modifiers}
	 * {_"Sample message", $count |modifiers}
	 * {_some.string.id, $count |modifiers}
	 */
	public function macroTranslate(MacroNode $node, PhpWriter $writer)
	{
		if ($node->closing) {
			return $writer->write('echo %modify($template->translate(ob_get_clean()))');

		} elseif ($node->isEmpty = ($node->args !== '')) {
			if ($this->containsOnlyOneWord($node)) {
				return $writer->write('echo %modify($template->translate(%node.word))');

			} else {
				return $writer->write('echo %modify($template->translate(%node.word, %node.args))');
			}

		} else {
			return 'ob_start()';
		}
	}



	private function containsOnlyOneWord(MacroNode $node)
	{
		if (method_exists($node->tokenizer, 'fetchUntil')) {
			$result = trim($node->tokenizer->fetchUntil(',')) === trim($node->args);

		} else {
			$result = trim($node->tokenizer->joinUntil(',')) === trim($node->args);
		}

		$node->tokenizer->reset();
		return $result;
	}

}
