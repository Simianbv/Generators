<?php
/**
 * Copyright (c) 2019.
 */

/**
 * PhpStorm
 * @user    merijn
 * @date    05/04/2019
 * @time    12:33
 * @version ${VERSION}
 */

namespace Simianbv\Generators\Console\Generators;


use Illuminate\Support\Str;

/**
 * Class OverviewGenerator
 * @package Simianbv\Generators\Console\Generators
 */
class OverviewGenerator extends ClassGenerator
{

    protected $stub = "overview";
    protected $columnStub = "overview-column";
    protected $detailStub = "detail";


    public function __construct ($stub = null, $columnStub = null, $detailStub = null)
    {
        if (!$stub) {
            $stub = config("generators.stubs.overview");
        }
        if (!$columnStub) {
            $columnStub = config("generators.stubs.overview-column");
        }
        if (!$detailStub) {
            $detailStub = config("generators.stubs.detail");
        }

        if ($stub && $stub !== $this->stub) {
            $this->stub = $stub;
        }

        if ($columnStub && $columnStub !== $this->columnStub) {
            $this->columnStub = $columnStub;
        }

        if ($detailStub && $detailStub !== $this->detailStub) {
            $this->detailStub = $detailStub;
        }
    }

    /**
     * @param $resource
     * @param $namespace
     * @param $model
     *
     * @return array
     */
    public function create ($resource, $namespace, $model)
    {
        $this->resource = $resource;

        $columns = $this->generateColumns($resource, $namespace, $model);

        $url = ($namespace != '' ? Str::slug(strtolower($namespace)) . '/' : '') . Str::slug(Str::snake(Str::plural($model))) . '/';

        $overviewFields = [
            'LabelSingle'    => $resource['singular'] ?? Str::singular($model),
            'LabelPlural'    => $resource['plural'] ?? Str::plural($model),
            'ModelSingle'    => $model,
            'ModelUrl'       => $url,
            'ModelPlural'    => Str::plural($model),
            'ModelLink'      => Str::slug(($namespace != '' ? $namespace . '-' : '') . Str::snake($model)),
            'Acl'            => strtolower($namespace) . '.' . Str::slug(Str::snake($model)),
            'ModelNamespace' => $this->ns($namespace) . '\\' . $model,
            'Columns'        => "\n" . implode("", $columns),
        ];

        $detailFields = [
            'Model' => $model,
            'Url'   => $url,
        ];

        $overviewFile = ucfirst(Str::camel($model) . "Overview.vue");
        $detailFile = ucfirst(Str::camel($model) . "Detail.vue");

        $overviewComponent = ucfirst(strtolower($namespace) . Str::plural($model)) . "Overview";
        $detailComponent = ucfirst(strtolower($namespace) . $model) . "Detail";

        $namePrefix = ($namespace != '' ? strtolower($namespace) . '-' : '');

        $overviewRouteName = $namePrefix . strtolower(ucfirst(Str::slug(Str::snake(Str::plural($model))) . "-overview"));
        $detailRouteName = $namePrefix . strtolower(ucfirst(Str::slug(Str::snake($model)) . "-detail"));

        $prefix = ($namespace != '' ? strtolower($namespace) . '/' : '');


        $this->parent->addFrontendImport('const ' . $overviewComponent . ' = () => import(/* webpackChunkName: "' . strtolower($namespace) . '" */ "./views/' . strtolower($namespace) . '/' . $overviewFile . '")');
        $this->parent->addFrontendImport('const ' . $detailComponent . ' = () => import(/* webpackChunkName: "' . strtolower($namespace) . '" */ "./views/' . strtolower($namespace) . '/' . $detailFile . '")');
        $this->parent->addFrontendRoute("{path: '/" . $prefix . Str::slug(Str::snake(Str::plural($model))) . "', name: '" . $overviewRouteName . "', component: " . $overviewComponent . ", props: { default: false}}, ");
        $this->parent->addFrontendRoute("{path: '/" . $prefix . Str::slug(Str::snake(Str::plural($model))) . "/:id', name: '" . $detailRouteName . "', component: " . $detailComponent . ", props: true}, ");

        $overviewStub = new Stub($this->stub);
        $detailStub = new Stub($this->detailStub);

        return [
            'overview' => $overviewStub->fill($overviewFields, ['{%', '%}']),
            'detail'   => $detailStub->fill($detailFields, ['{%', '%}']),
        ];
    }

    /**
     * @param $resource
     * @param $namespace
     * @param $model
     *
     * @return array
     */
    private function generateColumns ($resource, $namespace, $model)
    {
        $columns = [];
        $stub = new Stub($this->columnStub);
        $sortableTypes = ['string', 'integer', 'bigInteger', 'float', 'increments', 'bigIncrements', 'boolean', 'bool'];

        if (isset($resource['columns'])) {
            foreach ($resource['columns'] as $column => $attr) {
                if (!Str::endsWith($column, ['_id'])) {
                    if ($attr['type'] == 'bool' || $attr['type'] == 'boolean' || $attr['type'] == 'smallInt') {
                        $content = '<i class="far fa-check-circle" v-if="props.row.' . $column . '"></i>';
                    } else {
                        $content = '{{ props.row.' . $column . ' }}';
                    }

                    $fields = [
                        'Field'    => $column,
                        'Label'    => __(ucfirst(implode(' ', explode('_', $column)))),
                        'Sortable' => $column == 'id' || in_array($attr['type'], $sortableTypes) ? 'sortable' : '',
                        'Content'  => $content,
                    ];
                    $columns[] = $stub->fill($fields, ['{%', '%}']);
                }
            }
        }
        return $columns;
    }
}

