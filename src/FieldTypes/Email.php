<?php

namespace Eliurkis\Crud\FieldTypes;

use Illuminate\Support\Facades\Input;

class Email
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return \Form::email($name, Input::old($name, $value), $properties['attributes']);
    }
}
