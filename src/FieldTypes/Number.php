<?php

namespace Eliurkis\Crud\FieldTypes;

use Illuminate\Support\Facades\Input;

class Number
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return \Form::number($name, Input::old($name, $value), $properties['attributes']);
    }
}
