<?php

/**
 * Test: Kdyby\Translation\FallbackResolver.
 *
 * @testCase KdybyTests\Translation\FallbackResolverTest
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
class FallbackResolverTest extends Tester\TestCase
{

	public function testCompute()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->getByType('Nette\Localization\ITranslator');
		$translator->setFallbackLocales(array('cs_CZ', 'cs'));

		/** @var Kdyby\Translation\FallbackResolver $fallbackResolver */
		$fallbackResolver = $container->getByType('Kdyby\Translation\FallbackResolver');

		Assert::same(array('cs_CZ'), $fallbackResolver->compute($translator, 'cs'));
		Assert::same(array('sk_SK', 'cs_CZ', 'cs'), $fallbackResolver->compute($translator, 'sk'));
		Assert::same(array('sk', 'cs_CZ', 'cs'), $fallbackResolver->compute($translator, 'sk_SK'));
		Assert::same(array('en_US', 'cs_CZ', 'cs'), $fallbackResolver->compute($translator, 'en'));
		Assert::same(array('en', 'cs_CZ', 'cs'), $fallbackResolver->compute($translator, 'en_US'));
	}



	private function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('appDir' => __DIR__));
		Kdyby\Translation\DI\TranslationExtension::register($config);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$container = $config->createContainer();
		/** @var \Nette\DI\Container|\SystemContainer $container */

		return $container;
	}

}

\run(new FallbackResolverTest());
