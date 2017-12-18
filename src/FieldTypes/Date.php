<?php

namespace Eliurkis\Crud\FieldTypes;

use Illuminate\Support\Facades\Input;

class Date
{
    public static function prepare($name, $value = null, $properties = [])
    {
        return '<div class="input-group date">
                    <span class="input-group-addon"><i class="far fa-calendar-alt"></i></span>
                    '.(\Form::text($name, Input::old($name, $value), $properties['attributes'])).'
                </div>';
    }
}
