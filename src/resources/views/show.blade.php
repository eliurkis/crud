@extends('layouts.app')

@section('page-title')
    {!! $t[$type.'_title'] or trans('eliurkis::crud.'.$type.'_title') !!}
@stop

@section('content')
    <div id="form-manage" class="row">
        @foreach ($fields as $name => $field)
            <div class="{{ $formColsClasses[0] }} fieldtype_{{ $field['type'] }} fieldname_{{ $name }}">
                <div class="form-group">
                    <label class="{{ $formColsClasses[1] }} control-label">{{ $field['label'] or $name }}</label>
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
                        <i class="fas fa-arrow-left"></i> {{ $t['go_back'] or trans('eliurkis::crud.go_back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop
