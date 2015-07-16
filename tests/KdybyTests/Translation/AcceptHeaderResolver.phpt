<?php

/**
 * Test: Kdyby\Translation\AcceptHeaderResolver.
 *
 * @testCase KdybyTests\Translation\AcceptHeaderResolverTest
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
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class AcceptHeaderResolverTest extends TestCase
{

	public function testResolve()
	{
		Assert::null($this->resolve(null, ['xx']));
		Assert::null($this->resolve('en', []));
		Assert::null($this->resolve('garbage', ['en']));
		Assert::same('en', $this->resolve('en, cs', ['en', 'cs']));
		Assert::same('en', $this->resolve('en, cs', ['cs', 'en']));
		Assert::same('en-gb', $this->resolve('da, en-gb;q=0.8, en;q=0.7', ['en', 'en-gb']));
		Assert::same('en', $this->resolve('da, en-gb;q=0.8, en;q=0.7', ['en']));
		Assert::same('en', $this->resolve('da, en_gb', ['en']));
		Assert::same('en-gb', $this->resolve('da, en_gb', ['en', 'en-gb']));
	}



	protected function resolve($header, array $locales)
	{
		$httpRequest = \Mockery::mock('Nette\Http\IRequest');
		$httpRequest->shouldReceive('getHeader')->with('Accept-Language')->andReturn($header);

		$acceptHeaderResolver = new Kdyby\Translation\LocaleResolver\AcceptHeaderResolver($httpRequest);

		$translator = \Mockery::mock('Kdyby\Translation\Translator');
		$translator->shouldReceive('getAvailableLocales')->andReturn($locales);

		return $acceptHeaderResolver->resolve($translator);
	}



	protected function tearDown()
	{
		\Mockery::close();
	}

}

\run(new AcceptHeaderResolverTest());
