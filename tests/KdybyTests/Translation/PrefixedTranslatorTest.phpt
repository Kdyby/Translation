<?php

/**
 * Test: Kdyby\Translation\PrefixedTranslator.
 *
 * @testCase KdybyTests\Translation\PrefixedTranslatorTest
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Phrase;
use Kdyby\Translation\PrefixedTranslator;
use Nette\Localization\ITranslator;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class PrefixedTranslatorTest extends \KdybyTests\Translation\TestCase
{

	public function testTranslate()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::true($prefixed instanceof PrefixedTranslator);
		Assert::true($prefixed instanceof ITranslator);

		Assert::same('Hello world', $prefixed->translate('hello'));
	}

	public function testPhraseTranslate()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::same('Hello world', $prefixed->translate(new Phrase('hello')));
	}

	public function testPhraseTranslateWithParameters()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::same('Hello John', $prefixed->translate(new Phrase('namedHello', ['name' => 'John'])));
	}

	public function testPhraseTranslateWithCount()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.orderForm');

		Assert::same('Use 5 credits', $prefixed->translate(new Phrase('useCredits', 5)));
	}

	public function testGlobalTranslate()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::same('Hello world', $prefixed->translate('//front.homepage.hello'));
	}

	public function testGlobalPhraseTranslateWithParameters()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::same('Hello John', $prefixed->translate(new Phrase('//front.homepage.namedHello', ['name' => 'John'])));
	}

	public function testGlobalPhraseTranslateWithCount()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.orderForm');

		Assert::same('Use 5 credits', $prefixed->translate(new Phrase('//front.orderForm.useCredits', 5)));
	}

	/**
	 * Reported at https://github.com/Kdyby/Translation/commit/7858654f4e68a932b09e9515996036ac44339362
	 */
	public function testBugRemovedUnnecessaryCode()
	{
		$translator = $this->createTranslator();
		$prefixed = $translator->domain('front.homepage');

		Assert::same('Hello world', $prefixed->translate('namedHello', ['name' => 'world']));
	}

}

(new PrefixedTranslatorTest())->run();
