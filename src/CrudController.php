<?php

namespace Eliurkis\Crud;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class CrudController extends Controller
{
    protected $route;
    protected $entity;
    protected $entityInstance = null;
    protected $fields = [];
    protected $columns = [];
    protected $buttons = [
        'show',
        'create',
        'edit',
        'delete',
    ];
    protected $paginate = null;
    protected $searchable = [];
    protected $filters = [];
    protected $queryFilters = [];
    protected $orderBy = [];
    protected $orderByRaw = null;
    protected $filterRequire = [];
    protected $textsGeneral = [
        'list_title'   => 'Contents',
        'create_title' => '',
        'edit_title'   => '',
    ];
    protected $texts = [];
    protected $htmlFilters = [];
    protected $action = null;
    protected $formColsClasses = [
        'col-md-10 col-md-offset-1',
        'col-md-2',
        'col-md-10',
    ];
    protected $formCols = [
        'show'   => 2,
        'create' => 2,
        'edit'   => 2,
    ];
    protected $links = [];
    protected $listDisplay = [
        'action-buttons' => true,
    ];

    public function __construct($entity, $config = [])
    {
        $this->entity = $entity;

        $config = count($config) ? $config : config('crud.'.$this->route);

        if (is_array($config)) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public function index(Request $request)
    {
        // If DataTable is activated
        if (isset($this->dataTableActivated)) {
            return $this->indexDataTable($request);
        }

        $entity = $this->entity;

        // Relation Fields
        if ($belongToFields = $this->getBelongToFields()) {
            $entity = $this->entity->with($belongToFields);
        }

        // Filters
        $entity = $this->filters($entity, $request);

        // Search
        $entity = $this->search($entity, $request);

        // Order By
        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $column => $direction) {
                $entity = $entity->orderBy($column, $direction);
            }
        }

        if ($this->orderByRaw) {
            $entity = $entity->orderByRaw($this->orderByRaw);
        }

        // Pagination
        $rows = $this->paginate > 0 ? $this->paginate($entity, $request) : $entity->get();

        // Sort By Rows
        if (!empty($this->sortBy)) {
            foreach ($this->sortBy as $column => $direction) {
                $rows = strtolower($direction) == 'desc'
                    ? $rows->sortByDesc($column)
                    : $rows->sortBy($column);
            }
        }

        // HTML Filters
        $this->htmlFilters();

