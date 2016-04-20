<?php

/**
 * Test: Kdyby\Translation\LocaleParamResolver.
 *
 * @testCase KdybyTests\Translation\LocaleParamResolverTest
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
class LocaleSessionResolver extends TestCase
{

	public function testInvalidateLocaleOnRequest()
	{
		$container = $this->createContainer();

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->getByType('Kdyby\Translation\Translator');

		/** @var Nette\Application\Application $app */
		$app = $container->getByType('Nette\Application\Application');

		/** @var Kdyby\Translation\LocaleResolver\SessionResolver $sessionResolver */
		$sessionResolver = $container->getByType('Kdyby\Translation\LocaleResolver\SessionResolver');

		// this should fallback to default locale
		Assert::same('en', $translator->getLocale());

		// force cs locale
		$sessionResolver->setLocale('cs');

		// locale from request parameter should be ignored
		$app->onRequest($app, new Nette\Application\Request('Test', 'GET', ['action' => 'default', 'locale' => 'en']));
		Assert::same('cs', $translator->getLocale());

		// force en locale
		$sessionResolver->setLocale('en');

		// locale from request parameter should be ignored
		$app->onRequest($app, new Nette\Application\Request('Test', 'GET', ['action' => 'default', 'locale' => 'cs']));
		Assert::same('en', $translator->getLocale());
	}

}

\run(new LocaleSessionResolver());
