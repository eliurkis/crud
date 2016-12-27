<?php

namespace Eliurkis\Crud;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;

class CrudController extends Controller
{
    protected $route;
    protected $entity;
    protected $entityInstance = null;
    protected $fields = [];
    protected $columns = [];
    protected $buttons = [
        'create',
        'edit',
        'delete',
    ];
    protected $paginate = null;
    protected $searchable = [];
    protected $filters = [];
    protected $queryFilters = [];
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
    protected $links = [];

    public function index(Request $request)
    {
        $entity = $this->entity;

        // Relation Fields
        $belongToFields = $this->getBelongToFields();
        if (count($belongToFields)) {
            $entity = $this->entity->with($belongToFields);
        }

        // Filters
        $entity = $this->filters($entity, $request);

        // Search
        $entity = $this->search($entity, $request);

        // Pagination
        $rows = $this->paginate > 0 ? $this->paginate($entity, $request) : $entity->get();

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
            ->with('links', $this->prepareLinks())
            ->with('request', $request)
            ->with('route', $this->route);
    }

    public function create()
    {
        $this->prepare_fields();

        return view('crud::create')
            ->with('type', 'create')
            ->with('route', $this->route)
            ->with('t', $this->texts)
            ->with('formColsClasses', $this->formColsClasses)
            ->with('links', $this->prepareLinks())
            ->with('fields', $this->fields);
    }

    public function store(Request $request)
    {
        $validate = $this->prepareValidation();
        if ($validate['rules']) {
            $this->validate($request, $validate['rules'], $validate['messages'], $validate['customAttributes']);
        }

        DB::beginTransaction();

        try {
            $row = $this->entity->create(array_merge($request->all(), $this->queryFilters));
            $this->updateForeignRelations($row, $request);
        } catch (QueryException $e) {
            return redirect()
                ->back()
                ->with('error', 'Ha ocurrido un error, intente nuevamente');
        }

        DB::commit();

        return redirect()
            ->route($this->route.'.index')
            ->with('success', isset($this->textsGeneral['save_action'])
                ? $this->textsGeneral['save_action']
                : trans('eliurkis::crud.save_action'));
    }

    public function edit($id)
    {
        if (! $this->entityInstance) {
            $this->entityInstance = $this->entity->findOrFail($id);
        }

        $this->prepare_fields();

        return view('crud::create')
            ->with('type', 'edit')
            ->with('route', $this->route)
            ->with('t', $this->texts)
            ->with('fields', $this->fields)
            ->with('formColsClasses', $this->formColsClasses)
            ->with('links', $this->prepareLinks())
            ->with('data', $this->entityInstance);
    }

    public function update(Request $request, $id)
    {
        $validations = $this->prepareValidation();
        if ($validations) {
            $this->validate($request, $validations);
        }

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
        } catch (QueryException $e) {
            return redirect()
                ->back()
                ->with('error', 'Ha ocurrido un error, intente nuevamente');
        }

        DB::commit();

