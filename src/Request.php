<?php
namespace Tualo\Office\PUG;

class Request{
    public function get(string $key):mixed{
        if (!isset($_REQUEST[$key])) return false;
        return $_REQUEST[$key];
    }
}