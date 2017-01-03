<?php

namespace Eliurkis\Crud\FieldTypes;

use Illuminate\Support\Facades\Input;

class Select
{
    public static function prepare($name, $options = [], $value = null, $properties = [])
    {
        return \Form::select(
            $name,
            $options,
            Input::old($name, $value),
            isset($properties['attributes']) ? $properties['attributes'] : []
        );
    }
}
