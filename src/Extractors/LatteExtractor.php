<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Extractors;

use Latte\MacroTokens;
use Latte\Parser;
use Latte\PhpWriter;
use Nette\Utils\Finder;
use Symfony\Component\Translation\MessageCatalogue;

class LatteExtractor implements \Symfony\Component\Translation\Extractor\ExtractorInterface
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * {@inheritDoc}
	 */
	public function extract($directory, MessageCatalogue $catalogue)
	{
		foreach (Finder::findFiles('*.latte', '*.phtml')->from($directory) as $file) {
			$this->extractFile($file, $catalogue);
		}
	}

	/**
	 * Extracts translation messages from a file to the catalogue.
	 *
	 * @param string $file The path to look into
	 * @param \Symfony\Component\Translation\MessageCatalogue $catalogue The catalogue
	 */
	public function extractFile($file, MessageCatalogue $catalogue)
	{
		$buffer = NULL;
		$parser = new Parser();

		foreach ($tokens = $parser->parse(file_get_contents($file)) as $token) {
			if ($token->type !== $token::MACRO_TAG || !in_array($token->name, ['_', '/_'], TRUE)) {
				if ($buffer !== NULL) {
					$buffer .= $token->text;
				}

				continue;
			}

			if ($token->name === '/_' || ($token->name === '_' && $token->closing === TRUE)) {
				if ($buffer !== NULL) {
					$catalogue->set(($this->prefix ? $this->prefix . '.' : '') . $buffer, $buffer);
					$buffer = NULL;
				}

			} elseif ($token->name === '_' && empty($token->value)) {
				$buffer = '';

			} else {
				$args = new MacroTokens($token->value);
				$writer = new PhpWriter($args, $token->modifiers);

				$message = $writer->write('%node.word');
				if (in_array(substr(trim($message), 0, 1), ['"', '\''], TRUE)) {
					$message = substr(trim($message), 1, -1);
				}

				$catalogue->set(($this->prefix ? $this->prefix . '.' : '') . $message, $message);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

}
