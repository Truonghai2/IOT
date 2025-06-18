<?php

namespace App\Http\Requests;

class Request
{
    protected $data;

    public function __construct()
    {
        $this->data = array_merge($_GET, $_POST);
    }

    public function all()
    {
        return $this->data;
    }

    public function input($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function only($keys)
    {
        return array_intersect_key($this->data, array_flip((array) $keys));
    }

    public function except($keys)
    {
        return array_diff_key($this->data, array_flip((array) $keys));
    }
} 