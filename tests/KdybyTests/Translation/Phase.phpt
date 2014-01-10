<?php

/**
 * Test: Kdyby\Translation\Phase.
 *
 * @testCase KdybyTests\Translation\PhaseTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Translation
 */

namespace KdybyTests\Translation;

use Kdyby;
use Kdyby\Translation\Phase;
use Nette;
use Nette\Application\UI\Form;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PhaseTest extends TestCase
{

	public function testCheckboxCaption()
	{
		Tester\Environment::skip('Nette\Forms\Controls\BaseControl::translate() line 5: phase must not be serialised to string before passing to translator');

		$translator = $this->createTranslator();

		$form = new Form();
		$form->setTranslator($translator);

		$check = $form->addCheckbox('useCredits', new Phase('front.orderForm.useCredits', $credits = 10));
		Assert::same('', $check->getLabelPart()->getText());
	}



	public function testValidationMessage()
	{
		$translator = $this->createTranslator();

		$form = new Form();
		$form->setTranslator($translator);

		$check = $form->addCheckbox('useCredits');

		$check->addRule(function () { return FALSE; }, new Phase('front.orderForm.useCredits', $credits = 10));
		$check->addRule(Form::FILLED, new Phase('front.orderForm.useCredits', $credits = 10));
		$form->validate([$check]);

		Assert::same(array(
			'Use 10 credits'
		), $check->getErrors());

		Assert::same('[{"op":":filled","msg":"Use 10 credits"}]', $check->getControlPart()->attrs['data-nette-rules']);
	}

}

\run(new PhaseTest());
