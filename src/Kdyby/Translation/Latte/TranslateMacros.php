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
use Latte;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;
use Latte\Macros\MacroSet;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslateMacros extends MacroSet
{

	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);
		/** @var TranslateMacros $me */

		$me->addMacro('_', [$me, 'macroTranslate'], [$me, 'macroTranslate']);
		$me->addMacro('translator', [$me, 'macroDomain'], [$me, 'macroDomainEnd']);

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
		if (class_exists('Latte\Runtime\FilterInfo')) { // Nette 2.4
			if ($node->closing) {
				return $writer->write('$_fi = new LR\FilterInfo(%var); echo %modifyContent($this->filters->filterContent("translate", $_fi, ob_get_clean()))', $node->context[0]);

			} elseif ($node->empty = ($node->args !== '')) {
				if ($this->containsOnlyOneWord($node)) {
					return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word))');

				} else {
					return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word, %node.args))');
				}

			} else {
				return 'ob_start(function () {})';
			}

		} else { // <= Nette 2.3
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
	}



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 */
	public function macroDomain(MacroNode $node, PhpWriter $writer)
	{
		if ($node->isEmpty) {
			throw new Latte\CompileException("Expected message prefix, none given");
		}

		$node->isEmpty = $node->isEmpty || (substr($node->args, -1) === '/');

		if (method_exists('Latte\Engine', 'addProvider')) { // Nette 2.4
			return $writer->write('$_translator = \Kdyby\Translation\PrefixedTranslator::register($template, %node.word);');
		} else {
			return $writer->write('$_translator = \Kdyby\Translation\PrefixedTranslator::register23($template, %node.word);');
		}
	}



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 */
	public function macroDomainEnd(MacroNode $node, PhpWriter $writer)
	{
		if ($node->content !== NULL) {
			return $writer->write('$_translator->unregister($template);');
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
