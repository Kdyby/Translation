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
		$me->addMacro('translator', [$me, 'macroDomain'], [$me, 'macroDomain']);

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
			if (strpos($node->content, '<?php') === FALSE) {
				$value = var_export($node->content, TRUE);
				$node->content = '';
			} else {
				$node->openingCode = '<?php ob_start(function () {}) ?>' . $node->openingCode;
				$value = 'ob_get_clean()';
			}

			return $writer->write('$_fi = new LR\FilterInfo(%var); echo %modifyContent($this->filters->filterContent("translate", $_fi, %raw))', $node->context[0], $value);

		} elseif ($node->empty = ($node->args !== '')) {
			if ($this->containsOnlyOneWord($node)) {
				return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word))');

			} else {
				return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word, %node.args))');
			}
		}
	}



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string|null
	 */
	public function macroDomain(MacroNode $node, PhpWriter $writer)
	{
		if ($node->closing) {
			if ($node->content !== NULL && $node->content !== '') {
				return $writer->write('$_translator->unregister($this);');
			}

		} else {
			if ($node->empty) {
				throw new Latte\CompileException("Expected message prefix, none given");
			}

			return $writer->write('$_translator = \Kdyby\Translation\PrefixedTranslator::register($this, %node.word);');
		}
	}



	/**
	 * @param MacroNode $node
	 * @return bool
	 */
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
