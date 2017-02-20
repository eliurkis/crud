@extends('layouts.app')

@section('content')
    <h2 class="sub-header">
        {{ $t['list_title'] or trans('eliurkis::crud.list_title') }}
        @if (in_array('create', $buttons))
            <a href="{{ $links['create'] }}" class="btn btn-primary pull-right">
                <i class="fa fa-plus-circle"></i> {{ $t['new'] or trans('eliurkis::crud.new') }}
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
                    <th class="field--{{ $name }}">{{ $fields[$name]['label'] or $name }}</th>
                @endforeach
                <th class="no-sort action-buttons" nowrap>{{ trans('eliurkis::crud.action') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $name)
                        <td>
                        @if ($fields[$name]['type'] == 'select')
                            @if (isset($fields[$name]['config']['options']) && count($fields[$name]['config']['options']))
                                {{ $fields[$name]['config']['options'][$row->$name] or 'N/A' }}
                            @else
                                {{ $row->{$fields[$name]['config']['rel']}->{$fields[$name]['config']['field_value']} or 'N/A' }}
                            @endif
                        @elseif ($fields[$name]['type'] == 'date')
                            {{ $row->$name->diff(Carbon::now())->format('%y') != date('Y') ?  $row->$name->format('m/d/Y') : 'N/A' }}
                        @elseif ($fields[$name]['type'] == 'datetime')
                            {{ $row->$name->diff(Carbon::now())->format('%y') != date('Y') ?  $row->$name->format('m/d/Y h:ia') : 'N/A' }}
                        @else
                            {{ $row->$name or 'N/A' }}
                        @endif
                        </td>
                    @endforeach
                    <td class="action-buttons" nowrap>
                        <a href="{{ route($route.'.edit', $row->{$row->getKeyName()}) }}" class="btn-primary btn btn-xs edit_element"><i class="fa fa-pencil-square-o"></i> edit</a>
                        <a href="{{ route($route.'.destroy', $row->{$row->getKeyName()}) }}"
                           class="btn-danger btn btn-xs delete_element"
                           onclick="return confirm('{{ $t['confirmation_delete'] or trans('eliurkis::crud.confirmation_delete') }}');">
                            <i class="fa fa-trash-o"></i> delete
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="text-center">{{ $t['no_data'] or trans('eliurkis::crud.no_data') }}</td>
                </tr>
            @endforelse
            </tbody>
            @if ($paginate)
            <tfoot>
                <tr>
                    <td colspan="8">
                        <ul class="pagination pull-right">{!! $rows->links() !!}</ul>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
@stop
