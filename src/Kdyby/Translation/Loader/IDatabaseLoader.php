<?php

namespace Kdyby\Translation\Loader;

use Kdyby\Translation\Translator;
use Symfony\Component\Translation\Loader\LoaderInterface;

interface IDatabaseLoader extends LoaderInterface
{

	public function getLocales();

	public function addResources(Translator $translator);
}
