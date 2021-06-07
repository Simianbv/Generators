<?php
/**
 * @user          merijn
 * @copyright (c) 2019.
 */

namespace Simianbv\Generators\Console\Generators;


use Illuminate\Support\Str;

/**
 * Class ControllerGenerator
 * @package Simianbv\Generators\Console\Generators
 */
class ControllerGenerator extends ClassGenerator
{

    protected static $NS = "App\\Http\\Controllers\\";

    protected $stubToUse = "Lightning-Controller";
    protected $hasManyStubToUse = "Lightning-Controller-relation-many";
    protected $hasOneStubToUse = "Lightning-Controller-relation-one";

    /**
     * @var string
     */
    private $stub;

    /**
     * @var array
     */
    private $uses;

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

        $this->uses = [];

        $fields = [
            'Class'           => $model . 'Controller',
            'Model'           => $model,
            'ModelLabel'      => $resource['label'] ?? $model,
            'NamespaceSingle' => $this->ns($namespace),
            'Namespace'       => 'App\\Http\\Controllers\\' . $namespace,
            'FullModelClass'  => 'App\\Models\\' . $this->ns($namespace) . $model,
            'ModelClass'      => $model,
            'ModelVariable'   => Str::camel($model),
            'RelatedFields'   => implode("", $this->fillRelationStubs($resource, $namespace, $model)),
        ];

        $fields['Uses'] = implode('', array_unique($this->uses));

        $url = Str::slug($namespace) . '/' . Str::slug(Str::snake(Str::plural($model)));
        $params = static::$NS . $this->ns($namespace) . $model . 'Controller::class';
        $this->parent->addRoute('Route::apiResource("' . $url . '", ' . $params . ');');

        $stub = new Stub($this->stubToUse);
        return $stub->fill($fields);
    }

    /**
     * @param $resource
     * @param $namespace
     * @param $model
     * @return array
     */
    private function fillRelationStubs ($resource, $namespace, $model)
    {
        $relations = [];

        if (isset($resource['relations'])) {
            foreach ($resource['relations'] as $relationModel => $attr) {
                if (isset($attr['model'])) {
                    $relationModel = $attr['model'];
                }
                if (isset($attr['type'])) {
                    switch ($attr['type']) {
                        case 'hasMany':
                            $relations[] = $this->fillHasManyStub($namespace, $model, $relationModel, $attr);
                            break;
                        case 'hasOne':
                            $relations[] = $this->fillHasOneStub($resource, $namespace, $model, $relationModel, $attr);
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * @param $namespace
     * @param $model
     * @param $relationModel
     * @param $attr
     * @return string
     */
    private function fillHasManyStub ($namespace, $model, $relationModel, $attr): string
    {
        $relatedNamespace = 'App\\Models\\' . $this->ns($attr['namespace'] ?? $namespace) . $relationModel;

        $this->uses[] = 'use ' . $relatedNamespace . ";\n";

        if (isset($attr['function'])) {
            $relatedModelFunction = $attr['function'];
        } else {
            if (isset($attr['model'])) {
                $relatedModelFunction = Str::plural(Str::camel($attr['model']));
            } else {
                $relatedModelFunction = Str::plural(Str::camel($relationModel));
            }
        }

        $fields = [
            'ModelClass'           => $model,
            'RelatedModelFunction' => $relatedModelFunction,
            'RelatedModel'         => Str::camel(Str::plural($relationModel)),
            'RelatedModelClass'    => $relationModel,
            'ModelVariable'        => Str::camel($model),
            'RelatedForeignKey'    => $attr['foreign'],
        ];

        // add the route to add to the routes file as well..
        $parentRoute = $this->dir(Str::slug($namespace)) . Str::slug(Str::snake($model)) . '/{' . Str::camel($model) . '}/' . Str::slug(Str::snake(Str::plural($relationModel)));

        $params = '[' . static::$NS . $this->ns($namespace) . $model . 'Controller::class, "' . Str::camel($relatedModelFunction) . '"]';

        $this->parent->addRoute('get', $parentRoute, $params);


        // fill up the stub with the fields defined above

        $stub = new Stub($this->hasManyStubToUse);
        return $stub->fill($fields);
    }

    /**
     * @param $resource
     * @param $namespace
     * @param $model
     * @param $relationModel
     * @param $attr
     * @return string
     */
    private function fillHasOneStub ($resource, $namespace, $model, $relationModel, $attr): string
    {
        $relatedNamespace = 'App\\Models\\' . $this->ns($attr['namespace'] ?? null) . $relationModel;

        $this->uses[] = 'use ' . $relatedNamespace . ";\n";

        if (isset($attr['function'])) {
            $relatedModelFunction = Str::camel($attr['function']);
        } else {
            if (isset($attr['model'])) {
                $relatedModelFunction = Str::camel($attr['model']);
            } else {
                $relatedModelFunction = Str::camel($relationModel);
            }
        }

        $fields = [
            'RelatedModelFunction' => $relatedModelFunction,
            'ModelClass'           => $model,
            'ModelLabel'           => $resource['label'] ?? $model,
            'RelatedModel'         => Str::camel($relationModel),
            'RelatedModelClass'    => $relationModel,
            'ModelVariable'        => Str::camel($model),
            'RelatedForeignKey'    => $attr['foreign'],
            'LocalKey'             => $attr['local'],
        ];

        // Add the has One relation as a route as well
        $parentRoute = $this->dir(Str::slug($namespace)) . Str::slug(Str::snake($model)) . '/{' . Str::camel($model) . '}/' . Str::slug(Str::snake($relatedModelFunction));

        $params = '[' . static::$NS . $this->ns($namespace) . $model . 'Controller::class, "' . Str::camel($relatedModelFunction) . '"]';

        $this->parent->addRoute('get', $parentRoute, $params);

        $stub = new Stub($this->hasOneStubToUse);
        return $stub->fill($fields);
    }
}
