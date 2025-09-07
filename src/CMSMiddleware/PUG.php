<?php

namespace Tualo\Office\PUG\CMSMiddleware;

use Tualo\Office\PUG\PUG2;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\PUG\Barcode;

class PUG
{

    public static function pug(): callable
    {
        return function (array $options = []): PUG2 {
            return new PUG2(
                App::get('session')->getDB(),
                $options
            );
        };
    }


    public static function barcode(): callable
    {
        return function ($type, $data): PUG2 {
            return Barcode::get($type, $data);
        };
    }

    public static function run(&$request, &$result)
    {
        $result['pug'] = self::pug();
        $result['barcode'] = self::barcode();
    }
}
