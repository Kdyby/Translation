<?php

/**
 * Test: Kdyby\Translation\Extension.
 *
 * @testCase KdybyTests\Translation\ExtensionTest
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Translator as KdybyTranslator;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Nette\Localization\ITranslator;
use Symfony\Component\Translation\Translator as SymfonyTranslator;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class ExtensionTest extends \KdybyTests\Translation\TestCase
{

	public function testFunctionality()
	{
		$translator = $this->createTranslator();

		Assert::true($translator instanceof ITranslator);
		Assert::true($translator instanceof KdybyTranslator);
		Assert::true($translator instanceof SymfonyTranslator);

		Assert::same('Ahoj světe', $translator->translate('homepage.hello', NULL, [], 'front', 'cs'));
		Assert::same('Hello world', $translator->translate('homepage.hello', NULL, [], 'front', 'en'));

		Assert::same('front.not.found', $translator->translate('front.not.found'));
	}

	public function testResolvers()
	{
		$sl = $this->createContainer('resolvers.default-only');

		/** @var \Kdyby\Translation\Translator $translator */
		$translator = $sl->getByType(KdybyTranslator::class);

		Assert::same('cs', $translator->getLocale());
	}

	public function testLoaders()
	{
		$sl = $this->createContainer('loaders.custom');

		/** @var \Kdyby\Translation\TranslationLoader $loader */
		$loader = $sl->getService('translation.loader');

		$loaders = $loader->getLoaders();
		Assert::count(2, $loaders);
		Assert::true(array_key_exists('php', $loaders));
		Assert::true(array_key_exists('neon', $loaders));
		Assert::false(array_key_exists('po', $loaders));
		Assert::false(array_key_exists('dat', $loaders));
		Assert::false(array_key_exists('csv', $loaders));
	}

	public function testLogging()
	{
		$sl = $this->createContainer('logging');

		$logger = $sl->getByType(Logger::class);
		$logger->pushHandler($loggingHandler = new TestHandler());

		$translator = $sl->getByType(KdybyTranslator::class);
		Assert::same('front.not.found', $translator->translate('front.not.found'));

		list($record) = $loggingHandler->getRecords();
		Assert::same('Missing translation', $record['message']);
		Assert::same(Logger::NOTICE, $record['level']);
		Assert::same('translation', $record['channel']);
		Assert::same([
			'message' => 'front.not.found',
			'domain' => 'front',
			'locale' => 'en',
		], $record['context']);
	}

}

(new ExtensionTest())->run();
