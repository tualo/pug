<?php

namespace Tualo\Office\PUG;

use Pug\Pug as P;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\PUG\Request;



class PUGOptions
{
    public static function getPUGPath(): string
    {
        if (TualoApplication::get("pugCachePath") == '') TualoApplication::set("pugCachePath", TualoApplication::get("tempPath"));
        return (string)TualoApplication::get("pugCachePath");
    }

    private static function config($key, $default): mixed
    {
        $section = 'pug';

        if (TualoApplication::configuration('pug', 'debug_cidrs', false)) {
            $keys =  json_decode(TualoApplication::configuration('pug', 'debug_clientip_headers', "['HTTP_X_DDOSPROXY', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR']"), true);
            if (is_null($keys)) {
                $keys = ['HTTP_X_DDOSPROXY', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
            }
            if (CIDR::IPisWithinCIDR(
                CIDR::getIP($keys),
                explode(' ', TualoApplication::configuration('pug', 'debug_cidrs'))
            )) {
                TualoApplication::logger('PUG')->info("PUG Debug Mode enabled for " . CIDR::getIP($keys));
                $section = 'pug_debug';
            } else {
                TualoApplication::logger('PUG')->info("PUG Debug Mode disabled for " . CIDR::getIP($keys));
            }
        }


        return TualoApplication::configuration($section, $key, $default);
    }

    public static function getOptions(): array
    {
        $o = [
            'pretty' => self::config('pretty', '1') == '1',
            'debug' => self::config('debug', '0') == '1',
            'cache' => self::config('cache', dirname(self::getPUGPath()) . '/cache'),
            'basedir' => self::config('basedir', self::getPUGPath()),
            'execution_max_time' => intval(self::config('basedir', '30000')),
            'upToDateCheck' => self::config('upToDateCheck', '1') == '1',
            'enable_profiler' => self::config('enable_profiler', '0') == '1',
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
        return $o;
    }
}
