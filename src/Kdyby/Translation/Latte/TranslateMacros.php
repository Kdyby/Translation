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
use Latte\CompileException;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;
use Latte\Macros\MacroSet;
use Nette;


if (!class_exists('Latte\CompileException')) {
	class_alias('Nette\Latte\CompileException', 'Latte\CompileException');
	class_alias('Nette\Latte\Compiler', 'Latte\Compiler');
	class_alias('Nette\Latte\MacroNode', 'Latte\MacroNode');
	class_alias('Nette\Latte\PhpWriter', 'Latte\PhpWriter');
	class_alias('Nette\Latte\Macros\MacroSet', 'Latte\Macros\MacroSet');
}


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslateMacros extends MacroSet
{

	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);
		/** @var TranslateMacros $me */

		$me->addMacro('_', array($me, 'macroTranslate'), array($me, 'macroTranslate'));
		$me->addMacro('translator', array($me, 'macroDomain'), array($me, 'macroDomainEnd'));

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



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 */
	public function macroDomain(MacroNode $node, PhpWriter $writer)
	{
		if ($node->isEmpty) {
			throw new CompileException("Expected message prefix, none given");
		}

		$node->isEmpty = $node->isEmpty || (substr($node->args, -1) === '/');
		return $writer->write('$_translator = \Kdyby\Translation\PrefixedTranslator::register($template, %node.word);');
	}



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 */
	public function macroDomainEnd(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$_translator->unregister($template);');
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
