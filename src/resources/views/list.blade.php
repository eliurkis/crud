@extends('layouts.app')

@section('page-title')
    {!! $t['list_title'] or trans('eliurkis::crud.list_title') !!}
@stop

@section('content')
    <h2 class="sub-header">
        @if (in_array('create', $buttons))
            <a href="{{ $links['create'] }}" class="btn btn-primary pull-right">
                <i class="fas fa-plus-circle"></i> {{ $t['new'] or trans('eliurkis::crud.new') }}
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
                    <th class="field--{{ $name }}">{{ $fields[$name]['label'] or title_case(preg_replace("/[^A-Za-z0-9 ]/", ' ', $name)) }}</th>
                @endforeach
                @if ($listDisplay['action-buttons'])
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
                                {{ $row->$name or 'N/A' }}
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
                            @else
                                {{ $row->$name or 'N/A' }}
                            @endif
                        </td>
                    @endforeach
                    @if ($listDisplay['action-buttons'])
                        <td class="action-buttons" nowrap>
                            @if (Route::has($route.'.show'))
                                <a href="{{ route($route.'.show', $row->{$row->getKeyName()}) }}" class="btn-default btn btn-xs"><i class="fas fa-eye"></i> show</a>
                            @endif
                            @if (Route::has($route.'.edit'))
                                <a href="{{ route($route.'.edit', $row->{$row->getKeyName()}) }}" class="btn-primary btn btn-xs edit_element"><i class="far fa-edit"></i> edit</a>
                            @endif
                            @if (Route::has($route.'.destroy'))
                            <a href="{{ route($route.'.destroy', $row->{$row->getKeyName()}) }}"
                               class="btn-danger btn btn-xs delete_element"
                               onclick="return confirm('{{ $t['confirmation_delete'] or trans('eliurkis::crud.confirmation_delete') }}');">
                                <i class="far fa-trash-alt"></i> delete
                            </a>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="text-center">{{ $t['no_data'] or trans('eliurkis::crud.no_data') }}</td>
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
