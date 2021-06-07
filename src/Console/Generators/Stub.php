<?php

namespace Simianbv\Generators\Console\Generators;


use Illuminate\Support\Str;

/**
 * Class Stub
 * @package Simianbv\Generators\Console\Generators
 */
class Stub
{
    /**
     * @var string
     */
    protected $name = null;
    /**
     * @var string
     */
    protected $template = null;
    /**
     * @var array
     */
    protected $fillables = [];
    /**
     * @var string[]
     */
    protected $delimiters = ["{{", "}}"];

    public function __construct (string $name = null, string $path = null, array $delimiters = [])
    {
        if ($name) {
            $this->loadTemplate($name);
        }

        if ($delimiters) {
            $this->delimiters($delimiters);
        }
    }

    /**
     * @param $name
     */
    public function loadTemplate ($name)
    {
        $this->template = file_get_contents($this->getTemplate($name));
    }

    /**
     * @param $stub
     * @return string
     */
    protected function getTemplate ($stub)
    {
        $u = config("generators.location.stub");
        if (!Str::endsWith($u, '/')) {
            $u .= '/';
        }
        return $u . $stub . '.stub';
    }

    /**
     * @param array $fields
     * @param array $delimiters
     * @return string
     */
    public function fill (array $fields, array $delimiters = [])
    {
        if (empty($delimiters)) {
            $delimiters = $this->delimiters;
        }

        $keys = array_map(function ($key) use ($delimiters) {
            return $delimiters[0] . $key . $delimiters[1];
        }, array_keys($fields));

        return str_replace($keys, array_values($fields), $this->template);
    }

}
