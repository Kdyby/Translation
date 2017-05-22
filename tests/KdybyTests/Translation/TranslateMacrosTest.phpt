<?php

/**
 * Test: Kdyby\Translation\TranslateMacros.
 *
 * @testCase
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Phrase;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Nette\Application\UI\ITemplateFactory;
use Nette\Localization\ITranslator;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class TranslateMacrosTest extends \KdybyTests\Translation\TestCase
{

	/** @var \Kdyby\Translation\Translator */
	private $translator;

	/** @var \Nette\Bridges\ApplicationLatte\Template */
	private $template;

	protected function setUp()
	{
		parent::setUp();

		$container = $this->createContainer();

		/** @var \Kdyby\Translation\Translator $translator */
		$this->translator = $container->getByType(ITranslator::class);
		$this->translator->setFallbackLocales(['cs_CZ', 'cs']);
		$this->translator->setLocale('cs');

		$this->template = $container->getByType(ITemplateFactory::class)
			->createTemplate(new ControlMock());
	}

	public function testRenderTranslate()
	{
		$this->template->setFile(__DIR__ . '/data/files/Homepage.default.latte');

		Assert::same('Ahoj %name%
Ahoj Peter
Ahoj Peter

Ahoj %name%
Ahoj Peter
Ahoj Peter

front.missingKey.namedHello
front.missingKey.namedHello
front.missingKey.namedHello

Helloes %name%
Helloes Peter
Hello Peter|Helloes Peter

front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting' . "\n", $this->template->__toString());
	}

	public function testRenderTranslateNoescape()
	{
		$this->template->setFile(__DIR__ . '/data/files/Article.noescape.latte');

		Assert::same('Ahoj &lt;b&gt;%name%&lt;/b&gt;
Ahoj &lt;b&gt;Peter&lt;/b&gt;
Ahoj &lt;b&gt;Peter&lt;/b&gt;

Ahoj &lt;b&gt;%name%&lt;/b&gt;
Ahoj &lt;b&gt;Peter&lt;/b&gt;
Ahoj &lt;b&gt;Peter&lt;/b&gt;

front.missingKey.namedHello
front.missingKey.namedHello
front.missingKey.namedHello

Helloes &lt;i&gt;%name%&lt;/i&gt;
Helloes &lt;i&gt;Peter&lt;/i&gt;
Hello &lt;i&gt;Peter&lt;/i&gt;|Helloes &lt;i&gt;Peter&lt;/i&gt;

front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting

<hr>

Ahoj <b>%name%</b>
Ahoj <b>Peter</b>
Ahoj <b>Peter</b>

Ahoj <b>%name%</b>
Ahoj <b>Peter</b>
Ahoj <b>Peter</b>

front.missingKey.namedHello
front.missingKey.namedHello
front.missingKey.namedHello

Helloes <i>%name%</i>
Helloes <i>Peter</i>
Hello <i>Peter</i>|Helloes <i>Peter</i>

front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting' . "\n", $this->template->__toString());
	}

	public function testRenderTranslatePrefixed()
	{
		$this->template->setFile(__DIR__ . '/data/files/Order.default.latte');

		Assert::match('
Ahoj %name%
Ahoj Peter
Ahoj Peter

Ahoj %name%
Ahoj Peter
Ahoj Peter

%A?%
front.missingKey.namedHello
front.missingKey.namedHello
front.missingKey.namedHello

%A?%
Helloes %name%
Helloes Peter
Hello Peter|Helloes Peter

%A?%
front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting
front.missingKey.namedHelloCounting' . "\n", $this->template->__toString());
	}

	public function testPhraseInFlashMessage()
	{
		$logger = new Logger('translator');
		$handler = new TestHandler();
		$logger->pushHandler($handler);
		$this->translator->injectPsrLogger($logger);

		$this->template->setFile(__DIR__ . '/data/files/flashMessage.latte');
		$this->template->setParameters([
			'flashes' => unserialize(serialize([
				(object) [
					'message' => new Phrase('front.flashes.weSentPasswordRequest', ['email' => 'filip@prochazka.su']),
					'type' => 'info',
				],
				(object) [
					'message' => new Phrase('front.weSentPasswordRequest', ['email' => 'filip@prochazka.su']),
					'type' => 'info',
				],
			])),
		]);

		$expected = "\tHeslo vám bylo zasláno na email filip@prochazka.su\n" .
			"\tHeslo vám pošleme na email filip@prochazka.su\n\n";

		Assert::match($expected, $this->template->__toString());

		Assert::same([], $handler->getRecords());
	}

}

(new TranslateMacrosTest())->run();
