<?php

/**
 * Test: Kdyby\Translation\Translator.
 *
 * @testCase KdybyTests\Translation\TranslatorTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Symfony;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TranslatorTest extends TestCase
{

	public function testDefaultLocale()
	{
		$translator = $this->createTranslator();

		$translator->setDefaultLocale('cs');
		Assert::same('cs', $translator->getDefaultLocale());

		$translator->setDefaultLocale('en');
		Assert::same('en', $translator->getDefaultLocale());
	}



	public function testDefaultLocale_invalid()
	{
		$translator = $this->createTranslator();

		Assert::exception(function () use ($translator) {
			$translator->setDefaultLocale('cs$');
		}, 'InvalidArgumentException');

		Assert::exception(function () use ($translator) {
			$translator->setDefaultLocale('cs"');
		}, 'InvalidArgumentException');
	}



	public function testAddLoader()
	{
		$container = $this->createContainer();

		$loader = new Kdyby\Translation\TranslationLoader();

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->createInstance(Kdyby\Translation\Translator::class, [
			'localeResolver' => $container->getService('translation.userLocaleResolver'),
			'loader' => $loader
		]);

		Assert::same([], $loader->getLoaders());

		$translator->addLoader('neon', $neonLoader = new Kdyby\Translation\Loader\NeonFileLoader());
		Assert::same(['neon' => $neonLoader], $loader->getLoaders());
	}



	public function testAddResource()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\CatalogueFactory $catalogueFactory */
		$catalogueFactory = $container->createInstance(Kdyby\Translation\CatalogueFactory::class);

		/** @var Kdyby\Translation\CatalogueCompiler $catalogueCompiler */
		$catalogueCompiler = $container->createInstance(Kdyby\Translation\CatalogueCompiler::class, [
			'catalogueFactory' => $catalogueFactory,
		]);

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->createInstance(Kdyby\Translation\Translator::class, [
			'catalogueCompiler' => $catalogueCompiler,
			'localeResolver' => $container->getService('translation.userLocaleResolver')
		]);

		Assert::same([], $catalogueFactory->getResources());

		$translator->addResource('neon', __DIR__ . '/files/front.cs_CZ.neon', 'cs_CZ', 'front');

		Assert::same([
			__DIR__ . '/files/front.cs_CZ.neon'
		], $catalogueFactory->getResources());
	}



	public function testAvailableLocales()
	{
		$translator = $this->createTranslator();
		Assert::same(['cs_CZ', 'en_US', 'sk_SK'], $translator->getAvailableLocales());
	}



	public function dataWhitelistRegexp()
	{
		return [
			['cs', TRUE],
			['cs_CZ', TRUE],
			['en', TRUE],
			['en_US', TRUE],
			['en_GB', TRUE],
			['de', TRUE],
			['fr', FALSE],
			['hu', FALSE],
			['eu', FALSE],
			['ru', FALSE],
		];
	}



	/**
	 * @dataProvider dataWhitelistRegexp
	 */
	public function testWhitelistRegexp($locale, $isMatching)
	{
		$regexp = Kdyby\Translation\Translator::buildWhitelistRegexp(['cs', 'en', 'de']);

		Assert::same($isMatching, (bool) preg_match($regexp, $locale));
	}



	public function testNonIdTranslations()
	{
		$translator = $this->createTranslator();
		$translator->setLocale('cs');

		Assert::same("Ahoj světe", $translator->translate('Hello World')); // default domain is 'messages'
	}


	public function testAbsoluteTranslations()
	{
		$translator = $this->createTranslator();
		$translator->setLocale('cs');

		Assert::same("Ahoj světe", $translator->translate('//front.homepage.hello'));
	}



	public function testTranslatingAbsoluteMessageWithDomainIsNotSupported()
	{
		$translator = $this->createTranslator();

		Assert::exception(function () use ($translator) {
			$translator->translate('//homepage.hello', NULL, [], 'front');
		}, Kdyby\Translation\InvalidArgumentException::class, 'Providing domain "front" while also having the message "//homepage.hello" absolute is not supported');
	}

}

(new TranslatorTest())->run();
