<?php

namespace Tualo\Office\PUG;

use Tualo\Office\Basic\TualoApplication;
use Pug\Pug as P;
use Tualo\Office\DS\DSTable as T;

class PUG2
{
    private mixed $db = "";
    private mixed $options = [];

    function __construct(mixed $db, array $options = [])
    {
        $this->db = $db;
        $this->options = $options;
    }


    private function getPUGPath(): string
    {
        if (TualoApplication::get("pugCachePath") == '') TualoApplication::set("pugCachePath", TualoApplication::get("tempPath"));
        $path = (string)TualoApplication::get("pugCachePath");
        if (!file_exists($path)) mkdir($path, 0777, true);
        if (!file_exists($path . '/checksum'))  file_put_contents($path . '/checksum', md5(''));

        return (string)TualoApplication::get("pugCachePath");
    }

    private function exportPUG(): void
    {
        $db = $this->db;
        $path = $this->getPUGPath();
        $checksum = file_get_contents($path . '/checksum');
        $dbchecksum = $db->singleValue('select md5( group_concat( md5(ds_pug_templates.template) separator "")) checksum  from ds_pug_templates', [], 'checksum');
        if ($checksum == $dbchecksum) return;

        TualoApplication::logger('PUG2')->info("checksum is diffrent old $checksum new $dbchecksum > export pug files", [$db->dbname]);
        file_put_contents($path . '/checksum', $dbchecksum);

        $data = $db->direct('select id,template from ds_pug_templates');
        foreach ($data as $row) {
            file_put_contents($path . '/' . $row['id'] . '.pug', $row['template']);
        }
    }




    private function getPug($options = []): P
    {
        $path = $this->getPUGPath();
        $o = PUGOptions::getOptions();
        $o = array_merge($o, $this->options);
        $o = array_merge($o, $options);


        return new P($o);
    }

    /**
     * Render a pug template into a html string
     * the given data will be passed to the template
     * 
     * @param string $template
     * @param array $data
     * @param array $options
     * @return string
     */
    public function render(
        string $template,
        array $data,

        array $options = []
    ): string {
        $this->exportPUG();
        $pug = $this->getPug($options);

        $stylesheets = T::instance('ds_renderer_stylesheet_groups_assign')
            ->f('active', '=', 1)
            ->f('pug_id', '=', $template)
            ->read()
            ->get();

        if (is_null($stylesheets))
            $stylesheets = [];
        TualoApplication::set('inside_pug', true);

        return $pug->renderFile($template, [
            'data' => $data,
            'stylesheets' => $stylesheets,
            'datetime' => self::datetime(),
            'base64file' => self::base64file(),
            'dsfiles' => self::dsfiles(),
            'request' => new Request(),
            'relocate' => new Relocate(),
            'keysort' => self::keysort(),
            'dstable' =>  self::dstable(),
            'barcode' =>  self::barcode(),
            'pug' =>  self::pugFN(),
        ]);
    }

    public static function barcode(): callable
    {
        return function ($type, $data): string {
            return Barcode::get($type, $data);
        };
    }

    public static function dsfiles(): callable
    {
        return function (string $tablename): \Tualo\Office\DS\DSFiles {
            return \Tualo\Office\DS\DSFiles::instance($tablename);
        };
    }

    public static function base64file(): callable
    {
        return function (string $tablename, string $value, string $field = '__filename'): string {
            return \Tualo\Office\DS\DSFiles::instance($tablename)->getBase64($field, $value, true);
        };
    }

    public static function pugFN(): callable
    {
        return function ($options = []): PUG2 {
            return new PUG2($this->db, $options);
        };
    }

    public static function dstable(): callable
    {
        return function ($tn): \Tualo\Office\DS\DSTable {
            return \Tualo\Office\DS\DSTable::instance($tn);
        };
    }




    public static function datetime(): callable
    {
        return function (string $dt): \DateTime {
            return (new \DateTime($dt));
        };
    }

    public static function keysort(): callable
    {
        return function (array $data, string $key, string $direction = 'asc'): array {
            usort($data, function ($a, $b) use ($key, $direction) {
                if ($direction == 'asc') {
                    return $a[$key] <=> $b[$key];
                } else {
                    return $b[$key] <=> $a[$key];
                }
            });
            return $data;
        };
    }
}
