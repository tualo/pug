<?php

namespace Tualo\Office\PUG\Routes;

use Tualo\Office\Basic\Route;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\DS\DSTable as T;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\PUG\PUG2 as P2;
use Tualo\Office\PUG\PDF2 as D2;


class Pug2 extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {
        Route::add('/pug2html/(?P<tablename>[\w\-\_]+)/(?P<template>[\w\-\_]+)/(?P<id>.+)', function ($matches) {
            try {

                $pug = new P2(App::get('session')->getDB());
                $data = T::instance($matches['tablename'])
                    ->f('__id', '=', $matches['id'])
                    ->read()
                    ->get();
                $html = $pug->render($matches['template'], $data);
                App::contenttype('text/html');
                App::body($html);
            } catch (\Exception $e) {
                echo $e->getTraceAsString();
                exit();
            }

            Route::$finished = true;
        }, array('get', 'post'), true);

        Route::add('/pug2pdf/(?P<tablename>[\w\-\_]+)/(?P<template>[\w\-\_]+)/(?P<id>.+)', function ($matches) {
            App::contenttype('application/pdf');
            $pdf = D2::render($matches['tablename'], $matches['template'], $matches['id']);
            App::body($pdf);
            Route::$finished = true;
        }, array('get', 'post'), true);
    }
}
