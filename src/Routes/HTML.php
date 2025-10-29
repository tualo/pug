<?php

namespace Tualo\Office\PUG\Routes;

use Exception;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\Basic\Route;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\PUG\PUG as PUG;

class HTML extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {



        Route::add('/pug/local/html/(?P<tablename>[\w\-\_]+)/(?P<template>[\w\-\_]+)/(?P<id>.+)', function ($matches) {

            try {
                TualoApplication::contenttype('text/html');
                $table = \Tualo\Office\DS\DSTable::instance($matches['tablename']);
                $table->f('__id', '=', $matches['id']);
                $data = $table->read()->get();
                $_REQUEST['data'] = $data;


                $payload_data = json_decode(@file_get_contents('php://input'), true);
                if (is_array($payload_data)) {
                    $_REQUEST['data'] = [$payload_data];
                }

                $_REQUEST['id'] = $matches['id'];

                $html = PUG::render($matches['template'], $_REQUEST);
                TualoApplication::body($html);
                Route::$finished = true;
            } catch (Exception $e) {
                TualoApplication::body($e->getMessage());
            }
        }, array('get', 'post'), true);
    }
}
