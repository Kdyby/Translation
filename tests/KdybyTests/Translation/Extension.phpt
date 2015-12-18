<?php

/**
 * Test: Kdyby\Translation\Extension.
 *
 * @testCase KdybyTests\Translation\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Nette;
use Psr\Log\LoggerInterface;
use Symfony;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends TestCase
{

	public function testFunctionality()
	{
		$translator = $this->createTranslator();

		Assert::true($translator instanceof Nette\Localization\ITranslator);
		Assert::true($translator instanceof Kdyby\Translation\Translator);
		Assert::true($translator instanceof Symfony\Component\Translation\Translator);

		Assert::same("Ahoj světe", $translator->translate('homepage.hello', NULL, array(), 'front', 'cs'));
		Assert::same("Hello world", $translator->translate('homepage.hello', NULL, array(), 'front', 'en'));

		Assert::same("front.not.found", $translator->translate('front.not.found'));
	}



	public function testResolvers()
	{
		$sl = $this->createContainer('resolvers.default-only');

		/** @var Kdyby\Translation\Translator $translator */
		$translator = $sl->getByType('Kdyby\Translation\Translator');

		Assert::same('cs', $translator->getLocale());
	}



	public function testLogging()
	{
		$sl = $this->createContainer('logging');

		$logger = $sl->getByType('Kdyby\Monolog\Logger');
		$logger->pushHandler($loggingHandler = new TestHandler());

		$translator = $sl->getByType('Kdyby\Translation\Translator');
		Assert::same("front.not.found", $translator->translate('front.not.found'));

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

\run(new ExtensionTest());
