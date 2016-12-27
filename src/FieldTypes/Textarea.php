<?php

namespace Eliurkis\Crud\FieldTypes;

class Textarea
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return \Form::textarea($name, \Input::old($name, $value), $properties['attributes']);
    }
}
