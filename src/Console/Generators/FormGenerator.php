<?php
/**
 * @author        Merijn
 * @copyright (c) 2019.
 */

namespace Simianbv\Generators\Console\Generators;


use Illuminate\Support\Str;

/**
 * Class FormGenerator
 * @package Simianbv\Generators\Console\Generators
 */
class FormGenerator extends ClassGenerator
{

    private $stubToUse = 'Lightning-form';

    private $fieldStubs = [];
    private $methods = [];
    private $editorOptions = [];
    private $editorConfig = [];
    private $imports = [];
    private $components = [];

    protected static $numbers = ['integer', 'int', 'bigInteger', 'bigInt', 'smallInteger', 'smallInt', 'float', 'double', 'bigFloat', 'bigDouble', 'decimal'];
    protected static $strings = ['varchar', 'string', 'text', 'mediumText', 'bigText'];
    protected static $booleans = ['bool', 'boolean', 'tinyInt', 'tinyInteger'];


    /**
     * @param $resource
     * @param $namespace
     * @param $model
     *
     * @return bool
     */
    public function create ($resource, $namespace, $model)
    {
        $this->fieldStubs = [];
        $this->methods = [];
        $this->editorOptions = [];
        $this->editorConfig = [];
        $this->components = [];
        $this->imports = [];

        $this->resource = $resource;

        $url = ($namespace != '' ? Str::slug(strtolower($namespace)) . '/' : '') . Str::slug(Str::snake(Str::plural($model))) . '/';

        $generated = $this->getColumnFields($resource, $namespace, $model);

        $fields = [
            'Model'           => $model,
            'Name'            => ucfirst(strtolower(str_replace('_', ' ', Str::snake($model)))),
            'Fields'          => implode("", $generated['fields']),
            'Columns'         => $this->getFormColumns($resource),
            'RelationMethods' => implode($generated['relationMethods']),
            'RelationOptions' => $generated['relationOptions'] ? implode("\n        ", $generated['relationOptions']) : "",
            'Url'             => $url,
            'EditorOptions'   => $this->getEditorOptions(),
            'EditorConfig'    => $this->getEditorConfig(),
            'ResourceList'    => $generated['resourceList'] ? implode("\n        ", $generated['resourceList']) : "",
            'PaletteOptions'  => $generated['colorList'] ? $this->addColors($generated['colorList']) : "",
            'Components'      => implode(",\n      ", $this->components),
            'Imports'         => implode("\n", $this->imports),
            'Methods'         => $this->getMethods(),
        ];

        $stub = new Stub($this->stubToUse);
        $content = $stub->fill($fields, ['{%', '%}']);

        // Feedback to add to the output
        $file = ucfirst(Str::slug(Str::snake($model)) . "-form");
        $fileTrimmed = ucfirst(Str::slug(Str::snake($model)));
        $component = ucfirst(strtolower($namespace) . $model) . "Form";

        $this->parent->addFrontendImport('const ' . $component . ' = () => import(/* webpackChunkName: "' . strtolower($namespace) . '" */ "./views/' . strtolower($namespace) . '/' . $file . '.vue")');
        $this->parent->addFrontendRoute("{path: '/" . ($namespace != '' ? strtolower($namespace) . '/' : '') . Str::slug(Str::snake(Str::plural($model))) . "/create',      name: '" . ($namespace != '' ? strtolower($namespace) . '-' : '') . strtolower($fileTrimmed) . "-create', component: " . $component . ", props: true}, ");
        $this->parent->addFrontendRoute("{path: '/" . ($namespace != '' ? strtolower($namespace) . '/' : '') . Str::slug(Str::snake(Str::plural($model))) . "/:id/edit',   name: '" . ($namespace != '' ? strtolower($namespace) . '-' : '') . strtolower($fileTrimmed) . "-edit', component: " . $component . ", props: true}, ");

        return $content;
    }

    /**
     * Iterate over the columns in the yaml, for each column, add an entry to the Form object in the vue component.
     *
     * @param array $resource
     *
     * @return string
     */
    private function getFormColumns (array $resource)
    {
        $skippables = ['id', 'created_at', 'updated_at'];
        $columns = [];

        if (isset($resource['columns'])) {
            foreach ($resource['columns'] as $column => $attr) {
                if (in_array($column, $skippables)) {
                    continue;
                }
                if (in_array($attr['type'], self::$numbers)) {
                    $columns[] = $column . ": null, ";
                    continue;
                }
                if (in_array($attr['type'], self::$booleans)) {
                    $columns[] = $column . ": false, ";
                    continue;
                }

                // we're going to assume its a string type or a stringable saving type i.e. enum, set, string etc
                $columns[] = $column . ": '', ";
            }
        }

        if (isset($resource['lockable'])) {
            $columns[] = "is_locked: false, ";
        }

        return implode("\n          ", $columns);
    }

