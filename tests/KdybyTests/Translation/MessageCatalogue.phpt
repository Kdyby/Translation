<?php

/**
 * Test: Kdyby\Translation\MessageCatalogue.
 *
 * @testCase KdybyTests\Translation\MessageCatalogueTest
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
class MessageCatalogueTest extends Tester\TestCase
{

	/**
	 * @var \Kdyby\Translation\MessageCatalogue
	 */
	protected $catalogue;



	protected function setUp()
	{
		$this->catalogue = new Kdyby\Translation\MessageCatalogue('cs_CZ', array(
			'front' => array(
				'homepage.hello' => 'Ahoj světe!'
			)
		));
	}



	public function testGet()
	{
		Assert::same('Ahoj světe!', $this->catalogue->get('homepage.hello', 'front'));
	}



	public function testGet_untranslated()
	{
		Assert::same("\x01", $this->catalogue->get('missing', 'front'));
		Assert::same("\x01", $this->catalogue->get('foo', 'missing'));
	}

}

\run(new MessageCatalogueTest());
