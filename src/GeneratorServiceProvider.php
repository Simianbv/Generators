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
                __DIR__ . '/../resources/stubs/form-field-boolean.stub'                 => $path . 'form-field-boolean.stub',
                __DIR__ . '/../resources/stubs/form-field-color.stub'                   => $path . 'form-field-color.stub',
                __DIR__ . '/../resources/stubs/form-field-date.stub'                    => $path . 'form-field-date.stub',
                __DIR__ . '/../resources/stubs/form-field-editor.stub'                  => $path . 'form-field-editor.stub',
                __DIR__ . '/../resources/stubs/form-field-input.stub'                   => $path . 'form-field-input.stub',
                __DIR__ . '/../resources/stubs/form-field-select.stub'                  => $path . 'form-field-select.stub',
                __DIR__ . '/../resources/stubs/form-method-color.stub'                  => $path . 'form-method-color.stub',
                __DIR__ . '/../resources/stubs/form-method-editor.stub'                 => $path . 'form-method-editor.stub',
                __DIR__ . '/../resources/stubs/form-option-request.stub'                => $path . 'form-option-request.stub',
                __DIR__ . '/../resources/stubs/Lightning-Controller.stub'               => $path . 'Lightning-Controller.stub',
                __DIR__ . '/../resources/stubs/Lightning-controller-relation-many.stub' => $path . 'Lightning-controller-relation-many.stub',
                __DIR__ . '/../resources/stubs/Lightning-controller-relation-one.stub'  => $path . 'Lightning-controller-relation-one.stub',
                __DIR__ . '/../resources/stubs/Lightning-Criteria.stub'                 => $path . 'Lightning-Criteria.stub',
                __DIR__ . '/../resources/stubs/Lightning-detail.stub'                   => $path . 'Lightning-detail.stub',
                __DIR__ . '/../resources/stubs/Lightning-form.stub'                     => $path . 'Lightning-form.stub',
                __DIR__ . '/../resources/stubs/Lightning-Migration.stub'                => $path . 'Lightning-Migration.stub',
                __DIR__ . '/../resources/stubs/Lightning-Model.stub'                    => $path . 'Lightning-Model.stub',
                __DIR__ . '/../resources/stubs/Lightning-overview.stub'                 => $path . 'Lightning-overview.stub',
                __DIR__ . '/../resources/stubs/Lightning-overview-column.stub'          => $path . 'Lightning-overview-column.stub',
                __DIR__ . '/../resources/stubs/Lightning-relation-model.stub'           => $path . 'Lightning-relation-model.stub',
                __DIR__ . '/../resources/stubs/Lightning-Repository.stub'               => $path . 'Lightning-Repository.stub',
                __DIR__ . '/../resources/stubs/Lightning-Resource.stub'                 => $path . 'Lightning-Resource.stub',

            ], "generator-stubs"
        );

        if ($this->app->runningInConsole()) {
            $this->commands([CreateResource::class,]);
        }
    }
}