        return view('crud::list', compact('rows'))
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
            ->with('route', $this->route);
    }

    public function show($id)
    {
        if (!$this->entityInstance) {
            $this->entityInstance = $this->entity->findOrFail($id);
        }

        $this->prepareFields();

        return view('crud::show')
            ->with('type', 'show')
            ->with('route', $this->route)
            ->with('t', $this->texts)
            ->with('formColsClasses', $this->formColsClasses)
            ->with('colsNumber', $this->formCols['show'])
            ->with('fieldsGroup', collect($this->fields)->split($this->formCols['show']))
            ->with('fields', $this->fields)
            ->with('data', $this->entityInstance);
    }

    public function create()
    {
        $this->prepareFields();

        return view('crud::create')
            ->with('type', 'create')
            ->with('route', $this->route)
            ->with('t', $this->texts)
            ->with('formColsClasses', $this->formColsClasses)
            ->with('links', $this->prepareLinks())
            ->with('colsNumber', $this->formCols['create'])
            ->with('fieldsGroup', collect($this->fields)->split($this->formCols['create']))
            ->with('fields', $this->fields);
    }

    protected function manageFiles($row, $request)
    {
        $mediaFiles = [];

        foreach ($this->fields as $fieldName => $field) {
            if ($field['type'] === 'file' && $request->file($fieldName)) {
                $customProperties = ['route' => $this->route, 'field' => $fieldName];
                if (isset($field['storage_path'])) {
                    $customProperties['storage_path'] = $field['storage_path'];
                }
                $mediaFiles[] = $row->addMedia($request->file($fieldName))
                    ->withCustomProperties($customProperties)
                    ->toMediaCollection($fieldName);
            }
        }

        return $mediaFiles;
    }

    public function store(Request $request)
    {
        $this->validateRequest($request, 'store');

        DB::beginTransaction();

        try {
            $row = $this->entity->create(array_merge($request->all(), $this->queryFilters));
            $this->updateForeignRelations($row, $request);
            $mediaFiles = $this->manageFiles($row, $request);
        } catch (QueryException $e) {
            \Log::error($e);
            if (config('app.debug')) {
                throw new \Exception($e);
            }
            return redirect()
                ->back()
                ->with('error', 'Ha ocurrido un error, intente nuevamente');
        }

        DB::commit();

        event($this->route.'.store', [$row, $mediaFiles]);

        return redirect()
            ->route($this->route.'.index')
            ->with('success', isset($this->textsGeneral['save_action'])
                ? $this->textsGeneral['save_action']
                : trans('eliurkis::crud.save_action'));
    }

    public function edit($id)
    {
        if (!$this->entityInstance) {
            $this->entityInstance = $this->entity->findOrFail($id);
        }

        $this->prepareFields();

        return view('crud::create')
            ->with('type', 'edit')
            ->with('route', $this->route)
            ->with('t', $this->texts)
            ->with('formColsClasses', $this->formColsClasses)
            ->with('links', $this->prepareLinks())
            ->with('colsNumber', $this->formCols['edit'])
            ->with('fieldsGroup', collect($this->fields)->split($this->formCols['edit']))
            ->with('fields', $this->fields)
            ->with('data', $this->entityInstance);
    }

    public function update(Request $request, $id)
    {
        $this->validateRequest($request, 'update');

        DB::beginTransaction();

        try {
            $row = $this->entity->findOrFail($id);
            $row->update(
                array_merge(
                    $request->all(),
                    $this->queryFilters
                )
            );
            $this->updateForeignRelations($row, $request);
            $mediaFiles = $this->manageFiles($row, $request);
        } catch (QueryException $e) {
            \Log::error($e);
            if (config('app.debug')) {
                throw new \Exception($e);
            }
            return redirect()
                ->back()
                ->with('error', 'Ha ocurrido un error, intente nuevamente');
        }

        DB::commit();

        event($this->route.'.update', [$row, $mediaFiles]);

        return redirect()
            ->route($this->route.'.index', $this->getParamsFilters($row))
            ->with('success', isset($this->textsGeneral['save_action'])
                ? $this->textsGeneral['save_action']
                : trans('eliurkis::crud.save_action'));
    }

    public function destroy($id)
    {
        try {
            $row = $this->entity->findOrFail($id);
            $row->delete();

            event($this->route.'.destroy', [$row]);
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route($this->route.'.index')
                ->with('error', __('The element that you are trying to delete does not exist'));
        } catch (\Exception $e) {
            \Log::error($e);
            if (config('app.debug')) {
                throw new \Exception($e);
            }
            return redirect()
                ->route($this->route.'.index')
                ->with('error', __('An error occurred, try again'));
        }

        return redirect()
            ->route($this->route.'.index')
            ->with('success', isset($this->textsGeneral['delete_action'])
                ? $this->textsGeneral['delete_action']
                : trans('eliurkis::crud.delete_action'));
    }

    public function download($id, $fieldName)
    {
        if (!$this->entityInstance) {
            $this->entityInstance = $this->entity->findOrFail($id);
        }

        $media = $this->entityInstance->getMedia($fieldName)->last();

        if ($media && $media->disk === 's3') {
            $tempImage = tempnam(sys_get_temp_dir(), $media->file_name);
            copy($media->getTemporaryUrl(\Carbon::now()->addMinutes(5)), $tempImage);
            return response()->file($tempImage, ['Content-Type' => $media->mime_type]);
        }

        return $media;
    }

    /* Private Actions */

    /**
     * @param         $entity
     * @param Request $request
     *
     * @return mixed
     */
    protected function filters($entity, $request)
    {
        if ($request->query('filter')) {
            $filters = is_array($request->query('filter')) ? $request->query('filter') : [];
            foreach ($filters as $field => $value) {
                $entity = $entity->where($field, $value);
            }
        }

        if (count($this->queryFilters)) {
            foreach ($this->queryFilters as $field => $value) {
                if (is_array($value)) {
                    $entity = $entity->whereIn($field, $value);
                } else {
                    $entity = $entity->where($field, $value);
                }
            }
        }

        return $entity;
    }

    protected function htmlFilters()
    {
        $this->htmlFilters = [];
        if (count($this->filters)) {
            foreach ($this->filters as $filter) {
                // Build params
                $urlParams = \Input::query();

                // Default Value
                $this->fields[$filter]['config']['default_value'] = isset($urlParams['filter'][$filter])
                    ? $urlParams['filter'][$filter]
                    : null;

                // Create URL
                if (isset($urlParams['filter'][$filter])) {
                    unset($urlParams['filter'][$filter]);
                }
                $this->fields[$filter]['attributes']['data-filter-url'] = route($this->route.'.index', $urlParams)
                    .(count($urlParams) ? '&' : '?');

                // Create array
                $this->action = 'list';
                $this->htmlFilters[$filter] = $this->prepareField($filter);
            }
        }
    }

    /**
     * @param         $entity
     * @param Request $request
     *
     * @return mixed
     */
    protected function paginate($entity, $request)
    {
        $rows = $entity->paginate($this->paginate);

        if ($request->get('q') != '') {
            $rows->appends(['q' => $request->get('q')]);
        }

        if ($request->get('filter')) {
            foreach ($request->get('filter') as $field => $value) {
                $rows->appends(['filter['.$field.']' => $value]);
            }
        }

        return $rows;
    }

    /**
     * @param         $entity
     * @param Request $request
     *
     * @return mixed
     */
    protected function search($entity, $request)
    {
        if ($request->get('q') != '') {
            $searchableCols = isset($this->searchable['columns']) ? $this->searchable['columns'] : $this->searchable;

            $entity = $entity->where(function (Builder $query) use ($request, $searchableCols) {
                foreach ($searchableCols as $field) {
                    $query->orWhere($field, 'like', '%'.$request->get('q').'%');
                }
            });

            if (isset($this->searchable['joins'])) {
                foreach ($this->searchable['joins'] as $table => $joinFields) {
                    $entity = $entity->join($table, $joinFields[0], '=', $joinFields[1]);
                }
            }
        }

        return $entity;
    }

    protected function getForeignRelationsFields()
    {
        $foreignRelations = [];
        foreach ($this->fields as $field => $options) {
            if ($options['type'] === 'foreign') {
                $foreignRelations[] = $field;
            }
        }

        return $foreignRelations;
    }

    protected function getBelongToFields()
    {
        $fields = [];
        foreach ($this->fields as $field => $options) {
            if ($options['type'] === 'select' && isset($options['config']['rel'])) {
                $fields[] = $options['config']['rel'];
            }
        }

        return $fields;
    }

    /**
     * @param object  $row
     * @param Request $request
     */
    protected function updateForeignRelations($row, $request)
    {
        $foreignRelations = $this->getForeignRelationsFields();

        foreach ($foreignRelations as $foreignRelation) {
            $values = $request->get($foreignRelation);
            $row->$foreignRelation()->sync((array) $values);
        }
    }

    protected function getParamsFilters($row)
    {
        $params = [];

        if (count($this->filterRequire)) {
            $params['filter'] = [];

            foreach ($this->filterRequire as $field) {
                $params['filter'][$field] = $row->$field;
            }
        }

        return $params;
    }

    protected function prepareLinks()
    {
        $links = ['index', 'create', 'store'];

        foreach ($links as $link) {
            if (!isset($this->links[$link]) && Route::has($this->route.'.'.$link)) {
                $this->links[$link] = route($this->route.'.'.$link);
            }
        }

        return $this->links;
    }

    /**
     * @param Request $request
     * @param         $type
     */
    protected function validateRequest($request, $type)
    {
        $validations = [
            'rules'            => [],
            'messages'         => [],
            'customAttributes' => [],
        ];

        foreach ($this->fields as $field => $options) {
            $validation = null;
            if (isset($options['validation'][$type])) {
                $validation = $options['validation'][$type];
            } elseif (isset($options['validation']) && is_string($options['validation'])) {
                $validation = $options['validation'];
            }

            if ($validation != '') {
                $validations['rules'][$field] = $validation;
                $validations['customAttributes'][$field] = $options['label'];
            }
        }

        if ($validations['rules']) {
            $this->validate(
                $request,
                $validations['rules'],
                $validations['messages'],
                $validations['customAttributes']
            );
        }
    }

    protected function prepareRelationalFields($name)
    {
        // Default values
        $config = isset($this->fields[$name]['config']) ? $this->fields[$name]['config'] : [];
        $config['options'] = isset($config['options']) ? $config['options'] : [];
        $config['cols'] = isset($config['cols']) ? $config['cols'] : 1;

        // Get foreign values
        if (!count($config['options']) && isset($config['entity'])) {
            $config['options'] = $config['entity']::get()
                ->pluck($config['field_value'], $config['field_key'])
                ->toArray();
        }

        // No selection for filters
        if ($this->action == 'list' && isset($config['filter_no_selection'])) {
            $config['options'] = array_merge([
                '-1' => $config['filter_no_selection'],
            ], $config['options']);
        }

        if (isset($config['pre_options'])) {
            $config['options'] = $config['pre_options'] + $config['options'];
        }

        $this->fields[$name]['config'] = $config;

        return $this->fields[$name];
    }

    protected function prepareFields()
    {
        if ($this->entityInstance) {
            \Form::model($this->entityInstance);
        }

        foreach ($this->fields as $name => $properties) {
            $this->fields[$name]['html'] = $this->prepareField($name, $properties);
            $this->fields[$name]['value'] = $this->entityInstance->$name ?? null;
            $this->fields[$name]['value_text'] = $this->prepareFieldShow($name, $properties);
        }

        return $this->fields;
    }

    protected function prepareFieldShow($name, $properties = [])
    {
        // Init
        if (empty($properties)) {
            $properties = $this->fields[$name];
        }

        $this->fields[$name]['config'] = isset($properties['config']) ? $properties['config'] : [];
        $this->fields[$name]['attributes'] = isset($properties['attributes']) ? $properties['attributes'] : [];
        $config = $this->fields[$name]['config'];

        $value = $this->entityInstance
            ? ($this->entityInstance->$name ?? null)
            : (isset($config['default_value']) ? $config['default_value'] : null);

        if ($this->entityInstance) {
            if (($properties['type'] === 'date' || $properties['type'] === 'datetime') &&
                $this->entityInstance->$name != '') {
                $fieldValue = $this->entityInstance->$name;

                if (!is_object($fieldValue)) {
                    $fieldValue = Carbon::parse($this->entityInstance->$name);
                }

                $value = $fieldValue->diff(Carbon::now())->format('%y') != date('Y')
                    ? $fieldValue->format($properties['type'] === 'date' ? 'm/d/Y' : 'm/d/Y h:ia')
                    : null;
            }

            if ($properties['type'] === 'file' && $this->entityInstance->getMedia($name)->last()) {
                $value = '<a href="'.route($this->route.'.download', [$this->entityInstance->id, $name]).
                    '" target="_blank">'.(
                    isset($this->fields[$name]['link_name'])
                        ? $this->fields[$name]['link_name']
                        : 'download'
                    ).'</a>';
            }

            if (isset($config['entity'])) {
                $value = isset($this->entityInstance->{$config['rel']}->{$config['field_value']})
                    ? $this->entityInstance->{$config['rel']}->{$config['field_value']}
                    : null;
            } elseif (isset($config['options']) && count($config['options'])) {
                $value = $config['options'][$value] ?? null;
            }
        }

        return empty($value) ? 'N/A' : $value;
    }

    protected function prepareField($name, $properties = [])
    {
        // Init
        if (empty($properties)) {
            $properties = $this->fields[$name];
        }

        $this->fields[$name]['config'] = isset($properties['config']) ? $properties['config'] : [];
        $this->fields[$name]['attributes'] = isset($properties['attributes']) ? $properties['attributes'] : [];
        $this->fields[$name]['attributes']['class'] = 'form-control';
        $this->fields[$name]['html'] = null;

        $config = $this->fields[$name]['config'];

        $value = $this->entityInstance
            ? isset($properties['value_alias'])
                ? $this->entityInstance->{$properties['value_alias']}
                : $this->entityInstance->$name
            : (isset($config['default_value']) ? $config['default_value'] : null);

        // Define field type class namespace
        $className = '\Eliurkis\Crud\FieldTypes\\'.ucfirst($properties['type']);
        if (!class_exists($className)) {
            return;
        }

        if ($properties['type'] == 'foreign' || $properties['type'] == 'select') {
            $properties = $this->prepareRelationalFields($name);

            if ($properties['type'] == 'foreign' && $this->entityInstance) {
                $value = $this->entityInstance->{$config['rel']}->pluck($config['field_key'])->toArray();
            }

            if ($properties['type'] == 'select') {
                $properties['attributes']['class'] = 'form-control chosen-select-width';
            }

            return $className::prepare(
                $name,
                $properties['config']['options'],
                $value,
                $properties
            );
        }

        return $className::prepare(
            $name,
            $value,
            $this->fields[$name]
        );
    }
}
