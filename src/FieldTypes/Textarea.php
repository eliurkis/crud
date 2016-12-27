<?php

namespace Eliurkis\Crud\FieldTypes;

class Textarea
{
    static function prepare($name, $value = null, $properties = [])
    {
        return \Form::textarea($name, \Input::old($name, $value), $properties['attributes']);
    }
}
