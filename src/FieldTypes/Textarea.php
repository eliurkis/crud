<?php

namespace Eliurkis\Crud\FieldTypes;

class Textarea
{
    public static function prepare($name, $properties, $value = null)
    {
        return \Form::textarea($name, \Input::old($name, $value), $properties['attributes']);
    }
}
