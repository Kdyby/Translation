<?php

namespace Kdyby\Translation;


class MessageCatalogue extends \Symfony\Component\Translation\MessageCatalogue
{

	/**
	 * {@inheritdoc}
	 */
	public function get($id, $domain = 'messages')
	{
		if ($this->defines($id, $domain)) {
			return parent::get($id, $domain);
		}

		if ($this->getFallbackCatalogue() !== NULL) {
			return $this->getFallbackCatalogue()->get($id, $domain);
		}

		return "\x01";
	}

}
