<?php

return [

    'location' => [
        'migration'  => base_path(env("GENERATOR_MIGRATION_DIR", "database/migrations/")),
        'model'      => base_path(env("GENERATOR_MODEL_DIR", "app/Models/")),
        'controller' => base_path(env("GENERATOR_CONTROLLER_DIR", "app/Http/Controllers/")),
        'resource'   => base_path(env("GENERATOR_RESOURCE_DIR", "app/Resources/")),
        'stub'       => base_path(env("GENERATOR_STUB_DIR", "resources/generator-stubs/")),
        'yaml'       => base_path(env("GENERATOR_YAML_DIR", "resources/generators/")),
        'log'        => base_path(env("GENERATOR_LOG_DIR", "storage/logs/")),
        'view'       => base_path(env("GENERATOR_VIEW_DIR", "resources/generated-views")),
    ],

    'stubs' => [
        'controller'          => 'Controller',
        'migration'           => 'Migration',
        'model'               => 'Model',
        'resource'            => 'Resource',
        'overview'            => 'overview',
        'detail'              => 'detail',
        'form'                => 'form',
        'controller-many'     => 'controller-relation-many',
        'controller-one'      => 'controller-relation-one',
        'repository'          => 'Repository',
        'criteria'            => 'Criteria',
        'fields'              => [
            'bool'          => 'form-field-boolean',
            'color'         => 'form-field-color',
            'date'          => 'form-field-date',
            'editor'        => 'form-field-editor',
            'input'         => 'form-field-input',
            'select'        => 'form-field-select',
            'method-color'  => 'form-method-color',
            'method-editor' => 'form-method-editor',
        ],
        'form-option-request' => 'form-option-request',
        'overview-column'     => 'overview-column',
        'relation-model'      => 'relation-model',
    ],

];
