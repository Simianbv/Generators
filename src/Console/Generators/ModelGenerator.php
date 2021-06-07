<?php
/**
 * @user          Merijn
 * @copyright (c) 2019.
 */

namespace Simianbv\Generators\Console\Generators;

use Illuminate\Support\Str;

/**
 * Class ModelGenerator
 * @package Simianbv\Generators\Console\Generators
 */
class ModelGenerator extends ClassGenerator
{
    /**
     * @var string
     */

    protected $stubToUse = "Lightning-Model";
    protected $relationStubToUse = "Lightning-relation-model";

    protected $fillables = [
        'Date',
        'Namespace',
        'Uses',
        'Fillables',
        'Relations',
    ];

    /**
     * @param $resource
     * @param $namespace
     * @param $model
     *
     * @return bool
     */
    public function create ($resource, $namespace, $model)
    {
        $stub = new Stub($this->stubToUse);
        $relationStub = new Stub($this->relationStubToUse);

        $this->resource = $resource;

        $fillables = [];
        $relations = [];
        $uses = [];
        $traits = [];
        $implements = [];

        if (isset($this->resource['lockable']) && $this->resource['lockable'] == true) {
            $uses[] = "use Simianbv\\JsonSchema\\Contracts\\IsLockable;\n";
            $uses[] = "use Simianbv\\JsonSchema\\Traits\\Lockable;\n";
            $implements[] = 'IsLockable';
            $traits[] = 'Lockable';
            $fillables[] = 'is_locked';
        }

        if (isset($this->resource['columns'])) {
            foreach ($this->resource['columns'] as $field => $attr) {
                if (
                    (
                        !isset($attr['fillable']) ||
                        (isset($attr['fillable']) && $attr['fillable'] == true)
                    )
                    && !isset($attr['primary'])
                ) {
                    $fillables[] = $field;
                }
            }
        }

        if (isset($this->resource['relations']) && is_array($this->resource['relations'])) {
            foreach ($this->resource['relations'] as $relationModel => $attr) {
                if (!isset($attr['type']) || !isset($attr['foreign']) || !isset($attr['local'])) {
                    $this->parent->error("Niet alle verplichte velden ( type, foreign, local, namespace) zijn ingevuld bij $model -> $relationModel");
                    continue;
                }

                if (isset($attr['model'])) {
                    $relationModel = $attr['model'];
                }
                $relationNamespace = isset($attr['namespace']) ? $this->trim($attr['namespace']) : null;
                $fnName = $attr['function'] ?? '';
                $uses[] = 'use App\\Models\\' . $this->ns($relationNamespace) . $relationModel . ";\n";
                $uses[] = 'use Illuminate\Database\Eloquent\Relations\\' . Str::studly($attr['type']) . ";\n";
                $relations[] = $this->fillRelationStub($model, $relationModel, $attr, $fnName);
            }
        }

        $fields = [
            'Date'       => date('Y'),
            'ModelTable' => strtolower($namespace) . '_' . strtolower(Str::snake(Str::plural($model))),
            'Namespace'  => 'App\\Models' . (strlen($namespace) > 0 ? '\\' . $namespace : ''),
            'Model'      => $model,
            'Uses'       => implode("", array_unique($uses)),
            'Fillables'  => "'" . implode("', \n\t\t'", $fillables) . "'\n",
            'Relations'  => implode('', $relations),
            'Traits'     => (!empty($traits) ? 'use ' . implode(', ', $traits) . ';' : ''),
            'Implements' => (!empty($implements) ? ' implements ' . implode(', ', $implements) : ''),
        ];

        return $stub->fill($fields);
    }

    /**
     * @param string $model
     * @param string $relationModel
     * @param array $attr
     * @param string $fnName
     * @return mixed
     */
    public function fillRelationStub (string $model, string $relationModel, array $attr, $fnName = '')
    {
        if (!isset($attr['type'])) {
            return '';
        }

        if ($fnName === '') {
            if ($attr['type'] === 'hasMany') {
                $fnName = Str::camel(Str::plural($relationModel));
            } else {
                $fnName = Str::camel(Str::singular($relationModel));
            }
        }

        $fields = [
            'ModelClass'           => $model,
            'RelatedModelClass'    => $relationModel,
            'RelationType'         => Str::camel($attr['type']),
            'RelatedModelFunction' => $fnName,
        ];

        $stub = new Stub($this->relationStubToUse);
        return $stub->fill($fields);
    }
}
