<?php
namespace Tualo\Office\PUG\Checks;

use Tualo\Office\Basic\Middleware\Session;
use Tualo\Office\Basic\PostCheck;
use Tualo\Office\Basic\TualoApplication as App;


class Paths extends PostCheck {
    
    public static function test(array $config){
        $db = App::get('clientDB');
        if (is_null($db)) return;

        if (!file_exists(App::get("basePath").'/cache/'.$db->dbname)){
            self::formatPrintLn(['yellow'],"\t".'module pug create  /cache/'.$db->dbname);
            mkdir(App::get("basePath").'/cache/'.$db->dbname,0777,true);
        }
        if (!file_exists(App::get("basePath").'/cache/'.$db->dbname.'/ds')){
            self::formatPrintLn(['yellow'],"\t".'module pug create  /cache/'.$db->dbname.'/ds');
            mkdir(App::get("basePath").'/cache/'.$db->dbname.'/ds',0777,true);
        }
        if (!file_exists(App::get("basePath").'/cache/'.$db->dbname.'/cache')){
            self::formatPrintLn(['yellow'],"\t".'module pug create  /cache/'.$db->dbname.'/cache');
            mkdir(App::get("basePath").'/cache/'.$db->dbname.'/cache',0777,true);
        }
        if (!file_exists(App::get("basePath").'/cache/'.$db->dbname.'/readcache')){
            self::formatPrintLn(['yellow'],"\t".'module pug create  /cache/'.$db->dbname.'/readcache');
            mkdir(App::get("basePath").'/cache/'.$db->dbname.'/readcache',0777,true);
        }

        if (!file_exists(App::get("basePath").'/cache/'.$db->dbname.'/cache')){
            self::formatPrintLn(['yellow'],"\t".'module pug create  /cache/'.$db->dbname.'/cache');
            mkdir(App::get("basePath").'/cache/'.$db->dbname.'/cache',0777,true);
        }
        if (!file_exists(App::get("basePath").'/cache/'.$db->dbname.'/phpcache')){
            self::formatPrintLn(['yellow'],"\t".'module pug create  /cache/'.$db->dbname.'/phpcache');
            mkdir(App::get("basePath").'/cache/'.$db->dbname.'/phpcache',0777,true);
        }
        self::formatPrintLn(['green'],"\t".'module pug paths for '.$db->dbname.' done');
    }
}
