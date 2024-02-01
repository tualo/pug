<?php 
namespace Tualo\Office\PUG;
use Pug\Pug as P;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\PUG\Request;


// \Tualo\Office\PUG\PDF::get('event_tickes','ticket',1);
class PDF {
    public static function get($tablename,$template,$id){
        ini_set('memory_limit','4096M');
        $db = TualoApplication::get('session')->getDB();
        TualoApplication::contenttype('application/pdf');

        $_REQUEST['tablename']=$tablename;
        $matches=[
            'tablename'=>$tablename,
            'template'=>$template,
            'id'=>$id
        ];

        set_time_limit(600);
        
        if (!file_exists(TualoApplication::get("basePath").'/cache/'.$db->dbname)){
            mkdir(TualoApplication::get("basePath").'/cache/'.$db->dbname);
        }
        if (!file_exists(TualoApplication::get("basePath").'/cache/'.$db->dbname.'/ds')){
            mkdir(TualoApplication::get("basePath").'/cache/'.$db->dbname.'/ds');
        }
        $GLOBALS['pug_cache']=TualoApplication::get("basePath").'/cache/'.$db->dbname.'/ds';
            
        DomPDFRenderingHelper::render($matches,$_REQUEST);
    }
}