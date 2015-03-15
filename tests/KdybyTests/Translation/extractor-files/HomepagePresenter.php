<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Translation;

use Kdyby;
use Nette;
use Nette\Application\UI\Form;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class HomepagePresenter extends Nette\Application\UI\Presenter
{

	/**
	 * @return Form
	 */
	protected function createComponent()
	{
		$form = new Form();
		$form->addProtection('Invalid CSRF token');
		$form->addError("Nope!");
		$form->addText('a', $label = NULL, $cols = NULL, $maxLength = NULL);
		$form->addPassword('b', $label = NULL, $cols = NULL, $maxLength = NULL);
		$form->addTextArea('c', $label = NULL, $cols = 40, $rows = 10);
		$form->addUpload('d', $label = NULL)
			->addError("Yep!");
		$form->addHidden('e', $default = NULL);
		$form->addCheckbox('f', $caption = NULL);
		$form->addRadioList('g', $label = NULL, $items = NULL);
		$form->addSelect('h', $label = NULL, $items = NULL, $size = NULL);
		$form->addMultiSelect('i', $label = NULL, $items = NULL, $size = NULL);
		$form->addSubmit('j', $caption = NULL);
		$form->addButton('k', $caption);
		$form->addImage('l', $src = NULL, $alt = NULL)
			->addCondition($form::EQUAL, 1)
				->addRule($form::FILLED, "The image is missing!", 4);

		$form->addSubmit("send", "Submit");
		$form->onSuccess[] = $this->saveSucceeded;

		return $form;
	}



	/**
	 * @param Form $form
	 */
	public function saveSucceeded(Form $form)
	{
		$this->flashMessage("Entry with id %id% was saved", 'warning')
			->parameters = array('id' => $this->getParameter('id'));

		$this->redirect('list');
	}

}
