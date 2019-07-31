<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Latte;

use Kdyby\Translation\PrefixedTranslator;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;

class TranslateMacros extends \Latte\Macros\MacroSet
{

	use \Kdyby\StrictObjects\Scream;

	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);
		/** @var \Kdyby\Translation\Latte\TranslateMacros $me */

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

		} elseif ($node->args !== '') {
			$node->empty = TRUE;
			if ($this->containsOnlyOneWord($node)) {
				return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word))');

			} else {
				return $writer->write('echo %modify(call_user_func($this->filters->translate, %node.word, %node.args))');
			}
		}
	}

	/**
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 * @throws \Latte\CompileException for invalid domain
	 * @return string|NULL
	 */
	public function macroDomain(MacroNode $node, PhpWriter $writer)
	{
		if ($node->closing) {
			if ($node->content !== NULL && $node->content !== '') {
				return $writer->write('$_translator->unregister($this);');
			}

		} else {
			if ($node->empty) {
				throw new \Latte\CompileException('Expected message prefix, none given');
			}

			return $writer->write('$_translator = ' . PrefixedTranslator::class . '::register($this, %node.word);');
		}
	}

	/**
	 * @param \Latte\MacroNode $node
	 * @return bool
	 */
	private function containsOnlyOneWord(MacroNode $node)
	{
		$result = trim($node->tokenizer->joinUntil(',')) === trim($node->args);
		$node->tokenizer->reset();
		return $result;
	}

}
