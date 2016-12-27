<?php

namespace Eliurkis\Crud\FieldTypes;

class Date
{
    
    static function prepare($name, $properties, $value = null)
    {
        return '<div class="input-group date">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    '.(\Form::text($name, \Input::old($name, $value), $properties['attributes'])).'
                </div>';
    }

}