<?php

namespace Eliurkis\Crud\FieldTypes;

use Illuminate\Support\Facades\Input;

class Textarea
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return \Form::textarea($name, Input::old($name, $value), $properties['attributes']);
    }
}
