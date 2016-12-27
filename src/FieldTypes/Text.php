<?php

namespace Eliurkis\Crud\FieldTypes;

class Text
{
    
    static function prepare($name, $properties, $value = null)
    {
        return \Form::text($name, \Input::old($name, $value), $properties['attributes']);
    }

}