<?php
/**
 * Created by PhpStorm.
 * User: Azathoth
 * Date: 30. 8. 2015
 * Time: 10:30
 */

namespace Kdyby\Translation;


use Nette\DI\Compiler;
use Nette\DI\Statement;

class Helpers
{

    public static function flatten(array &$messages, array $subnode = null, $path = null)
    {
        if (null === $subnode) {
            $subnode = &$messages;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path.'.'.$key : $key;
                self::flatten($messages, $value, $nodePath);
                if (null === $path) {
                    unset($messages[$key]);
                }
            } elseif (null !== $path) {
                $messages[$path.'.'.$key] = $value;
            }
        }
    }


    /**
     * @param string|\stdClass $statement
     * @return Statement[]
     */
    public static function filterArgs($statement)
    {
        return Compiler::filterArguments(array(is_string($statement) ? new Statement($statement) : $statement));
    }

}
