<?php

namespace Eliurkis\Crud\FieldTypes;

class Password
{
    static function prepare($name, $value = null, $properties = [])
    {
        return \Form::password($name, $properties['attributes']);
    }
}
