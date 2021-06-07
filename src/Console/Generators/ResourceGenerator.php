<?php
/**
 * Copyright (c) 2019.
 */

namespace Simianbv\Generators\Console\Generators;

use Exception;
use Illuminate\Support\Str;

/**
 * @class   ResourceGenerator
 * @package Simianbv\Generators\Console\Generators
 */
class ResourceGenerator extends ClassGenerator
{
    /**
     * @var string
     */

    protected $stubToUse = "Lightning-Resource";


    /**
     * @param $resource
     * @param $namespace
     * @param $model
     *
     * @return bool
     */
    public function create ($resource, $namespace, $model)
    {
        $this->resource = $resource;

        $fields = [];
        $filters = [];
        $actions = [];
        $uses = [];

        $p = config('generators.location.resource');
        if (!Str::endsWith($p, '/')) {
            $p .= "/";
        }
        $fields = [
            'FullNamespace' => 'App\\' . str_replace(['/'], ['\\'], $p) . $this->ns($namespace) . $model . 'Resource',
            'Model'         => $model,
            'ResourceClass' => $model . 'Resource',
            'Resource'      => $model . 'Resource',
            'Uses'          => implode("", $uses),
            'Fields'        => implode(',', $fields),
            'Filters'       => implode(", \n", $this->generateFilters($resource, $namespace, $model)),
            'Actions'       => implode(',', $actions),
        ];

        $stub = new Stub($this->stubToUse);
        return $stub->fill($fields);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * **
     *  FILTER GENERATION
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


    /**
     * Valid filter types to render inc. aliases
     * @var array
     */
    protected $filterTypes = [
        'autocomplete' => ['tag', 'multiselect'],
        'select'       => ['combobox', 'enum', 'set'],
        'text'         => [
            'mediumText',
            'largeText',
            'text',
            'string',
            'email',
        ],
        'number'       => [
            'int', 'integer', 'smallInt', 'smallInteger', 'mediumInt', 'mediumInteger', 'bigInt', 'bigInteger', 'increments', 'bigIncrements',
            'double',
            'float',
        ],
        'datetime'     => ['datetime', 'timestamp'],
        'date'         => ['date'],
        'bool'         => ['boolean'],
    ];

    /**
     * @var array
     */
    protected $filterOptions = [
        'tags'     => ['=', 'IN', 'NOT IN', '!='],
        'select'   => ['=', 'IN', 'NOT IN', '!='],
        'text'     => ['LIKE', '=', '!=', 'IS EMPTY', 'IS NOT EMPTY'],
        'number'   => ['>', '<', '>=', '<=', '=', '!=', 'IN', 'NOT IN', 'BETWEEN', 'LIKE'],
        'datetime' => ['=', '!=', 'IS AFTER', '<', '<=', '>', '>=', 'BETWEEN'],
        'date'     => ['=', '!=', 'IS AFTER', '<', '<=', '>', '>=', 'BETWEEN'],
        'bool'     => ['=', '!=', 'IS NULL'],
    ];


    /**
     * Build up the relations and corresponding models associated with the relation.
     *
     * @param array $resource
     * @param string $namespace
     * @param string $model
     *
     * @return array $filters
     */
    private function generateFilters (array $resource, string $namespace, string $model): array
    {
        $filters = [];

        if (isset($resource['columns'])) {
            foreach ($resource['columns'] as $field => $attr) {
                $type = $this->getFieldType($attr);
                $filterOptions = $this->filterOptions[$type];

                $options = '';
                if (($attr['type'] === 'enum' || $attr['type'] === 'set') && isset($attr['options'])) {
                    $options = "\n\t\t\t'options' => ['" . implode("', '", $attr['options']) . "'],";
                }

                $current = "\n\t\t'$field' => [
            'type' => '$type',
            'filter_options' => ['" . implode("', '", $filterOptions) . "'],
            'name' => '" . $field . "',
            'label' => '" . $field . "', $options\n\t\t]";
                $filters[] = $current;
            }
        }

        if (isset($resource['relations'])) {
            foreach ($resource['relations'] as $field => $attr) {
                $relationModel = $field;
                $relationField = Str::snake($field);

                $fullRelationModel = 'App\\Models\\' . $this->ns($attr['namespace'] ?? null) . $relationModel;

                // we're simply assuming the related model has already been created and get the corresponding table from there.
                if (class_exists($fullRelationModel)) {
                    $table = (new $fullRelationModel)->getTable();
                } else {
                    $table = '';
                }

                [$type, $options] = $this->determineFieldType($field, $attr, $namespace, $model);
                $filterOptions = $this->filterOptions[$type];

                $current = "\t\t\t'" . $relationField . "_id' => [
                        'type' => '$type',
                        'filter_options' => ['" . implode("', '", $filterOptions) . "'],
                        'name' => '" . $relationField . "_id',
                        'label' => '" . $field . "',
                        $options,
                        'relation' => [
                            'relation' => '{$attr['type']}',
                            'model' => '$fullRelationModel',
                            'select' => '*',
                            'searchable' => ['id'],
                            'joins' => [
                                'table' => '$table',
                                'local' => '{$attr['local']}',
                                'foreign' => '{$attr['foreign']}'
                            ],
                        ]
                    ]";

                $filters[] = $current;
            }
        }

        return $filters;
    }

    /**
     * @param $attr
     *
     * @return int|string
     */
    private function getFieldType (array $attr)
    {
        if (!isset($attr['type'])) {
            return 'text';
        }
        $type = $attr['type'];

        if (in_array($type, array_keys($this->filterTypes))) {
            return $type;
        }

        foreach ($this->filterTypes as $key => $options) {
            if (in_array($type, $options)) {
                return $key;
            }
        }

        return 'text';
    }

    /**
     * @param $relationModel
     * @param $relationAttributes
     * @param $namespace
     * @param $model
     *
     * @return array
     */
    private function determineFieldType (string $relationModel, array $relationAttributes, string $namespace, string $model)
    {
        // we can assume this is about the relation field, which can be either select or multi select and can have either options or autocomplete

        $appendix = null;
        $autocomplete = false;
        try {
            $fullRelationModel = 'App\\Models\\' . $this->ns($relationAttributes['namespace'] ?? null) . $relationModel;

            $count = 0;
            if (class_exists($fullRelationModel)) {
                $count = $fullRelationModel::count();
            }

            if ($count > 30) {
                $apiUrl = env('APP_URL') . '/' . Str::snake($this->dir($relationAttributes['namespace']) ?? '') . Str::slug(Str::snake(Str::plural($relationModel)));
                $autocomplete = true;

                $appendix = "'autocomplete' => ['url' => '$apiUrl']";
            }
        } catch (Exception $e) {
        }

        if ($relationAttributes['type'] == 'hasMany') {
            $type = 'tags';
        } else {
            $type = 'select';
        }

        if (!$appendix) {
            $appendix = "'options' => []";
        }


        return [$type, $appendix];
    }

}
