<?php

/**
 * Test: Kdyby\Translation\Translator.
 *
 * @testCase
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\CatalogueCompiler;
use Kdyby\Translation\CatalogueFactory;
use Kdyby\Translation\Loader\NeonFileLoader;
use Kdyby\Translation\TranslationLoader;
use Kdyby\Translation\Translator;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class TranslatorTest extends \KdybyTests\Translation\TestCase
{

	public function testDefaultLocale()
	{
		$translator = $this->createTranslator();

		$translator->setDefaultLocale('cs');
		Assert::same('cs', $translator->getDefaultLocale());

		$translator->setDefaultLocale('en');
		Assert::same('en', $translator->getDefaultLocale());
	}

	public function testDefaultLocaleInvalid()
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

		$loader = new TranslationLoader();

		/** @var \Kdyby\Translation\Translator $translator */
		$translator = $container->createInstance(Translator::class, [
			'localeResolver' => $container->getService('translation.userLocaleResolver'),
			'loader' => $loader,
		]);

		Assert::same([], $loader->getLoaders());

		$neonLoader = new NeonFileLoader();
		$translator->addLoader('neon', $neonLoader);
		Assert::same(['neon' => $neonLoader], $loader->getLoaders());
	}

	public function testAddResource()
	{
		$container = $this->createContainer();

		/** @var \Kdyby\Translation\CatalogueFactory $catalogueFactory */
		$catalogueFactory = $container->createInstance(CatalogueFactory::class);

		/** @var \Kdyby\Translation\CatalogueCompiler $catalogueCompiler */
		$catalogueCompiler = $container->createInstance(CatalogueCompiler::class, [
			'catalogueFactory' => $catalogueFactory,
		]);

		/** @var \Kdyby\Translation\Translator $translator */
		$translator = $container->createInstance(Translator::class, [
			'catalogueCompiler' => $catalogueCompiler,
			'localeResolver' => $container->getService('translation.userLocaleResolver'),
		]);

		Assert::same([], $catalogueFactory->getResources());

		$translator->addResource('neon', __DIR__ . '/data/files/front.cs_CZ.neon', 'cs_CZ', 'front');

		Assert::same([
			__DIR__ . '/data/files/front.cs_CZ.neon',
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
		$regexp = Translator::buildWhitelistRegexp(['cs', 'en', 'de']);

		Assert::same($isMatching, (bool) preg_match($regexp, $locale));
	}

	public function testNonIdTranslations()
	{
		$translator = $this->createTranslator();
		$translator->setLocale('cs');

		Assert::same('Ahoj světe', $translator->translate('Hello World')); // default domain is 'messages'
	}

	public function testAbsoluteTranslations()
	{
		$translator = $this->createTranslator();
		$translator->setLocale('cs');

		Assert::same('Ahoj světe', $translator->translate('//front.homepage.hello'));
	}

	public function testTranslatingAbsoluteMessageWithDomainIsNotSupported()
	{
		$translator = $this->createTranslator();

		Assert::exception(function () use ($translator) {
			$translator->translate('//homepage.hello', NULL, [], 'front');
		}, \Kdyby\Translation\InvalidArgumentException::class, 'Providing domain "front" while also having the message "//homepage.hello" absolute is not supported');
	}

}

(new TranslatorTest())->run();
