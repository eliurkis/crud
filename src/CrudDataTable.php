<?php

namespace Eliurkis\Crud;

use DB;
use Illuminate\Http\Request;

trait CrudDataTable
{
    protected $dataTableActivated = true;

    public function indexDataTable(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return $this->indexDataTableResults($request);
        }

        return view('crud::list-datatable')
            ->with('rows', [])
            ->with('fields', $this->fields)
            ->with('columns', $this->columns)
            ->with('searchable', $this->searchable)
            ->with('buttons', $this->buttons)
            ->with('paginate', $this->paginate)
            ->with('t', $this->texts)
            ->with('htmlFilters', $this->htmlFilters)
            ->with('listDisplay', $this->listDisplay)
            ->with('links', $this->prepareLinks())
            ->with('request', $request)
            ->with('orderBy', $this->orderBy)
            ->with('route', $this->route);
    }

    public function indexDataTableResults(Request $request)
    {
        list($colSortBy, $colOrderBy) = $this->getSortInformation($this->columns, $request);
        list($totalRows, $totalRowsFiltered) = $this->getRowsTotals($request->get('search')['value'] ?? null);

        $query = $this->entity->orderBy($colSortBy, $colOrderBy);
        $query = $this->applySearchScope($query, $request->get('search')['value'] ?? null);

        $rows = $query->offset($request->get('start') ?? 0)
            ->limit($request->get('length') ?? $totalRows)
            ->get();

        return response()->json([
            'data'            => $rows,
            'draw'            => (int) ($request->get('draw') ?? 0),
            'recordsFiltered' => $totalRowsFiltered,
            'recordsTotal'    => $totalRows,
            'colSortBy'       => $colSortBy,
            'colOrderBy'      => $colOrderBy,
        ]);
    }

    protected function getSortInformation($cols, $request)
    {
        return [
            $cols[$request->get('order')[0]['column'] ?? 0],
            $request->get('order')[0]['dir'] ?? 'asc',
        ];
    }

    protected function getRowsTotals($searchValue = null)
    {
        $totalRows = $totalRowsFiltered = $this->getRowsTotal();

        if ($searchValue != '' && $this->searchable) {
            $totalRowsFiltered = $this->getRowsTotal($searchValue);
        }

        return [$totalRows, $totalRowsFiltered];
    }

    protected function getRowsTotal($searchValue = null)
    {
        $query = $this->entity->select(DB::raw('count(*) as total'));
        $query = $this->applySearchScope($query, $searchValue);

        return $query->first()
            ->total;
    }

    protected function applySearchScope($query, $searchValue = null)
    {
        if ($searchValue == '' || !$this->searchable) {
            return $query;
        }

        $searchable = $this->searchable;

        return $query->where(function ($query) use ($searchValue, $searchable) {
            foreach ($searchable as $key => $field) {
                $query = $key === 0
                    ? $query->where($field, 'like', '%'.$searchValue.'%')
                    : $query->orWhere($field, 'like', '%'.$searchValue.'%');

            }
            return $query;
        });
    }
}
