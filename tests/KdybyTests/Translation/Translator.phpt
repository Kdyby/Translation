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
		$translator = $container->createInstance('Kdyby\Translation\Translator', array(
			'localeResolver' => $container->getService('translation.userLocaleResolver'),
			'loader' => $loader
		));

		Assert::same(array(), $loader->getLoaders());

		$translator->addLoader('neon', $neonLoader = new Kdyby\Translation\Loader\NeonFileLoader());
		Assert::same(array('neon' => $neonLoader), $loader->getLoaders());
	}



	public function testAddResource()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\CatalogueFactory $catalogueFactory */
		$catalogueFactory = $container->createInstance('Kdyby\Translation\CatalogueFactory');

		/** @var Kdyby\Translation\CatalogueCompiler $catalogueCompiler */
		$catalogueCompiler = $container->createInstance('Kdyby\Translation\CatalogueCompiler', array(
			'catalogueFactory' => $catalogueFactory,
		));

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->createInstance('Kdyby\Translation\Translator', array(
			'catalogueCompiler' => $catalogueCompiler,
			'localeResolver' => $container->getService('translation.userLocaleResolver')
		));

		Assert::same(array(), $catalogueFactory->getResources());

		$translator->addResource('neon', __DIR__ . '/files/front.cs_CZ.neon', 'cs_CZ', 'front');

		Assert::same(array(
			__DIR__ . '/files/front.cs_CZ.neon'
		), $catalogueFactory->getResources());
	}



	public function testAvailableLocales()
	{
		$translator = $this->createTranslator();
		Assert::same(array('cs_CZ', 'en_US', 'sk_SK'), $translator->getAvailableLocales());
	}



	public function dataWhitelistRegexp()
	{
		return array(
			array('cs', TRUE),
			array('cs_CZ', TRUE),
			array('en', TRUE),
			array('en_US', TRUE),
			array('en_GB', TRUE),
			array('de', TRUE),
			array('fr', FALSE),
			array('hu', FALSE),
			array('eu', FALSE),
			array('ru', FALSE),
		);
	}



	/**
	 * @dataProvider dataWhitelistRegexp
	 */
	public function testWhitelistRegexp($locale, $isMatching)
	{
		$regexp = Kdyby\Translation\Translator::buildWhitelistRegexp(array('cs', 'en', 'de'));

		Assert::same($isMatching, (bool) preg_match($regexp, $locale));
	}



	public function testNonIdTranslations()
	{
		$translator = $this->createTranslator();
		$translator->setLocale('cs');

		Assert::same("Ahoj světe", $translator->translate('Hello World')); // default domain is 'messages'
	}

}

\run(new TranslatorTest());
