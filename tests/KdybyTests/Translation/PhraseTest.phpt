<?php

/**
 * Test: Kdyby\Translation\Phrase.
 *
 * @testCase
 */

namespace KdybyTests\Translation;

use Kdyby\Translation\Phrase;
use Nette\Application\UI\Form;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class PhraseTest extends \KdybyTests\Translation\TestCase
{

	public function testCheckboxCaption()
	{
		$translator = $this->createTranslator();

		$form = new Form();
		$form->setTranslator($translator);

		$check = $form->addCheckbox('useCredits', new Phrase('front.orderForm.useCredits', $credits = 10));
		Assert::same('Use 10 credits', $check->getLabelPart()->getText());
	}

	public function testValidationMessage()
	{
		$translator = $this->createTranslator();

		$form = new Form();
		$form->setTranslator($translator);

		$check = $form->addCheckbox('useCredits');

		$check->addRule(Form::FILLED, new Phrase('front.orderForm.useCredits', $credits = 10));
		$form->validate([$check]);

		Assert::same([
			'Use 10 credits',
		], $check->getErrors());

		Assert::same([['op' => ':filled', 'msg' => 'Use 10 credits']], $check->getControlPart()->attrs['data-nette-rules']);
		Assert::match('<input%A% data-nette-rules=\'[{"op":":filled","msg":"Use 10 credits"}]\'>', (string) $check->getControlPart());
	}

}

(new PhraseTest())->run();
