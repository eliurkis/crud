@extends('layouts.app')

@section('page-title')
    {!! isset($t[$type.'_title']) ? __($t[$type.'_title']) : trans('eliurkis::crud.'.$type.'_title') !!}
@stop

@section('content')
    <div id="form-manage" class="row form-horizontal">
        @foreach ($fields as $name => $field)
            <div class="{{ $formColsClasses[0] }} fieldtype_{{ $field['type'] }} fieldname_{{ $name }}">
                <div class="form-group">
                    <label class="{{ $formColsClasses[1] }} control-label">{{ isset($field['label']) ? __($field['label']) : __(title_case(preg_replace("/[^A-Za-z0-9 ]/", ' ', $name))) }}</label>
                    <div class="{{ $formColsClasses[2] }}">
                        <p class="form-control-static">{!! $field['value_text'] !!}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="text-center">
                    <a href="{{ route($route.'.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> {{ isset($t['go_back']) ? __($t['go_back']) : trans('eliurkis::crud.go_back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop
