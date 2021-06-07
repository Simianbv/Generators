<?php
/**
 * @autor         Merijn
 * @copyright (c) 2019.
 */

namespace Simianbv\Generators\Console\Generators;

use Simianbv\Generators\Console\Commands\CreateResource;
use Illuminate\Console\Command;

/**
 * Class ClassGenerator
 * @package Simianbv\Generators\Console\Generators
 */
class ClassGenerator
{
    /**
     * @var string
     */
    protected $location = '';

    /**
     * @var string
     */
    protected $stubLocation = '';

    /**
     * @var array
     */
    protected $fillables = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $resource;

    /**
     * @var string[]
     */
    protected $output = [];

    /**
     * @var CreateResource
     */
    protected $parent;

    /**
     * ClassGenerator constructor.
     *
     * @param       $resource
     * @param array $fields
     */
    public function initialize ($resource, array $fields = [])
    {
        if (!empty($fields)) {
            $this->fill($fields);
        }
        $this->resource = $resource;
    }

    /**
     * @param array $fields
     */
    public function fill (array $fields)
    {
        foreach ($fields as $key => $value) {
            if (in_array($key, $this->fillables)) {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * @param array $fields
     * @param       $content
     * @param array $delimiters
     *
     * @return mixed
     */
    public function fillStub (array $fields, $content, $delimiters = ['{{', '}}'])
    {
        $keys = array_map(function ($key) use ($delimiters) {
            return $delimiters[0] . $key . $delimiters[1];
        }, array_keys($fields));

        return str_replace($keys, array_values($fields), $content);
    }

    /**
     * Trim the value from all slashes
     *
     * @param string $value
     * @param string $trim defaults to \
     *
     * @return string
     */
    protected function trim (string $value, string $trim = '\\')
    {
        return rtrim(trim($value, $trim), $trim);
    }

    /**
     * @param Command $class
     */
    public function setCallee ($class)
    {
        $this->parent = $class;
    }

    /**
     * Return the namespace if the namespace is set, otherwise, return an empty string
     *
     * @param string $ns
     *
     * @return string
     */
    protected function ns ($ns): string
    {
        if (!$ns) {
            return '';
        }

        return strlen($ns) > 0 ? $ns . '\\' : '';
    }

    /**
     * @param string $ns
     *
     * @return string
     */
    protected function dir ($ns): string
    {
        if (!$ns) {
            return '';
        }
        return strlen($ns) > 0 ? $ns . '/' : '';
    }

    /**
     * @return string
     */
    public function getLocation (): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation (string $location): void
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getStubLocation (string $stub): string
    {
        return env("GENERATOR_STUB_DIR") . $stub . '.stub';
    }

    /**
     * @param string $stubLocation
     */
    public function setStubLocation (string $stubLocation): void
    {
        $this->stubLocation = $stubLocation;
    }

    /**
     * @return array
     */
    public function getResource (): array
    {
        return $this->resource;
    }

    /**
     * @param array $resource
     */
    public function setResource (array $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * @return array
     */
    public function getFillables (): array
    {
        return $this->fillables;
    }

    /**
     * @param array $fillables
     */
    public function setFillables (array $fillables): void
    {
        $this->fillables = $fillables;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addFillables ($key, $value): void
    {
        $this->fillables[$key] = $value;
    }

}
