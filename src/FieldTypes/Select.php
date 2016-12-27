<?php

namespace Eliurkis\Crud\FieldTypes;

class Select
{
    public static function prepare($name, $options = [], $value = null, $properties = [])
    {
        return \Form::select(
            $name,
            $options,
            \Input::old($name, $value),
            isset($properties['attributes']) ? $properties['attributes'] : []
        );
    }
}
