<?php

namespace Tualo\Office\PUG\CMSMiddleware;

use Tualo\Office\PUG\PUG2;

use Tualo\Office\Basic\TualoApplication as App;


class PUG
{

    public static function pug(): callable
    {
        return function ($options): PUG2 {
            return new PUG2(
                App::get('session')->getDB(),
                $options
            );
        };
    }

    public static function run(&$request, &$result)
    {
        $result['pug'] = self::pug();
    }
}