        return redirect()
            ->route($this->route.'.index', $this->getParamsFilters())
            ->with('success', isset($this->textsGeneral['save_action'])
                ? $this->textsGeneral['save_action']
                : trans('eliurkis::crud.save_action'));
    }

    public function destroy($id)
    {
        $this->entity->destroy($id);

        return redirect()
            ->route($this->route.'.index')
            ->with('success', isset($this->textsGeneral['delete_action'])
                ? $this->textsGeneral['delete_action']
                : trans('eliurkis::crud.delete_action'));
    }

    /* Private Actions */

    protected function filters($entity, $request)
    {
        if ($request->query('filter')) {
            foreach ($request->query('filter') as $field => $value) {
                $entity = $entity->where($field, $value);
            }
        }

        if (count($this->queryFilters)) {
            foreach ($this->queryFilters as $field => $value) {
                $entity = $entity->where($field, $value);
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
                $this->fields[$filter]['config']['default_value'] = isset($urlParams['filter'][$filter]) ? $urlParams['filter'][$filter] : null;

                // Create URL
                if (isset($urlParams['filter'][$filter])) {
                    unset($urlParams['filter'][$filter]);
                }
                $this->fields[$filter]['attributes']['data-filter-url'] = route($this->route.'.index', $urlParams).(count($urlParams) ? '&' : '?');

                // Create array
                $this->action = 'list';
                $this->htmlFilters[$filter] = $this->fieldtype_select($filter);
            }
        }
    }

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

    protected function search($entity, $request)
    {
        if ($request->get('q') != '') {
            $searchableCols = isset($this->searchable['columns']) ? $this->searchable['columns'] : $this->searchable;

            $entity = $entity->where(function ($query) use ($request, $searchableCols) {
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

    protected function updateForeignRelations($row, $request)
    {
        $foreignRelations = $this->getForeignRelationsFields();

        foreach ($foreignRelations as $foreignRelation) {
            $values = $request->get($foreignRelation);
            $row->$foreignRelation()->sync((array) $values);
        }
    }

    protected function prepareMultipleFields($name)
    {
        $properties = $this->fields[$name];
        $config = isset($properties['config']) ? $properties['config'] : [];
        $config['options'] = isset($config['options']) ? $config['options'] : [];
        $config['cols'] = isset($config['cols']) ? $config['cols'] : 1;

        if (! count($config['options']) && isset($config['entity'])) {
            $config['options'] = $config['entity']::get()
                ->lists($config['field_value'], $config['field_key'])
                ->toArray();
        }

        if ($this->action == 'list' && isset($this->fields[$name]['config']['filter_no_selection'])) {
            $config['options'] = array_merge([
                '-1' => $this->fields[$name]['config']['filter_no_selection'], ],
                $config['options']
            );
        }

        $properties['config'] = $config;
        $this->fields[$name] = $properties;
    }

    protected function getParamsFilters()
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
            if (! isset($this->links[$link])) {
                $this->links[$link] = route($this->route.'.'.$link);
            }
        }

        return $this->links;
    }

    protected function prepareValidation()
    {
        $validations = [
            'rules'            => [],
            'messages'         => [],
            'customAttributes' => [],
        ];
        foreach ($this->fields as $field => $options) {
            if (isset($options['validation'])) {
                $validations['rules'][$field] = $options['validation'];
                $validations['customAttributes'][$field] = $options['label'];
            }
        }

        return $validations;
    }

    /* Fields Types */

    protected function prepare_fields()
    {
        if ($this->entityInstance) {
            \Form::model($this->entityInstance);
        }

        foreach ($this->fields as $name => $properties) {
            $this->fields[$name]['config'] = isset($properties['config']) ? $properties['config'] : [];
            $this->fields[$name]['attributes'] = isset($properties['attributes']) ? $properties['attributes'] : [];
            $this->fields[$name]['attributes']['class'] = 'form-control';

            // Determine the value
            if ($this->entityInstance) {
                $activeValue = $this->entityInstance->$name;
            } else {
                $activeValue = isset($config['default_value']) ? $config['default_value'] : null;
            }

            $fieldTypeMethod = 'fieldtype_'.$properties['type'];
            $className = '\Eliurkis\Crud\FieldTypes\\'.ucfirst($properties['type']);

            if (class_exists($className)) {
                $this->fields[$name]['html'] = $className::prepare(
                    $name,
                    $this->fields[$name],
                    $this->entityInstance
                        ? $this->entityInstance->$name
                        : (isset($config['default_value']) ? $config['default_value'] : null)
                );
            } elseif (method_exists($this, $fieldTypeMethod)) {
                $this->fields[$name]['html'] = $this->$fieldTypeMethod($name);
            } else {
                $this->fields[$name]['html'] = $this->fieldtype_text($name);
            }
        }
    }

    protected function fieldtype_select($name)
    {
        // Init
        $this->prepareMultipleFields($name);
        $properties = $this->fields[$name];
        $properties['attributes'] = isset($properties['attributes']) ? $properties['attributes'] : [];
        $properties['attributes']['class'] = 'form-control chosen-select-width';
        $config = isset($properties['config']) ? $properties['config'] : [];

        // Determine the value
        if ($this->entityInstance) {
            $activeValue = $this->entityInstance->$name;
        } else {
            $activeValue = isset($config['default_value']) ? $config['default_value'] : null;
        }

        return \Form::select(
            $name,
            $this->fields[$name]['config']['options'],
            \Input::old($name, $activeValue),
            $properties['attributes']
        );
    }

    protected function fieldtype_radio($name)
    {
        // Init
        $this->prepareMultipleFields($name);
        $properties = $this->fields[$name];
        $config = isset($properties['config']) ? $properties['config'] : [];

        // Determine the value
        if ($this->entityInstance) {
            $activeValue = $this->entityInstance->$name;
        } else {
            $activeValue = isset($config['default_value']) ? $config['default_value'] : null;
        }

        $colsSize = 12 / $config['cols'];
        $html = '<div class="row">';
        foreach ($config['options'] as $key => $value) {
            $uniqueId = uniqid('opt', true).md5(mt_rand(1, 1000));
            $html .=
                '<div class="col-md-'.$colsSize.' col-xs-12">'.
                \Form::radio($name, $key, $activeValue === $key, ['id' => $uniqueId]).
                \Form::label($uniqueId, $value).
                '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    protected function fieldtype_foreign($name)
    {
        // Init
        $checkedValues = [];
        $properties = $this->fields[$name];
        $config = isset($properties['config']) ? $properties['config'] : [];

        $properties['options'] = isset($properties['options']) ? $properties['options'] : [];
        $config['cols'] = isset($config['cols']) ? $config['cols'] : 1;

        if (! count($properties['options']) && isset($config['entity'])) {
            $properties['options'] = $config['entity']::get()
                ->lists($config['field_value'], $config['field_key'])
                ->toArray();
        }

        if ($this->entityInstance) {
            $checkedValues = $this->entityInstance->{$config['rel']}->lists('id')->toArray();
        }

        $colsSize = 12 / $config['cols'];
        $html = '<div class="row">';
        foreach ($properties['options'] as $key => $value) {
            $uniqueId = uniqid('opt', true).md5(mt_rand(1, 1000));
            $html .=
                '<div class="col-md-'.$colsSize.' col-xs-12">'.
                \Form::checkbox($name.'[]', $key, in_array($key, $checkedValues) ? true : false, ['id' => $uniqueId]).
                \Form::label($uniqueId, $value).
                '</div>';
        }
        $html .= '</div>';

        $properties['config'] = $config;
        $this->fields[$name] = $properties;

        return $html;
    }
}
