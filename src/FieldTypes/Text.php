<?php

namespace Eliurkis\Crud\FieldTypes;

use Illuminate\Support\Facades\Input;

class Text
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return \Form::text($name, Input::old($name, $value), $properties['attributes']);
    }
}
