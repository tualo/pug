<?php

namespace Tualo\Office\PUG\Routes;

use Exception;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\Basic\Route;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\PUG\DomPDFRenderingHelper;
use Tualo\Office\PUG\PUGRenderingHelper;
use Tualo\Office\PUG\PDF as P;
use Tualo\Office\PUG\PUG as PUG;

class PDF extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {


        Route::add('/pugreportpdf/(?P<tablename>[\w\-\_]+)/(?P<template>[\w\-\_]+)/(?P<id>.+)', function ($matches) {
            P::get($matches['tablename'], $matches['template'], $matches['id']);
        }, array('get', 'post'), true);

        Route::add('/pugreporthtml/(?P<tablename>[\w\-\_]+)/(?P<template>[\w\-\_]+)/(?P<id>.+)', function ($matches) {

            try {

                TualoApplication::contenttype('text/html');
                $table = \Tualo\Office\DS\DSTable::instance($matches['tablename']);
                $table->f('__id', '=', $matches['id']);
                $data = $table->read()->get();
                $_REQUEST['data'] = $data;
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
