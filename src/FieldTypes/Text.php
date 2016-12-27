<?php

namespace Eliurkis\Crud\FieldTypes;

class Text
{
    static function prepare($name, $value = null, $properties = [])
    {
        return \Form::text($name, \Input::old($name, $value), $properties['attributes']);
    }
}
