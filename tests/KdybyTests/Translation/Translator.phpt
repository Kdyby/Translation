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

}

\run(new TranslatorTest());
