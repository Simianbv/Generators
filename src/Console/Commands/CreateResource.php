<?php
/**
 * @copyright (c) 2019
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

// 1. Fix the controller's relation fields, hasMany and hasOne, belongsTo and belongsToMany

3. Add the Resource to the Lightning directory, including fields, relations, filters and actions
4. Create groups and permissions based on the models provided in the config file.

optional Add a Repository to the Lightning directory, base

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace Simianbv\Generators\Console\Commands;

use Simianbv\Generators\Console\Generators\ControllerGenerator;
use Simianbv\Generators\Console\Generators\FormGenerator;
use Simianbv\Generators\Console\Generators\MigrationGenerator;
use Simianbv\Generators\Console\Generators\ModelGenerator;
use Simianbv\Generators\Console\Generators\OverviewGenerator;
use Simianbv\Generators\Console\Generators\ResourceGenerator;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * @class   CreateResource
 * @package App\Console\Commands
 */
class CreateResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simian:make:resource {name}';
    /**
     * A string containing all the routes you need to add.
     * @var array
     */
    protected $routes = [];
    /**
     * @var array
     */
    protected $files_generated = [];
    /**
     * @var int
     */
    protected static $PAD_LENGTH = 30;
    /**
     * A string containing all the routes you need to add.
     * @var array
     */
    protected $frontend_routes = [];

    /**
     * A string containing all the imports you need to add.
     * @var array
     */
    protected $frontend_imports = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full stack for the models defined in the yml file, ie.
    create a model, resource, repository and controller for each model defined';
    /**
     * @var ModelGenerator
     */
    protected $modelGenerator;
    /**
     * @var ControllerGenerator
     */
    protected $controllerGenerator;
    /**
     * @var MigrationGenerator
     */
    protected $migrationGenerator;
    /**
     * @var ResourceGenerator
     */
    protected $resourceGenerator;

    /**
     * @var OverviewGenerator
     */
    protected OverviewGenerator $overviewGenerator;
    /**
     * @var FormGenerator
     */
    protected $formGenerator;

    /**
     * Create a new command instance.
     *
     * @param FormGenerator $formGenerator
     * @param ModelGenerator $modelGenerator
     * @param ControllerGenerator $controllerGenerator
     * @param ResourceGenerator $resourceGenerator
     * @param OverviewGenerator $overviewGenerator
     * @param MigrationGenerator $migrationGenerator
     */
    public function __construct (
        //AclGenerator $aclGenerator,
        FormGenerator $formGenerator,
        ModelGenerator $modelGenerator,
        ControllerGenerator $controllerGenerator,
        ResourceGenerator $resourceGenerator,
        OverviewGenerator $overviewGenerator,
        MigrationGenerator $migrationGenerator)
    {
        parent::__construct();
//        $this->aclGenerator = $aclGenerator;
//        $this->aclGenerator->setCallee($this);
        $this->formGenerator = $formGenerator;
        $this->formGenerator->setCallee($this);
        $this->modelGenerator = $modelGenerator;
        $this->modelGenerator->setCallee($this);
        $this->controllerGenerator = $controllerGenerator;
        $this->controllerGenerator->setCallee($this);
        $this->migrationGenerator = $migrationGenerator;
        $this->migrationGenerator->setCallee($this);
        $this->resourceGenerator = $resourceGenerator;
        $this->resourceGenerator->setCallee($this);
        $this->overviewGenerator = $overviewGenerator;
        $this->overviewGenerator->setCallee($this);
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle ()
    {
        $this->info("Starting work on the yaml first.. Someone, please.. Think of the hamsters 😱");
        $data = $this->parseYaml($this->argument('name'));

        if (!$data) {
            $this->error('Unable to continue, no data present in the yaml file.');
        }

        $idx = 1;

        // perform the creation in several passes, that way, we can create all the base models first and their corresponding
        // migrations, before moving on to the creation of controllers and the resources
        foreach ($data as $resourceName => $resource) {
            try {
                $this->createFirstPass($resourceName, $resource, $idx);
            } catch (Exception $e) {
                $this->revert();
                throw $e;
            }
            $idx++;
        }

        // on the second pass, create the controllers and resource objects
        foreach ($data as $resourceName => $resource) {
            try {
                $this->createSecondPass($resourceName, $resource);
            } catch (Exception $e) {
                $this->revert();
                throw $e;
            }
            $idx++;
        }

        try {
            $this->output();
        } catch (Exception $e) {
            $this->revert();
            throw $e;
        }
    }

    /**
     * Write the output to the console and optionally to a logfile as well.
     * @return void
     */
    private function output ()
    {
        $lines = [
            "All files generated:\n\n",
        ];

        $lines += $this->files_generated;

        if (!empty($this->routes)) {
            $line = "\n ⚡️Copy over these routes to your web/api folder to make sure the controllers actually work and listen";
            $this->line("<fg=magenta>$line</>\n");
            $lines[] = $line;

            foreach ($this->routes as $route) {
                $this->line('<fg=yellow>' . $route . '</>');
                $lines[] = $route;
            }
        }

        if (!empty($this->frontend_routes)) {
            $line = "\n ⚡️Front-end ~ Copy over these imports at the top of your router.js file in the front-end to make sure the routes have the correct files";
            $this->line("<fg=magenta;bg=yellow>$line</>\n");
            $lines[] = $line;

            foreach ($this->frontend_imports as $import) {
                $this->line('<fg=yellow>' . $import . '</>');
                $lines[] = $import;
            }

            $line = "\n ⚡️Front-end ~ Copy over these routes to your router.js file in the front-end to make the links followable";
            $this->line("<fg=blue;bg=yellow>$line</>\n");
            $lines[] = $line;
            foreach ($this->frontend_routes as $route) {
                $this->line('<fg=yellow>' . $route . '</>');
                $lines[] = $route;
            }
        }

        try {
            $this->writeToLogFile($lines);
        } catch (Exception $e) {
            $this->warn("Unable to write the output to a log file!");
        }
    }

    private function writeToLogFile (array $lines)
    {
        $logPath = config('generators.location.log');
        $logFile = date('Y-m-d_H:i:s') . "-" . $this->argument('name') . '.log';

        file_put_contents($logPath . $logFile, implode("\n", $lines));
    }

    /**
     * @param     $resourceName
     * @param     $resource
     * @param int $index
     */
    private function createFirstPass ($resourceName, $resource, $index = 0)
    {
        $model = ucfirst($this->trim($resourceName));
        $namespace = isset($resource['namespace']) ? $this->trim($resource['namespace']) : '';
        $this->createModelIfNotExisting($namespace, $model, $resource, $index);
    }

    /**
     * @param     $resourceName
     * @param     $resource
     * @param int $index
     */
    private function createSecondPass ($resourceName, $resource)
    {
        $model = ucfirst($this->trim($resourceName));
        $namespace = isset($resource['namespace']) ? $this->trim($resource['namespace']) : '';


        $this->createControllerIfNotExisting($namespace, $model, $resource);
        // @todo: resources worden niet gebruikt
        // $this->createResourceIfNotExisting($namespace, $model, $resource);

        // @todo hier
        $this->createOverview($namespace, $model, $resource);
        $this->createForm($namespace, $model, $resource);
        // $this->createAcl($namespace, $model, $resource);
    }

    /**
     * Create the Acl rules for the given resource.
     *
     * @param $namespace
     * @param $model
     * @param $resource
     *
     * @return void
     */
    private function createAcl ($namespace, $model, $resource)
    {
        $this->aclGenerator->create($resource, $namespace, $model);
    }

    /**
     * Create the form for the given resource.
     *
     * @param $namespace
     * @param $model
     * @param $resource
     *
     * @return void
     */
    private function createForm ($namespace, $model, $resource)
    {
        $dir = config('generators.location.view');
        if (!Str::endsWith($dir, "/")) {
            $dir .= "/";
        }
        $file = ucfirst(Str::camel($model) . "Form.vue");
        $path = $dir . $file;

        $this->info(str_pad("Creating Form:", self::$PAD_LENGTH) . $path);
        $contents = $this->formGenerator->create($resource, $namespace, $model);
        file_put_contents($path, $contents);
        $this->files_generated[] = $path;
    }

    /**
     * Create the Overview for the given resource.
     *
     * @param $namespace
     * @param $model
     * @param $resource
     *
     * @return void
     */
    private function createOverview ($namespace, $model, $resource)
    {
        $dir = config('generators.location.view');
        if (!Str::endsWith($dir, "/")) {
            $dir .= "/";
        }
        $overviewFile = ucfirst(Str::camel($model) . "Overview.vue");
        $detailFile = ucfirst(Str::camel($model) . "Detail.vue");
        $overviewPath = $dir . $overviewFile;
        $detailPath = $dir . $detailFile;

        $this->info(str_pad("Creating Overview:", self::$PAD_LENGTH) . $overviewPath);
        $this->info(str_pad("Creating Detail:", self::$PAD_LENGTH) . $detailPath);
        $contents = $this->overviewGenerator->create($resource, $namespace, $model);

        file_put_contents($overviewPath, $contents['overview']);
        file_put_contents($detailPath, $contents['detail']);

        $this->files_generated[] = $overviewPath;
        $this->files_generated[] = $detailPath;
    }

    /**
     * Create the resource if the file does not exist yet.
     *
     * @param $namespace
     * @param $model
     * @param $resource
     *
     * @return void
     */
    private function createResourceIfNotExisting ($namespace, $model, $resource)
    {
        $resourceClass = $model . 'Resource';
        $file = $resourceClass . '.php';
        $dir = config('generators.location.resource') . $this->dir($namespace);

        $resourcePath = $dir . $file;

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir);
        }

        if (!File::exists($resourcePath)) {
            $this->info(str_pad("Creating Resource:", self::$PAD_LENGTH) . $resourcePath);
            $resourceContent = $this->resourceGenerator->create($resource, $namespace, $model);
            file_put_contents($resourcePath, $resourceContent);
            $this->files_generated[] = $resourcePath;
        }
    }

    /**
     * Create the controller class for the resource if the controller doesn't exist yet.
     *
     * @param $namespace
     * @param $model
     * @param $resource
     *
     * @return void
     */
    private function createControllerIfNotExisting ($namespace, $model, $resource)
    {
        $controllerClass = $model . 'Controller';
        $dir = config('generators.location.controller') . $this->dir($namespace);
        $file = $controllerClass . '.php';
        $controllerPath = $dir . $file;

        if (!File::exists($controllerPath)) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir);
            }

            Artisan::call('make:request', [
                'name' => $this->ns($namespace) . 'Create' . $model . 'Request',
            ]);
            Artisan::call('make:request', [
                'name' => $this->ns($namespace) . 'Update' . $model . 'Request',
            ]);

            $this->info(str_pad("Creating Controller:", self::$PAD_LENGTH) . $controllerPath);
            $controllerContent = $this->controllerGenerator->create($resource, $namespace, $model);
            file_put_contents($controllerPath, $controllerContent);
            $this->files_generated[] = $controllerPath;
        }
    }

    /**
     *
     * Create the model, the migration and return the model once created.
     *
     * @param     $namespace
     * @param     $model
     * @param     $resource
     *
     * @param int $index
     *
     * @return bool|void
     */
    private function createModelIfNotExisting ($namespace, $model, $resource, $index = 0)
    {
        $dir = config('generators.location.model') . $this->dir($namespace);
        $file = $model . '.php';
        $modelPath = $dir . $file;

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir);
        }

        if ($index < 10) {
            $index = '0' . $index;
        }
        $migrationFileName = date('Y_m_d') . '_00' . $index . '00_' . strtolower($namespace) . '_create_' . Str::snake($model) . '_table';

        $dir = config('generators.location.migration');
        $file = $migrationFileName . '.php';

        $migrationPath = $dir . $file;

        if (!File::exists($modelPath)) {
            $this->info(str_pad("Creating Migration:", self::$PAD_LENGTH) . $migrationPath);
            $migrationContent = $this->migrationGenerator->create($resource, $namespace, $model);
            file_put_contents($migrationPath, $migrationContent);
            $this->files_generated[] = $migrationPath;

            $this->info(str_pad("Creating Model:", self::$PAD_LENGTH) . $modelPath);
            $modelContent = $this->modelGenerator->create($resource, $namespace, $model);
            file_put_contents($modelPath, $modelContent);
            $this->files_generated[] = $modelPath;
        }
    }

    /**
     * Parse the Yaml input file
     *
     * Parse the Yaml file, check if the file exists and return whatever it was able to parse.
     *
     * @param string $file
     *
     * @return mixed
     * @throws Exception
     */
    private function parseYaml (string $file)
    {
        $dir = config('generators.location.yaml');
        if (!Str::endsWith($dir, '/')) {
            $dir .= '/';
        }
        $filesToTest = [
            $dir . $file . '.yml',
            $dir . $file . '.yaml',
        ];

        $fileCount = count($filesToTest);
        foreach ($filesToTest as $filePath) {
            if (!File::exists($filePath)) {
                $fileCount--;
                continue;
            }
            $yaml = File::get($filePath);
            return Yaml::parse($yaml);
        }

        throw new Exception("Unable to process the file, the file does not exist. We've looked at: " . print_r($filesToTest, true));
    }

    /**
     * Add a route to the output which needs to be copied over.
     *
     * @param string $type
     * @param string $route
     * @param string $controller
     */
    public function addRoute (string $type, string $route = '', string $controller = '')
    {
        if ($route == '' || $controller == '') {
            $this->routes[] = $type;
        } else {
            $this->routes[] = "" . 'Route::' . $type . '("' . $route . '", ' . $controller . ');';
        }
    }

    /**
     * Add a route to the output which needs to be copied over.
     *
     * @param string $route
     */
    public function addFrontendRoute (string $route)
    {
        $this->frontend_routes[] = $route;
    }

    /**
     * Add a import to the output which needs to be copied over.
     *
     * @param string $import
     */
    public function addFrontendImport (string $import)
    {
        $this->frontend_imports[] = $import;
    }

    /**
     * Trim the value from all slashes
     *
     * @param string $value
     * @param string $trim defaults to \
     *
     * @return string
     */
    private function trim (string $value, string $trim = '\\')
    {
        return rtrim(trim($value, $trim), $trim);
    }

    /**
     * Return the namespace if the namespace is set, otherwise, return an empty string
     *
     * @param string $ns
     *
     * @return string
     */
    private function ns (string $ns): string
    {
        return strlen($ns) > 0 ? $ns . '\\' : '';
    }

    /**
     * @param string $ns
     *
     * @return string
     */
    private function dir (string $ns): string
    {
        return strlen($ns) > 0 ? $ns . '/' : '';
    }

    /**
     * Revert the creation of all the files, in case something went wrong
     * @return void
     */
    private function revert ()
    {
        $this->warn("Reverting back, deleting all the created files");
        foreach ($this->files_generated as $file) {
            $this->warn("Deleting file: " . $file);
        }
        File::delete($this->files_generated);
    }
}
