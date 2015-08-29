<?php

namespace Kdyby\Translation;


use Nette\DI\Compiler;
use Nette\DI\Statement;

class Helpers
{

    public static function flatten(array &$messages, array $subnode = NULL, $path = NULL)
    {
        if (NULL === $subnode) {
            $subnode = &$messages;
        }
        foreach ($subnode as $key => $value) {
            if (is_array($value)) {
                $nodePath = $path ? $path.'.'.$key : $key;
                static::flatten($messages, $value, $nodePath);
                if (NULL === $path) {
                    unset($messages[$key]);
                }
            } elseif (NULL !== $path) {
                $messages[$path.'.'.$key] = $value;
            }
        }
    }


}
