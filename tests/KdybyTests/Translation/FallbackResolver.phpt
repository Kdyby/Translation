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
class FallbackResolverTest extends TestCase
{

	public function testCompute()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->getByType('Nette\Localization\ITranslator');
		$translator->setFallbackLocales(['cs_CZ', 'cs']);

		/** @var Kdyby\Translation\FallbackResolver $fallbackResolver */
		$fallbackResolver = $container->getByType('Kdyby\Translation\FallbackResolver');

		Assert::same(['cs_CZ'], $fallbackResolver->compute($translator, 'cs'));
		Assert::same(['sk_SK', 'cs_CZ', 'cs'], $fallbackResolver->compute($translator, 'sk'));
		Assert::same(['sk', 'cs_CZ', 'cs'], $fallbackResolver->compute($translator, 'sk_SK'));
		Assert::same(['en_US', 'cs_CZ', 'cs'], $fallbackResolver->compute($translator, 'en'));
		Assert::same(['en', 'cs_CZ', 'cs'], $fallbackResolver->compute($translator, 'en_US'));
	}

}

\run(new FallbackResolverTest());
