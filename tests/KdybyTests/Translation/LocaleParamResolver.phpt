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
class LocaleParamResolverTest extends Tester\TestCase
{

	public function testInvalidateLocaleOnRequest()
	{
		$container = $this->createContainer();
		/** @var Kdyby\Translation\Translator $translator */
		$translator = $container->getByType('Kdyby\Translation\Translator');
		/** @var Nette\Application\Application $app */
		$app = $container->getByType('Nette\Application\Application');

		// this should fallback to default locale
		Assert::same('en', $translator->getLocale());

		$app->onRequest($app, new Nette\Application\Request('Test', 'GET', array('action' => 'default', 'locale' => 'cs')));
		Assert::same('cs', $translator->getLocale());

		$app->onRequest($app, new Nette\Application\Request('Test', 'GET', array('action' => 'default', 'locale' => 'en')));
		Assert::same('en', $translator->getLocale());
	}



	protected function createContainer()
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

\run(new LocaleParamResolverTest());
