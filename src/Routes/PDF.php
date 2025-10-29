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

                /*
                ini_set('memory_limit','4096M');
                $db = TualoApplication::get('session')->getDB();
        
                $_REQUEST['tablename']=$matches['tablename'];
        
                set_time_limit(600);
                
                if (!file_exists(TualoApplication::get("basePath").'/cache/'.$db->dbname)){
                    mkdir(TualoApplication::get("basePath").'/cache/'.$db->dbname);
                }
                if (!file_exists(TualoApplication::get("basePath").'/cache/'.$db->dbname.'/ds')){
                    mkdir(TualoApplication::get("basePath").'/cache/'.$db->dbname.'/ds');
                }
                $GLOBALS['pug_cache']=TualoApplication::get("basePath").'/cache/'.$db->dbname.'/ds';
                    
                PUGRenderingHelper::exportPUG($db);
                $template = $matches['template'];
                $id = $matches['id'];
                $html = PUGRenderingHelper::render([$id], $template, $_REQUEST);
                */

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

        /*
        Route::add('/Tualo/cmp/cmp_ds/(?P<file>[\/.\w\d]+)', function ($matches) {
            if (file_exists(dirname(__DIR__) . '/js/' . $matches['file'])) {
                TualoApplication::etagFile((dirname(__DIR__) . '/js/' . $matches['file']));
                TualoApplication::contenttype('application/javascript');
                Route::$finished = true;
            }
        }, array('get'), false);


        
        Route::add('/dslibrary/(?P<file>[\/.\w\d]+).js', function ($matches) {
            / *
            if (file_exists(dirname(__DIR__).'/js/'.$matches['file'])){
                TualoApplication::etagFile((dirname(__DIR__).'/js/'.$matches['file']));
                TualoApplication::contenttype('application/javascript');
                Route::$finished=true;
            }
            * /
            try {

                if (!file_exists(TualoApplication::get('cachePath') . '/jscache')) {
                    mkdir(TualoApplication::get('cachePath') . '/jscache', 0777, true);
                }

                $session = TualoApplication::get('session');
                $db = TualoApplication::get('session')->getDB();
                $data = [];
                if (($matches['file'] == 'all') || ($matches['file'] == 'model')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_model" m from view_ds_model limit 2000 '));
                if (($matches['file'] == 'all') || ($matches['file'] == 'store')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_store" m from view_ds_store limit 2000 '));
                if (($matches['file'] == 'all') || ($matches['file'] == 'column')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_column" m from view_ds_column limit 2000  '));

                if (($matches['file'] == 'all') || ($matches['file'] == 'combobx')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_combobox" m from view_ds_combobox limit 2000 '));
                if (($matches['file'] == 'all') || ($matches['file'] == 'displayfield')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_displayfield" m from view_ds_displayfield  2imit 1000 '));
                if (($matches['file'] == 'all') || ($matches['file'] == 'controller')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_controller" m from view_ds_controller 2imit 1000 '));
                if (($matches['file'] == 'all') || ($matches['file'] == 'list'))  $data = array_merge($data, $db->direct('select js,table_name,"view_ds_list" m from view_ds_list limit 2000 '));
                if (($matches['file'] == 'all') || ($matches['file'] == 'form')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_form" m from view_ds_form limit 2000 '));
                if (($matches['file'] == 'all') || ($matches['file'] == 'dsview')) $data = array_merge($data, $db->direct('select js,table_name,"view_ds_dsview" m from view_ds_dsview limit 2000  '));


                file_put_contents(
                    TualoApplication::get('cachePath') . '/jscache/compile.js',
                    'function dsmicroloader(){' . PHP_EOL . 'Ext.Loader.syncModeEnabled=true;' . PHP_EOL .
                        array_reduce($data, function ($acc, $item) {
                            return $acc . "\n" .
                                "/ * console.debug('" . $item['table_name'] . "','" . $item['m'] . "');* /" .
                                "\n" . $item['js'];
                        }) . 'Ext.Loader.syncModeEnabled=false;}'
                );


                TualoApplication::etagFile(TualoApplication::get('cachePath') . '/jscache/compile.js');
                TualoApplication::contenttype('application/javascript');
                Route::$finished = true;
            } catch (\Exception $e) {
            }
        }, array('get'), true);


        Route::add('/Tualo/DataSets/(?P<type>[\/.\w\d\_]+)/(?P<tablename>[\/.\w\d\_]+)/(?P<name>[\/.\w\d\_]+).js', function ($matches) {
            try {
                $session = TualoApplication::get('session');
                $db = TualoApplication::get('session')->getDB();
                TualoApplication::result('msg', '');
                TualoApplication::result('success', false);
                TualoApplication::contenttype('text/javascript');

                if (file_exists(dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js')) {
                    TualoApplication::etagFile((dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js'));
                    Route::$finished = true;
                    return;
                }

                if (file_exists(dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js')) {
                    TualoApplication::etagFile(dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js');
                }


                if (
                    isset($_SESSION['tualoapplication']['loggedIn'])
                    &&  ($_SESSION['tualoapplication']['loggedIn'] === true)
                    &&  (!is_null($session))
                    &&  (!is_null($db))
                ) {
                    $v = false;


                    if ($matches['type'] == 'column') $v = $db->singleValue('select js from view_ds_column where table_name=lower({tablename}) and name=lower({name}) ', $matches, 'js');
                    if ($matches['type'] == 'combobox') $v = $db->singleValue('select js from view_ds_combobox where table_name=lower({tablename}) and name=lower({name}) ', $matches, 'js');
                    if ($matches['type'] == 'displayfield') $v = $db->singleValue('select js from view_ds_displayfield where table_name=lower({tablename}) and name=lower({name}) ', $matches, 'js');

                    if ($v !== false) {
                        TualoApplication::body($v);
                    }
                }

                Route::$finished = true;
            } catch (\Exception $e) {
                //                echo $e->getMessage();
            }
        }, array('get'), false);


        Route::add('/Tualo/DataSets/(?P<type>[\/.\w\d\_]+)/(?P<tablename>[\/.\w\d\_]+).js', function ($matches) {
            try {
                $session = TualoApplication::get('session');
                $db = TualoApplication::get('session')->getDB();
                TualoApplication::result('msg', '');
                TualoApplication::result('success', false);
                TualoApplication::contenttype('text/javascript');


                if (file_exists(dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js')) {
                    TualoApplication::etagFile((dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js'));
                    return;
                }

                if (file_exists(dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js')) {
                    TualoApplication::etagFile(dirname(__DIR__) . '/js/' . $matches['type'] . '/' . $matches['tablename'] . '.js');
                }


                if (
                    isset($_SESSION['tualoapplication']['loggedIn'])
                    &&  ($_SESSION['tualoapplication']['loggedIn'] === true)
                    &&  (!is_null($session))
                    &&  (!is_null($db))
                ) {
                    $v = false;

                    if ($matches['type'] == 'store') $v = $db->singleValue('select js from view_ds_store where table_name=lower({tablename}) ', $matches, 'js');
                    if ($matches['type'] == 'model') $v = $db->singleValue('select js from view_ds_model where table_name=lower({tablename}) ', $matches, 'js');

                    if ($matches['type'] == 'list') {
                        $v = $db->singleValue('select js from view_ds_list where table_name=lower({tablename}) ', $matches, 'js');
                    }
                    if ($matches['type'] == 'form') $v = $db->singleValue('select js from view_ds_form where table_name=lower({tablename}) ', $matches, 'js');
                    if ($matches['type'] == 'dsview') $v = $db->singleValue('select js from view_ds_dsview where table_name=lower({tablename}) ', $matches, 'js');
                    if ($matches['type'] == 'controller') $v = $db->singleValue('select js from view_ds_controller where table_name=lower({tablename}) ', $matches, 'js');

                    / *
                    if ($matches['type']=='views') $v = $db->singleValue('select getViewport({tablename}) v',$matches,'v');
                    if ($matches['type']=='list') $v = $db->singleValue('select getListViewport({tablename}) v',$matches,'v');
                    if ($matches['type']=='stores') $v = $db->singleValue('select getStoreDefinition({tablename}) v',$matches,'v');
                    if ($matches['type']=='models') $v = $db->singleValue('select getViewportModel({tablename}) v',$matches,'v');
                    // if ($matches['type']=='model') $v = $db->singleValue('select getModelDefinition({tablename}) v',$matches,'v');
                    * /
                    if ($v !== false) {
                        TualoApplication::body($v);
                    }
                }
                Route::$finished = true;
            } catch (\Exception $e) {
            }
        }, array('get'), false);

        
        Route::add('/Tualo/DataSets/Viewport',function($matches){
            $matches=['file'=>'Viewport'];
            if (file_exists(dirname(__DIR__).'/js/'.$matches['file'])){  TualoApplication::etagFile((dirname(__DIR__).'/js/'.$matches['file'])); }
            TualoApplication::contenttype('application/javascript');
            Route::$finished=true;
        
        });
        
        
        Route::add('/Tualo/DataSets/store/Basic',function($matches){
            $matches=['file'=>'Viewport'];
            if (file_exists(dirname(__DIR__).'/js/'.$matches['file'])){  TualoApplication::etagFile((dirname(__DIR__).'/js/'.$matches['file'])); }
            TualoApplication::contenttype('application/javascript');
            Route::$finished=true;
        
        });
        */
    }
}
