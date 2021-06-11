<?php
/**
 * @copyright (c) Simian B.V. 2019
 * @version       1.0.0
 */

namespace Simianbv\Generators;

use Illuminate\Support\ServiceProvider;
use Simianbv\Generators\Console\Commands\CreateResource;

/**
 * @class   JsonSchemaServiceProvider
 * @package Simianbv\JsonSchema
 */
class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register ()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot ()
    {
        $this->publishes([__DIR__ . '/../config/generators.php' => config_path('generators.php'),], "generator-config");
        $path = resource_path("generator-stubs") . '/';
        $this->publishes(
            [
                __DIR__ . '/../resources/stubs/form-field-boolean.stub'  => $path . 'form-field-boolean.stub',
                __DIR__ . '/../resources/stubs/form-field-color.stub'    => $path . 'form-field-color.stub',
                __DIR__ . '/../resources/stubs/form-field-date.stub'     => $path . 'form-field-date.stub',
                __DIR__ . '/../resources/stubs/form-field-editor.stub'   => $path . 'form-field-editor.stub',
                __DIR__ . '/../resources/stubs/form-field-input.stub'    => $path . 'form-field-input.stub',
                __DIR__ . '/../resources/stubs/form-field-select.stub'   => $path . 'form-field-select.stub',
                __DIR__ . '/../resources/stubs/form-method-color.stub'   => $path . 'form-method-color.stub',
                __DIR__ . '/../resources/stubs/form-method-editor.stub'  => $path . 'form-method-editor.stub',
                __DIR__ . '/../resources/stubs/form-option-request.stub' => $path . 'form-option-request.stub',
                __DIR__ . '/../resources/stubs/controller.stub'          => $path . 'controller.stub',
                __DIR__ . '/../resources/stubs/controller-has-many.stub' => $path . 'controller-has-many.stub',
                __DIR__ . '/../resources/stubs/controller-has-one.stub'  => $path . 'controller-has-one.stub',
                __DIR__ . '/../resources/stubs/criteria.stub'            => $path . 'criteria.stub',
                __DIR__ . '/../resources/stubs/detail.stub'              => $path . 'detail.stub',
                __DIR__ . '/../resources/stubs/form.stub'                => $path . 'form.stub',
                __DIR__ . '/../resources/stubs/migration.stub'           => $path . 'migration.stub',
                __DIR__ . '/../resources/stubs/Lightning-Model.stub'     => $path . 'model.stub',
                __DIR__ . '/../resources/stubs/overview.stub'            => $path . 'overview.stub',
                __DIR__ . '/../resources/stubs/overview-column.stub'     => $path . 'overview-column.stub',
                __DIR__ . '/../resources/stubs/model-relation.stub'      => $path . 'model-relation.stub',
                __DIR__ . '/../resources/stubs/repository.stub'          => $path . 'repository.stub',
                __DIR__ . '/../resources/stubs/resource.stub'            => $path . 'resource.stub',

            ], "generator-stubs"
        );

        if ($this->app->runningInConsole()) {
            $this->commands([CreateResource::class,]);
        }
    }
}
