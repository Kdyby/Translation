<?php

/**
 * Test: Kdyby\Translation\CatalogueFactory.
 *
 * @testCase
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\CatalogueFactory;
use Kdyby\Translation\FallbackResolver;
use Kdyby\Translation\Loader\NeonFileLoader;
use Kdyby\Translation\TranslationLoader;
use Kdyby\Translation\Translator;
use Mockery;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class CatalogueFactoryTest extends \KdybyTests\Translation\TestCase
{

	public function testCircularFallback()
	{
		$fallbacks = new FallbackResolver();
		$fallbacks->setFallbackLocales(['cs_CZ', 'cs']);

		$loader = new TranslationLoader();
		$loader->addLoader('neon', new NeonFileLoader());

		/** @var \Kdyby\Translation\Translator|\Mockery\MockInterface $translator */
		$translator = Mockery::mock(Translator::class);
		$translator->shouldReceive('getAvailableLocales')->andReturn(['cs_CZ', 'en_US']);

		$factory = new CatalogueFactory($fallbacks, $loader);
		$factory->addResource('neon', __DIR__ . '/lang/front.cs_CZ.neon', 'cs_CZ', 'front');
		$factory->addResource('neon', __DIR__ . '/lang/front.en_US.neon', 'en_US', 'front');

		/** @var \Symfony\Component\Translation\MessageCatalogueInterface[] $catalogues */
		$catalogues = [];
		$factory->createCatalogue($translator, $catalogues, 'cs');
		Assert::truthy(isset($catalogues['cs']));
		Assert::truthy(isset($catalogues['cs_CZ']));

		Assert::same($catalogues['cs_CZ'], $catalogues['cs']->getFallbackCatalogue());
		Assert::null($catalogues['cs_CZ']->getFallbackCatalogue());

		$factory->createCatalogue($translator, $catalogues, 'en');

		Assert::same($catalogues['en_US'], $catalogues['en']->getFallbackCatalogue());
		Assert::same($catalogues['cs_CZ'], $catalogues['en_US']->getFallbackCatalogue());

		Assert::same($catalogues['cs_CZ'], $catalogues['cs']->getFallbackCatalogue());
		Assert::null($catalogues['cs_CZ']->getFallbackCatalogue());
	}

	protected function tearDown()
	{
		Mockery::close();
	}

}

(new CatalogueFactoryTest())->run();
