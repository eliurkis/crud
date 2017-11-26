@extends('layouts.app')

@section('page-title')
    {!! $t[$type.'_title'] or trans('eliurkis::crud.'.$type.'_title') !!}
@stop

@section('content')
    @if (isset($data))
        {!! Form::model($data, ['route' => [$route.'.update', $data->id], 'class' => 'form-horizontal', 'id' => 'frm-'.$route.'-update', 'files' => true]) !!}
        {!! method_field('put') !!}
    @else
        {!! Form::open(['route' => [$route.'.store'], 'class' => 'form-horizontal', 'id' => 'frm-'.$route.'-store', 'files' => true]) !!}
    @endif
    <div id="form-manage" class="row">
        @foreach ($fields as $name => $field)
            <div class="{{ $formColsClasses[0] }} fieldtype_{{ $field['type'] }} fieldname_{{ $name }}">
                <div class="form-group">
                    <label class="{{ $formColsClasses[1] }} control-label">{{ $field['label'] or $name }}</label>
                    <div class="{{ $formColsClasses[2] }}">
                        {!! $field['html'] !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="text-center">
                    <button class="btn btn-primary" type="submit" style="margin-right: 10px;">
                        <i class="fa fa-check"></i> {{ $t['save'] or trans('eliurkis::crud.save') }}
                    </button>
                    <a href="{{ $links['index'] }}" class="btn btn-default">
                        <i class="fa fa-remove"></i> {{ $t['cancel'] or trans('eliurkis::crud.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@stop
