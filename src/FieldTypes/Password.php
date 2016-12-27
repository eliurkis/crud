<?php

namespace Eliurkis\Crud\FieldTypes;

class Password
{
    
    static function prepare($name, $properties)
    {
        return \Form::password($name, $properties['attributes']);
    }

}