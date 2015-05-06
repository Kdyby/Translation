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

	public function testPhraseTranslate()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::same('Hello world', $prefixed->translate(new Kdyby\Translation\Phrase('hello')));
	}

	public function testPhraseTranslateWithParameters()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::same('Hello John', $prefixed->translate(new Kdyby\Translation\Phrase('namedHello', array('name' => 'John'))));
	}

	public function testPhraseTranslateWithCount()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.orderForm');

		Assert::same('Use 5 credits', $prefixed->translate(new Kdyby\Translation\Phrase('useCredits', 5)));
	}

}

\run(new PrefixedTranslatorTest());
