<?php

namespace Eliurkis\Crud\FieldTypes;

class FileLegacy
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return \Form::file($name, $properties['attributes']);
    }
}
