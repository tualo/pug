<?php

namespace Tualo\Office\PUG;

use Pug\Pug as P;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\PUG\Request;



class PUG
{

    public static function getPUGPath(): string
    {
        if (TualoApplication::get("pugCachePath") == '') TualoApplication::set("pugCachePath", TualoApplication::get("tempPath"));
        return (string)TualoApplication::get("pugCachePath");
    }

    public static function exportPUG($db): void
    {
        if (!file_exists(self::getPUGPath())) mkdir(self::getPUGPath(), 0777, true);
        if (!file_exists(self::getPUGPath() . '/checksum'))  file_put_contents(self::getPUGPath() . '/checksum', md5(''));
        $checksum = file_get_contents(self::getPUGPath() . '/checksum');
        $dbchecksum = $db->singleValue('select md5( group_concat( md5(ds_pug_templates.template) separator "")) checksum  from ds_pug_templates', [], 'checksum');
        if ($checksum == $dbchecksum) return;


        TualoApplication::logger('PUG')->info("checksum is diffrent old $checksum new $dbchecksum > export pug files", [$db->dbname]);
        file_put_contents(self::getPUGPath() . '/checksum', $dbchecksum);
        $data = $db->direct('select id,template from ds_pug_templates');
        foreach ($data as $row) {
            file_put_contents(self::getPUGPath() . '/' . $row['id'] . '.pug', $row['template']);
        }
    }

    public static function getPug($options = []): P
    {
        $o = [
            'pretty' => true,
            'debug' => TualoApplication::configuration('pug', 'debug', '0') == 1,
            'cache' => dirname(self::getPUGPath()) . '/cache',
            'basedir' => self::getPUGPath(),
            //'execution_max_time'=>3000000,
            'execution_max_time' => 30000,
            'upToDateCheck' => true,
            'enable_profiler' => false,
            'profiler' => [
                'timeprecision' => 3,
                'lineheight'    => 30,
                'display'        => true,
                'log'            => false,
            ]
        ];
        if (isset($GLOBALS['pug_formats'])) {
            $o['formats'] = $GLOBALS['pug_formats'];
        }
        $o = array_merge($o, $options);
        return new P($o);
    }

    public static function datetime(): callable
    {
        return function (string $dt): \DateTime {
            return (new \DateTime($dt));
        };
    }


    public static function base64file(): callable
    {
        return function (string $tablename, string $value, string $field = '__filename'): string {
            return \Tualo\Office\DS\DSFiles::instance($tablename)->getBase64($field, $value, true);
        };
    }

    public static function dstable(): callable
    {
        return function ($tn): \Tualo\Office\DS\DSTable {
            return \Tualo\Office\DS\DSTable::instance($tn);
        };
    }

    public static function data($data)
    {
        if (isset($_SESSION['pug_session'])) {
            $data = array_merge(
                [
                    'session' => $_SESSION['pug_session']
                ],
                $data
            );
        }
        $http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? "https://" : "http://";
        $url = $http . $_SERVER["SERVER_NAME"] . dirname($_SERVER['SCRIPT_NAME']) . '/';
        $o = [
            'request' => new Request(),
            'datetime' => self::datetime(),
            'base64file' => self::base64file(),
            'dstable' => self::dstable(),
            'baseURL' => $url
        ];
        return array_merge($o, $data);
    }

    public static function render(string $template, array $data = [], array $options = []): string
    {
        $pug = self::getPug($options);

        self::exportPUG(TualoApplication::get('session')->getDB());

        $css = \Tualo\Office\DS\DSTable::instance('ds_renderer_stylesheet_groups_assign');
        $data['stylesheets'] = $css->f('active', '=', 1)->f('pug_id', '=', $template)->read()->get();

        $data['hasTemplate'] = function ($template) use ($pug) {
            return file_exists(self::getPUGPath() . '/' . $template . '.pug');
        };
        $data['includeTemplate'] = function ($template, $data, $parentData = []) use ($pug) {
            $data = array_merge($parentData, $data);
            return $pug->renderFile(self::getPUGPath() . '/' . $template . '.pug', self::data($data));
        };
        $html = $pug->renderFile(self::getPUGPath() . '/' . $template . '.pug',  self::data($data));
        return $html;
    }
}
