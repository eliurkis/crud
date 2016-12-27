<?php

namespace Eliurkis\Crud\FieldTypes;

class Foreign
{
    public static function prepare($name, $options = [], $value = null, $properties = [])
    {
        // Define the total of columns
        $colsSize = 12 / $properties['config']['cols'];

        $html = '<div class="row">';
        foreach ($options as $key => $option) {
            $uniqueId = uniqid('opt', true).md5(mt_rand(1, 1000));
            $html .=
                '<div class="col-md-'.$colsSize.' col-xs-12">'.
                \Form::checkbox($name.'[]', $key, in_array($key, (array) $value), ['id' => $uniqueId]).
                \Form::label($uniqueId, $option).
                '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
