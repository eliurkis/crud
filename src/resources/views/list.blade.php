@extends('layouts.app')

@section('page-title')
    {!! isset($t['list_title']) ? __($t['list_title']) : trans('eliurkis::crud.list_title') !!}
@stop

@section('content')
    <h2 class="sub-header">
        @if (in_array('create', $buttons))
            <a href="{{ $links['create'] }}" class="btn btn-primary pull-right">
                <i class="fas fa-plus-circle"></i> {{ isset($t['new']) ? __($t['new']) : trans('eliurkis::crud.new') }}
            </a>
        @endif
    </h2>
    <div class="row">
        <div class="col-sm-4 m-b-xs">
            @foreach($htmlFilters as $filter=>$html)
                {{ $html }}
            @endforeach
        </div>
        @if (count($searchable))
            <div class="col-sm-4 col-md-offset-4">
                {!! Form::open(['method' => 'get']) !!}
                <div class="input-group">
                    <input type="text" name="q" value="{{ $request->get('q') }}" placeholder="{{ $t['search'] or trans('eliurkis::crud.search') }}" class="input-sm form-control">
                    <span class="input-group-btn">
                    <button type="submit" class="btn btn-sm btn-primary">{{ $t['go'] or trans('eliurkis::crud.go') }}</button>
                    <a href="{{ route($route.'.index') }}" class="btn btn-sm btn-success">{{ $t['reset'] or trans('eliurkis::crud.reset') }}</a>
                </span>
                </div>
                @if ($request->get('filter'))
                    @foreach($request->get('filter') as $filter => $value)
                        <input type="hidden" name="filter[{{ $filter }}]" value="{{ $value }}">
                    @endforeach
                @endif
                {!! Form::close() !!}
            </div>
        @endif
    </div>
    <div class="table-responsive {{ $route }}-module">
        <table id="table-data" class="footable table table-striped table-hover" data-page-size="20">
            <thead>
            <tr>
                @foreach($columns as $name)
                    <th class="field--{{ $name }}">{!! isset($fields[$name]['label']) ? __($fields[$name]['label']) : __(title_case(preg_replace("/[^A-Za-z0-9 ]/", ' ', $name))) !!}</th>
                @endforeach
                @if ($actions)
                    <th class="no-sort action-buttons" nowrap>{{ trans('eliurkis::crud.action') }}</th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $name)
                        <td>
                            @if (!isset($fields[$name]))
                                {!! $row->$name or 'N/A' !!}
                            @elseif ($fields[$name]['type'] == 'select')
                                @if (isset($fields[$name]['config']['options']) && count($fields[$name]['config']['options']))
                                    {{ $fields[$name]['config']['options'][$row->$name] or 'N/A' }}
                                @else
                                    {{ $row->{$fields[$name]['config']['rel']}->{$fields[$name]['config']['field_value']} or 'N/A' }}
                                @endif
                            @elseif ($fields[$name]['type'] == 'date' && is_object($row->$name))
                                {{ !empty($row->$name) && $row->$name->diff(Carbon::now())->format('%y') != date('Y') ?  $row->$name->format('m/d/Y') : 'N/A' }}
                            @elseif ($fields[$name]['type'] == 'datetime' && is_object($row->$name))
                                {{ !empty($row->$name) && $row->$name->diff(Carbon::now())->format('%y') != date('Y') ?  $row->$name->format('m/d/Y h:ia') : 'N/A' }}
                            @elseif (substr($fields[$name]['type'], 0, 4) == 'file' && $row->getMedia($name)->last())
                                <a href="{{ route($route.'.download', [$row->id, $name]) }}" target="_blank">
                                    {!! isset($fields[$name]['link_name'])? $fields[$name]['link_name'] : 'download' !!}
                                </a>
                            @else
                                {!! !empty($row->$name) ? nl2br($row->$name) : 'N/A' !!}
                            @endif
                        </td>
                    @endforeach
                    @if ($actions)
                        <td class="action-buttons" nowrap>
                            @foreach($actions as $actionCallback)
                                {!! $actionCallback($row, $route) !!}
                            @endforeach
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="text-center">{{ isset($t['no_data']) ? __($t['no_data']) : trans('eliurkis::crud.no_data') }}</td>
                </tr>
            @endforelse
            </tbody>
            @if ($rows && $paginate)
                <tfoot>
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="text-center">
                        <ul class="pagination">{!! $rows->links() !!}</ul>
                    </td>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
@stop
