<?php

namespace Eliurkis\Crud\FieldTypes;

class Date
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return '<div class="input-group date">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    '.(\Form::text($name, \Input::old($name, $value), $properties['attributes'])).'
                </div>';
    }
}
