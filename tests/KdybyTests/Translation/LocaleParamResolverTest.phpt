<?php

/**
 * Test: Kdyby\Translation\LocaleParamResolver.
 *
 * @testCase KdybyTests\Translation\LocaleParamResolverTest
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Translator;
use Nette\Application\Application;
use Nette\Application\Request as AppRequest;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class LocaleParamResolverTest extends \KdybyTests\Translation\TestCase
{

	public function testInvalidateLocaleOnRequest()
	{
		$container = $this->createContainer();

		/** @var \Kdyby\Translation\Translator $translator */
		$translator = $container->getByType(Translator::class);

		/** @var \Nette\Application\Application $app */
		$app = $container->getByType(Application::class);

		// this should fallback to default locale
		Assert::same('en', $translator->getLocale());

		$app->onRequest($app, new AppRequest('Test', 'GET', ['action' => 'default', 'locale' => 'cs']));
		Assert::same('cs', $translator->getLocale());

		$app->onRequest($app, new AppRequest('Test', 'GET', ['action' => 'default', 'locale' => 'en']));
		Assert::same('en', $translator->getLocale());
	}

}

(new LocaleParamResolverTest())->run();
