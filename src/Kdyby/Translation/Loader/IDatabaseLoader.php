<?php
/**
 * Created by PhpStorm.
 * User: Azathoth
 * Date: 24. 8. 2015
 * Time: 13:01
 */

namespace Kdyby\Translation\Loader;

use Kdyby\Translation\Translator;
use Symfony\Component\Translation\Loader\LoaderInterface;

interface IDatabaseLoader extends LoaderInterface
{

    public function getLocales();

    public function addResources(Translator $translator);
}