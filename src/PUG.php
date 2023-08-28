<?php 
namespace Tualo\Office\PUG;
use Pug\Pug as P;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\PUG\Request;



class PUG {

    public static function getPUGPath(): string{
        if (TualoApplication::get("pugCachePath")=='') TualoApplication::set("pugCachePath",TualoApplication::get("tempPath"));
        return (string)TualoApplication::get("pugCachePath");
    }

    public static function exportPUG($db): void{
        if(!file_exists( self::getPUGPath() )) mkdir( self::getPUGPath() ,0777);
        if(!file_exists( self::getPUGPath().'/checksum' ))  file_put_contents( self::getPUGPath().'/checksum',md5('') );
        $checksum = file_get_contents( self::getPUGPath().'/checksum');
        $dbchecksum = $db->singleValue('checksum table ds_pug_templates',[],'checksum');
        if ($checksum==$dbchecksum) return; 


        TualoApplication::logger('PUG')->info("checksum is diffrent old $checksum new $dbchecksum > export pug files",[$db->dbname]);
        file_put_contents( self::getPUGPath().'/checksum',$dbchecksum );
        $data = $db->direct('select id,template from ds_pug_templates');
        foreach($data as $row){
            file_put_contents( self::getPUGPath().'/'.$row['id'].'.pug',$row['template'] );
        }
    }

    public static function getPug($options=[]): P{
        $o = [        
                'pretty' => true,
                'debug' => true,
                'cache' => dirname(self::getPUGPath()).'/cache',
                'basedir' => self::getPUGPath(),
                //'execution_max_time'=>3000000,
                'execution_max_time'=>3000,
                'upToDateCheck' => true,
                'enable_profiler' => false,
                'profiler' => [
                    'timeprecision' => 3,
                    'lineheight'    => 30,
                    'display'        => true,
                    'log'            => false,
                ]
        ];
        if (isset($GLOBALS['pug_formats'])){
            $o['formats']=$GLOBALS['pug_formats'];
        }
        $o = array_merge($o,$options);
        return new P($o);
    }

    public static function datetime():mixed{
        return function(string $dt):mixed{
            return (new \DateTime($dt));
        };
    }

    public static function data($data){
        if (isset($_SESSION['pug_session'])){
            $data=array_merge(
            [
                'session'=>$_SESSION['pug_session']
            ],
            $data
            );
        }

        $o = [
            'request' => new Request(),
            'datetime' => self::datetime()
        ];
        return array_merge($o,$data);
    }

    public static function render(string $template,array $data=[],array $options=[]):string{
        $pug = self::getPug($options);
        $data['hasTemplate'] = function($template) use ($pug){
                return file_exists( self::getPUGPath().'/'.$template.'.pug');
        };
        $data['includeTemplate'] = function($template,$data,$parentData=[]) use ($pug){
                // $data['parent'] = $parentData;
                $data = array_merge($parentData,$data);
                return $pug->renderFile( self::getPUGPath().'/'.$template.'.pug', self::data($data));
        };
        $html = $pug->renderFile( self::getPUGPath().'/'.$template.'.pug',  self::data($data));
        return $html;
    }
}