    /**
     * @param array $resource
     * @param string $namespace
     * @param string $model
     *
     * @return array
     */
    private function getColumnFields (array $resource, string $namespace, string $model)
    {
        // $relations = $this->getRelations();

        // prepare the relation stubs

        $delims = ['{%', '%}'];
        $stubs = [
            'boolean'        => new Stub(config("generators.stubs.fields.bool")),
            'date'           => new Stub(config("generators.stubs.fields.date")),
            'input'          => new Stub(config("generators.stubs.fields.input")),
            'select'         => new Stub(config("generators.stubs.fields.select")),
            'editor'         => new Stub(config("generators.stubs.fields.editor")),
            'color'          => new Stub(config("generators.stubs.fields.color")),
            'method-editor'  => new Stub(config('generators.stubs.fields.method-editor')),
            'method-color'   => new Stub(config('generators.stubs.fields.method-color')),
            'option-request' => new Stub(config('generators.stubs.fields.option-request')),
        ];


        $relationOptions = [];
        $fields = [];
        $relationMethodsList = [];
        $resourceList = [];
        $colorList = [];
        $hasEditor = false;

        // skip these fields, as they are not necessary
        $skippables = ['id', 'created_at', 'updated_at'];

        if (isset($resource['columns'])) {
            foreach ($resource['columns'] as $column => $type) {
                // skip the columns in the $skippables array as they don't need fields
                if (in_array($column, $skippables)) {
                    continue;
                }

                // check if the column is a relation field
                if (Str::endsWith($column, ['_id'])) {
                    $relation = $this->findRelation($column, $resource, $namespace, $model);

                    $label = __(ucfirst(implode(' ', explode('_', $column))));

                    $data = [
                        'Column'            => $column,
                        'Label'             => $label,
                        'Required'          => isset($type['nullable']) && $type['nullable'] == false ? ' *' : '',
                        'RequiredAttribute' => isset($type['nullable']) && $type['nullable'] == false ? 'required="true"' : '',
                    ];

                    $fields[] = $stubs['select']->fill($data, $delims);
                    $relationOptions[] = $column . "_options: [],";

                    if ($relation !== null) {
                        $relType = $relation['attr']['type'] == 'hasOne';
                        $relationMethodsList[] = $this->getRelationOption($column, $relation['model'], $relation['attr'], $model, $relType);
                        $resourceList[] = "this.get" . Str::plural($relation['model']) . "();";
                    }

                    continue;
                } else {
                    $label = __(ucfirst(implode(' ', explode('_', $column))));
                    $data = [
                        'Column'            => $column,
                        'Label'             => $label,
                        'Required'          => isset($type['nullable']) && $type['nullable'] == false ? ' *' : '',
                        'RequiredAttribute' => isset($type['nullable']) && $type['nullable'] == false ? 'required="true"' : '',
                    ];

                    $hasField = false;

                    if (isset($type['field'])) {
                        if (in_array($type['field'], ['color', 'color-picker'])) {
                            $colorList[] = $column;
                            $hasField = true;
                            $fields[] = $stubs['color']->fill($data, $delims);
                        }

                        if (Str::startsWith($type['field'], 'editor')) {
                            if (!$hasEditor) {
                                $hasEditor = true;
                                $this->imports[] = "import Monaco from 'monaco-editor-forvue'";
                                $this->components[] = 'Monaco';

                                $this->methods[] = $stubs['method-editor']->fill([], $delims);
                            }

                            $data['EditorLanguage'] = 'html';
                            $data['EditorTheme'] = 'vs-light';
                            $this->editorOptions[] = $column;
                            $this->editorConfig[] = true;
                            $fields[] = $stubs['editor']->fill($data, ['{%', '%}']);
                            $hasField = true;
                        }
                    }

                    if (isset($type['editor']) && is_array($type['editor'])) {
                        if (!$hasEditor) {
                            $hasEditor = true;
                            $this->imports[] = "import Monaco from 'monaco-editor-forvue'";
                            $this->components[] = 'Monaco';
                            $this->methods[] = $stubs['method-editor']->fill([], $delims);
                        }

                        $data['EditorLanguage'] = $type['editor']['language'] ?? 'html';
                        $data['EditorTheme'] = $type['editor']['theme'] ?? 'vs-light';
                        $this->editorOptions[] = $column;
                        $this->editorConfig[] = true;
                        $fields[] = $stubs['editor']->fill($data, ['{%', '%}']);
                        $hasField = true;
                    }

                    if (!$hasField) {
                        if (in_array($type['type'], self::$numbers)) {
                            $data['Type'] = 'number';

                            $fields[] = $stubs['input']->fill($data, $delims);
                        } else {
                            if ($column == 'color' || $column == 'colors') {
                                $colorList[] = $column;
                                $fields[] = $stubs['color']->fill($data, $delims);
                            } else {
                                if (in_array($type['type'], self::$strings)) {
                                    $data['Type'] = 'text';
                                    $fields[] = $stubs['input']->fill($data, $delims);
                                } else {
                                    if (in_array($type['type'], self::$booleans)) {
                                        $fields[] = $stubs['boolean']->fill($data, $delims);
                                    } else {
                                        if (in_array($type['type'], ['select', 'enum', 'set', 'multiselect', 'tags'])) {
                                            $fields[] = $stubs['select']->fill($data, $delims);
                                        } else {
                                            if (in_array($type['type'], ['date', 'datetime', 'timestamp', 'dateTime'])) {
                                                $fields[] = $stubs['date']->fill($data, $delims);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (isset($resource['lockable']) && $resource['lockable'] == true) {
            $data = [
                'Column'            => 'is_locked',
                'Label'             => "Op slot zetten",
                'Required'          => '',
                'RequiredAttribute' => '',
            ];
            $fields[] = $stubs['boolean']->fill($data, $delims);
        }

        return [
            'relationOptions' => $relationOptions,
            'fields'          => $fields,
            'relationMethods' => $relationMethodsList,
            'resourceList'    => $resourceList,
            'colorList'       => $colorList,
        ];
    }

    /**
     * Attempt to find the relation model, if no relation model is found, return null.
     *
     * @param string $column
     * @param array $resource
     * @param string $namespace
     * @param string $model
     *
     * @return array|null
     */
    private function findRelation (string $column, array $resource, string $namespace, string $model)
    {
        if (isset($resource['relations'])) {
            $columnParts = explode('_', $column);
            array_pop($columnParts);

            if (count($columnParts) == 1) {
                $relatedModel = Str::studly(implode('_', $columnParts));
                $relatedNamespace = $resource['relations'][$relatedModel]['namespace'] ?? null;
            } else {
                $relatedModel = Str::studly(implode('_', $columnParts));
                $relatedNamespace = $resource['relations'][$relatedModel]['namespace'] ?? null;
            }

            foreach ($resource['relations'] as $relationModel => $relationAttr) {
                if ($relationModel == $relatedModel) {
                    return ['model' => $relationModel, 'attr' => $relationAttr];
                }

                if ($relationAttr['local'] === $column) {
                    return ['model' => $relationModel, 'attr' => $relationAttr];
                }
            }
        }

        return null;
    }

    /**
     * Get the relation Option method and fill in the stub.
     *
     * @param string $column
     * @param string $relatedModel
     * @param array $attr
     * @param string $model
     *
     * @param bool $modelId
     * @return mixed
     */
    protected function getRelationOption (string $column, string $relatedModel, array $attr, $model = null, $modelId = false)
    {
        $prefix = isset($attr['namespace']) ? Str::slug($attr['namespace']) . '/' : '';

        // in case of a hasOne relation, we want the current ID as well
        if ($model && isset($attr['namespace'])) {
            $prefix .= Str::slug(Str::plural(Str::snake($model))) . '/' . ($modelId ? "' + this.id + '/" : '');
        }

        $data = [
            'Model'  => Str::plural($relatedModel),
            'Url'    => $prefix . Str::slug(Str::plural(Str::snake($relatedModel))),
            'Column' => $column,
        ];

        return (new Stub(config("generators.stubs.fields.option-request")))->fill($data, ['{%', '%}']);
    }

    /**
     * @param $list
     *
     * @return string
     */
    private function addColors ($list)
    {
        if (empty($list)) {
            return '';
        }

        $output = "\n        palettes: {";
        $options = [];
        foreach ($list as $item) {
            $options[] = "$item: false";
        }
        $output .= "\n\t\t\t\t\t" . implode(",\n\t\t\t\t\t", $options);
        $output .= "\n\t\t\t\t},";

        $this->methods[] = (new Stub(config("generators.stubs.fields.option-request")))->fill([]);

        return $output;
    }

    /**
     * @return string
     */
    private function getMethods ()
    {
        return implode("\n", $this->methods);
    }

    /**
     * @return string
     */
    private function getEditorOptions ()
    {
        if (empty($this->editorOptions)) {
            return '';
        }
        return "editor: {
                    " . implode(" : '',\n\t\t\t\t", $this->editorOptions) . " : '',
                },";
    }

    /**
     * @return string
     */
    private function getEditorConfig ()
    {
        if (empty($this->editorConfig)) {
            return '';
        }

        return "editorConfig: {},";
    }

}

