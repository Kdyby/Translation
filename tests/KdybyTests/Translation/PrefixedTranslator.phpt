<?php

/**
 * Test: Kdyby\Translation\PrefixedTranslator.
 *
 * @testCase KdybyTests\Translation\PrefixedTranslatorTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PrefixedTranslatorTest extends TestCase
{

	public function testTranslate()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::true($prefixed instanceof Kdyby\Translation\PrefixedTranslator);
		Assert::true($prefixed instanceof Nette\Localization\ITranslator);

		Assert::same('Hello world', $prefixed->translate('hello'));
	}

}

\run(new PrefixedTranslatorTest());
