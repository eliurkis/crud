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
    <table id="table-data" class="footable table table-striped table-hover {{ $route }}-module" data-page-size="20">
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
        </tbody>
    </table>
@stop

@push('scripts')
    <script>
        $(function () {
            if (!$.fn.dataTable.isDataTable('#table-data')) {
                var crudDataTable = {
                    generateEditLink: function (id) {
                        return '{{ route($route.'.edit', '*') }}'.replace('*', id);
                    },

                    generateDeleteLink: function (id) {
                        return '{{ route($route.'.destroy', '*') }}'.replace('*', id);
                    }
                };

                var table = $('#table-data').DataTable({
                    'order': [
                        @foreach ($orderBy as $field => $order)
                            [{{ array_search($field, $columns) }}, '{{ strtolower($order) }}'],
                        @endforeach
                    ],
                    'columns': [
                        @foreach ($columns as $col)
                        {'data': '{{ $col }}'},
                        @endforeach
                        {
                            'data': function (row) {
                                return '<a href="' + crudDataTable.generateEditLink(row.id) + '" class="btn-primary btn btn-xs edit_element"><i class="far fa-edit"></i> edit</a>\n' +
                                    '<a href="' + crudDataTable.generateDeleteLink(row.id) + '" ' +
                                    '   class="btn-danger btn btn-xs delete_element" ' +
                                    '   onclick="return confirm(\'{{ $t['confirmation_delete'] or trans('eliurkis::crud.confirmation_delete') }}\');">\n' +
                                    '   <i class="far fa-trash-alt"></i> delete\n' +
                                    '</a>';
                            },
                            'orderable': false
                        }
                    ],
                    'processing': true,
                    'serverSide': true,
                    'ajax': '{{ route($route.'.index') }}'
                });

                $('.dataTables_filter input')
                    .unbind()
                    .bind('input keyup', function (e) {
                        if (e.keyCode == 13 || this.value.length > 2) {
                            table
                                .search(this.value)
                                .draw();
                        }
                    });
            }
        });
    </script>
@endpush
