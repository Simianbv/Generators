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

    protected $stubToUse = "Lightning-overview";
    protected $columnStubToUse = "Lightning-overview-column";
    protected $detailStubToUse = "Lightning-detail";


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
            'ModelSingle'    => $model,
            'ModelUrl'       => $url,
            'ModelPlural'    => Str::plural($model),
            'ModelLink'      => Str::slug(($namespace != '' ? $namespace . '-' : '') . Str::snake($model)),
            'Acl'            => strtolower($namespace) . '.' . Str::slug(Str::snake($model)),
            'ModelNamespace' => $this->ns($namespace) . $model,
            'Columns'        => "\n" . implode("", $columns),
        ];

        $detailFields = [
            'Model' => $model,
            'Url'   => $url,
        ];

        $overviewFile = ucfirst(Str::slug(Str::snake(Str::plural($model))) . "-overview");
        $detailFile = ucfirst(Str::slug(Str::snake($model)) . "-detail");

        $overviewComponent = ucfirst(strtolower($namespace) . Str::plural($model)) . "Overview";
        $detailComponent = ucfirst(strtolower($namespace) . $model) . "Detail";

        $prefix = ($namespace != '' ? strtolower($namespace) . '/' : '');
        $namePrefix = ($namespace != '' ? strtolower($namespace) . '-' : '');

        $this->parent->addFrontendImport('const ' . $overviewComponent . ' = () => import(/* webpackChunkName: "' . strtolower($namespace) . '" */ "./views/' . strtolower($namespace) . '/' . $overviewFile . '.vue")');
        $this->parent->addFrontendImport('const ' . $detailComponent . ' = () => import(/* webpackChunkName: "' . strtolower($namespace) . '" */ "./views/' . strtolower($namespace) . '/' . $detailFile . '.vue")');
        $this->parent->addFrontendRoute("{path: '/" . $prefix . Str::slug(Str::snake(Str::plural($model))) . "', name: '" . $namePrefix . strtolower($overviewFile) . "', component: " . $overviewComponent . ", props: { default: false}}, ");
        $this->parent->addFrontendRoute("{path: '/" . $prefix . Str::slug(Str::snake(Str::plural($model))) . "/:id', name: '" . $namePrefix . strtolower($detailFile) . "', component: " . $detailComponent . ", props: true}, ");

        $overviewStub = new Stub($this->stubToUse);
        $detailStub = new Stub($this->detailStubToUse);

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
        $stub = new Stub($this->columnStubToUse);
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

