<?php 
namespace Tualo\Office\PUG;
use Pug\Pug;
use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\DS\DSReadRoute;
use Tualo\Office\DS\DSRenderer;
use Tualo\Office\DS\DS;
use Tualo\Office\DS\DSFileHelper;


class PUGRenderingHelper{

    public static $maxDeep=10;

    public static function getPUGPath():string{
        if (TualoApplication::get("pugCachePath")==''){
            TualoApplication::set("pugCachePath",TualoApplication::get("tempPath"));
        }
        return (string)TualoApplication::get("pugCachePath");
    }

    public static function file_put_contents($fn,$data){
        $write=false;
        if (file_exists($fn)){
            if (file_get_contents($fn)!=$data){
                $write=true;
            }

        }else{
            $write=true;
        }
        if ($write==true){
            file_put_contents($fn,$data);
            
        }
        return $write;
    }

    public static function exportPUG($db){
        
        if(!file_exists( self::getPUGPath() )) mkdir( self::getPUGPath() ,0777);
        $data = $db->direct('select id,template from ds_pug_templates');
        $list=[];
        foreach($data as $row){
            if (self::file_put_contents( self::getPUGPath().'/'.$row['id'].'.pug',$row['template'] )) $list[] = self::getPUGPath().'/___init.pug';
        }
        
        if (self::file_put_contents( 
            self::getPUGPath().'/___init.pug',
            "ds(tablename=tablename,template=template)\n    dsfilter(property=\"__id\",operator=\"eq\",value=idList)"
        )) $list[] = self::getPUGPath().'/___init.pug';

        // self::cachePUGFiles();
    }

    public static function getPug(){
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

        return new Pug($o);
    }

    public static function cachePUGFiles(){
        $pug = self::getPug();
        list($success, $errors) = $pug->cacheDirectory(self::getPUGPath());
    }


    
    public static function getIDArray($matches,$request){

        $idlist = [];
        if(isset($matches['id'])){
            $id = $matches['id'];
            $idlist = [$id];
        }else{
            if(isset($request['id'])){
                if (is_array($request['id'])){
                    $idlist = $request['id'];
                }else{
                    $idlist = json_decode($request['id'],true);
                    if (is_null($idlist )){
                        $idlist = [ $request['id'] ];
                    }
                }
            }
        }

        
        return $idlist;
    }

    public static function domReplaceDS(&$doc,$idList,$template,$request){
        $items = $doc->getElementsByTagName('ds');
        $fnrequest = $request;



        $nodeListLength = $items->length; 
        for ($i =  $nodeListLength-1; $i >= 0; $i--) {

            $node = $items->item($i);
            $parent = $node->parentNode;
            $tablename = $node->getAttribute('tablename');

            $localtemplate = $node->getAttribute('template');
            TualoApplication::timing("render_ds_".$localtemplate .' '.$tablename );
            
            if ($tablename){
                $request = array(
                    'start' => 0,
                    'limit' => 1000000,
                    'filter' => array(),
                    'sort' => array()
                );
                $filterNodes = $node->getElementsByTagName('dsfilter');
                if(count($filterNodes) > 0) {
                    foreach ($filterNodes as $filterNode){
                        if ($filterNode->getAttribute('property')=='__id'){
                            $request['filter'][] = array(
                                'property'=> $filterNode->getAttribute('property'),
                                'value'=>$filterNode->getAttribute('value'),
                                'operator'=>$filterNode->getAttribute('operator')
                            );
                        }else{
                            $request['filter'][] = array(
                                'property'=> $tablename.'__'.$filterNode->getAttribute('property'),
                                'value'=>$filterNode->getAttribute('value'),
                                'operator'=>$filterNode->getAttribute('operator')
                            );
                        }
                    }
                }
                $filterNodes = $node->getElementsByTagName('dssort');
                if(count($filterNodes) > 0) {
                    foreach ($filterNodes as $filterNode){
                        $request['sort'][] = array(
                            'property'=> $tablename.'__'.$filterNode->getAttribute('property'),
                            'direction'=>$filterNode->getAttribute('direction')
                        );
                    }
                }

                $request['shortfieldnames']=1;
                $request['sqlcache']=true;
                
                $db = TualoApplication::get('session')->getDB();


            self::$maxDeep--;
            if (self::$maxDeep==0) throw new \Exception("max deep reached $localtemplate $tablename");

            $read = DSReadRoute::read($db,$tablename,$request);

            TualoApplication::timing("render_ds_".$localtemplate .' '.$tablename, [] );

                $read['definition'] = $db->direct('select column_name,label from ds_column_list_label where active=1 and hidden=0 and table_name={table_name} order by position',array('table_name'=>$tablename));
                

                $subhtml = PUGRenderingHelper::_render($idList,$localtemplate,$read);
                $subdoc = new \DOMDocument();
                if (!empty(trim(chop($subhtml)))){
                    $subdoc->loadHTML('<?xml encoding="utf-8" ?>'.$subhtml,LIBXML_NOWARNING);
                    $subitems = null;
                    $body = $subdoc->getElementsByTagName('body');
                    if ($body->length!=0) {
                        $subitems = $body[0]->childNodes;
                    }
                    if ($subitems!==null){
                        for ($it = $subitems->length; --$it >= 0; ) {
                            $subnode = $subitems->item($it);
                            if ($importedNode = $doc->importNode($subnode->cloneNode(true),true)) {
                                $parent->insertBefore($importedNode,$node->nextSibling);
    //                                    $parent->appendChild($importedNode);
                            }
                        }
    
                    }
                    
                }

            }
            $parent->removeChild($node);
        }
        return $doc;
    }


    public static function domReplaceBarcodeImage(&$doc,$idList,$template,$request){
        $db = TualoApplication::get('session')->getDB();
        $items = $doc->getElementsByTagName('barcodeimage');
        $nodeListLength = $items->length; 

        for ($i =  $nodeListLength-1; $i >= 0; $i--) {
            $node = $items->item($i);
            $parent = $node->parentNode;
            $id = NULL;
            
            $xattr="";
            
            if ($node->hasAttributes()) {
                if ($node->hasAttribute('type')){
                    $type = $node->getAttribute('type');
                    if ($node->hasAttribute('data')){
                        $data = $node->getAttribute('data');
                        $xattr = "";
                        foreach ($node->attributes as $attr) {
                            $name = $attr->nodeName;
                            if (!in_array($name,array('data','type' ))){
                                $value = $attr->nodeValue;
                                $xattr.=' '.$name.'="'.$value.'"';
                            }
                        }

                        if ($type=='qr'){

                            // composer require chillerlan/php-qrcode
                            if (class_exists("chillerlan\QRCode\QRCode")){
                                $subhtml = '<img src="'.(new chillerlan\QRCode\QRCode)->render($data).'" '.$xattr.' />';
                            }else{
                                $subhtml = '<span>QRCode lib not installed</span>';
                            }
                            $subdoc = new \DOMDocument();
                            if (!empty(trim(chop($subhtml)))){
                                $subdoc->loadHTML('<?xml encoding="utf-8" ?>'.$subhtml,LIBXML_NOWARNING);
                                $subitems = $subdoc->getElementsByTagName('body')[0]->childNodes;
                                for ($it = $subitems->length; --$it >= 0; ) {
                                    $subnode = $subitems->item($it);
                                    if ($importedNode = $doc->importNode($subnode->cloneNode(true),true)) {
                                        $parent->insertBefore($importedNode,$node->nextSibling);
    //                                    $parent->appendChild($importedNode);
                                    }
                                }
                            }
                        }else if(in_array($type,array(
                            'c39',
                            'c39-c',
                            'c39-e',
                            'c39-ec',
                            'c93',
                            's25',
                            's25-c',
                            'i25',
                            'i25-c',
                            'c128',
                            'c128-a',
                            'c128-b',
                            'c128-c',
                            'ean2',
                            'ean5',
                            'ean8',
                            'ean13',
                            'upc-a',
                            'upc-b',
                            'msi',
                            'msi-c',
                            'postnet',
                            'planet',
                            'rms4-cc',
                            'kix',
                            'imb',
                            'codabar',
                            'c11',
                            'pharma',
                            'pharma-2'
                        ))){
                            if (class_exists("Picqer\Barcode\BarcodeGeneratorPNG")){

                                $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                                $types = array();
                                $types['c39'] = $generator::TYPE_CODE_39;
                                $types['c39-c'] = $generator::TYPE_CODE_39_CHECKSUM;
                                $types['c39-e'] = $generator::TYPE_CODE_39E;
                                $types['c39-ec'] = $generator::TYPE_CODE_39E_CHECKSUM;
                                $types['c93'] = $generator::TYPE_CODE_93;
                                $types['s25'] = $generator::TYPE_STANDARD_2_5;
                                $types['s25-c'] = $generator::TYPE_STANDARD_2_5_CHECKSUM;
                                $types['i25'] = $generator::TYPE_INTERLEAVED_2_5;
                                $types['i25-c'] = $generator::TYPE_INTERLEAVED_2_5_CHECKSUM;
                                $types['c128'] = $generator::TYPE_CODE_128;
                                $types['c128-a'] = $generator::TYPE_CODE_128_A;
                                $types['c128-b'] = $generator::TYPE_CODE_128_B;
                                $types['c128-c'] = $generator::TYPE_CODE_128_C;
                                $types['ean2'] = $generator::TYPE_EAN_2;
                                $types['ean5'] = $generator::TYPE_EAN_5;
                                $types['ean8'] = $generator::TYPE_EAN_8;
                                $types['ean13'] = $generator::TYPE_EAN_13;
                                $types['upc-a'] = $generator::TYPE_UPC_A;
                                $types['upc-b'] = $generator::TYPE_UPC_E;
                                $types['msi'] = $generator::TYPE_MSI;
                                $types['msi-c'] = $generator::TYPE_MSI_CHECKSUM;
                                $types['postnet'] = $generator::TYPE_POSTNET;
                                $types['planet'] = $generator::TYPE_PLANET;
                                $types['rms4-cc'] = $generator::TYPE_RMS4CC;
                                $types['kix'] = $generator::TYPE_KIX;
                                $types['imb'] = $generator::TYPE_IMB;
                                $types['codabar'] = $generator::TYPE_CODABAR;
                                $types['c11'] = $generator::TYPE_CODE_11;
                                $types['pharma'] = $generator::TYPE_PHARMA_CODE;
                                $types['pharma-2'] = $generator::TYPE_PHARMA_CODE_TWO_TRACKS;
                                $subhtml = '<img src="data:image/png;base64,' . base64_encode($generator->getBarcode($data, $types[$type])) . '" '.$xattr.'/>';
                                // composer require picqer/php-barcode-generator
                            }else{
                                $subhtml = '<span>BarcodeGenerator lib not installed</span>';
                            }
                            $subdoc = new \DOMDocument();
                            if (!empty(trim(chop($subhtml)))){
                                $subdoc->loadHTML('<?xml encoding="utf-8" ?>'.$subhtml,LIBXML_NOWARNING);
                                $subitems = $subdoc->getElementsByTagName('body')[0]->childNodes;
                                foreach($subitems as $subnode) {
                                    if ($importedNode = $doc->importNode($subnode->cloneNode(true),true)) {
    //                                            $parent->insertBefore($importedNode,$node->nextSibling);
                                        $parent->appendChild($importedNode);

                                    }
                                }
                            }
                        }

                    }
                }

                
            }
        }
    }

    public static function domReplaceDSSignumImage(&$doc,$idList,$template,$request){
        $db = TualoApplication::get('session')->getDB();

        $items = $doc->getElementsByTagName('signumimage');
        $nodeListLength = $items->length; 
        for ($i =  $nodeListLength-1; $i >= 0; $i--) {
            $node = $items->item($i);
            $parent = $node->parentNode;
            $tableextension = NULL;
            $reportnumber = NULL;
            $thickness = 1;
            $xattr = '';
            
            if ($node->hasAttributes()) {
                if ($node->hasAttribute('tableextension'))
                $tableextension= $node->getAttribute('tableextension');
                if ($node->hasAttribute('reportnumber'))
                $reportnumber = $node->getAttribute('reportnumber');
                if ($node->hasAttribute('thickness'))
                $thickness = $node->getAttribute('thickness');
                foreach ($node->attributes as $attr) {
                    $name = $attr->nodeName;
                    if (!in_array($name,array('tableextension','reportnumber','thickness'))){
                        $value = $attr->nodeValue;
                        $xattr.=' '.$name.'="'.$value.'"';
                    }
                }
            }

            if ($reportnumber)
            if ($tableextension){
                $list = $db->direct('select x,y from blg_signum_'.$tableextension.' where id={id} order by pos',array('id'=>$reportnumber));
                if(($list)&&(count($list)>0)){
                    $width=0;$height=0;
                    foreach($list as $item) { $width=max($width,$item['x']);$height=max($height,$item['y']); }

                    if ( ($width>0) && ($height>0) ){
                        $im_dest = imagecreatetruecolor ($width, $height);
                        imagealphablending($im_dest, false);

                        $white = imagecolorallocate($im_dest, 255, 255, 255);
                        $black = imagecolorallocate($im_dest, 0, 0, 0);
                        imagefilledrectangle($im_dest,0,0,$width,$height,$white);

                        imagesetthickness($im_dest,$thickness);

                        $start_line = true;
                        //$max_scale=0.3;
                        $last_point = array('x'=>-1,'y'=>-1);
                        foreach($list as $pos){
                            if ($pos['x']!=-1){
                                if ($last_point['x']!=-1){
                                    imageline($im_dest,$last_point['x'],$last_point['y'],$pos['x'],$pos['y'],$black);
                                }
                            }
                            $last_point=$pos;
                        }

                        $destfile = TualoApplication::get('tempPath').'/'.uniqid();

                        imagepng($im_dest, $destfile);
                        imagedestroy($im_dest);
                        $subhtml = '<img src="data:image/png;base64,'.base64_encode( file_get_contents($destfile) ).'" '.$xattr.' />';
                        $subdoc = new \DOMDocument();
                        if (!empty(trim(chop($subhtml)))){
                            $subdoc->loadHTML('<?xml encoding="utf-8" ?>'.$subhtml,LIBXML_NOWARNING);
                            $subitems = $subdoc->getElementsByTagName('body')[0]->childNodes;
                            for ($it = $subitems->length; --$it >= 0; ) {
                                $subnode = $subitems->item($it);
                                if ($importedNode = $doc->importNode($subnode->cloneNode(true),true)) {
                                    $parent->insertBefore($importedNode,$node->nextSibling);
                                }
                            }


                        }
                        unlink($destfile);
                    }
                }
            }
            $parent->removeChild($node);

        }
    }

    public static function domReplaceDSImage(&$doc,$idList,$template,$request){
        $db = TualoApplication::get('session')->getDB();

        $items = $doc->getElementsByTagName('dsimage');
        $nodeListLength = $items->length; 
        for ($i =  $nodeListLength-1; $i >= 0; $i--) {
            $node = $items->item($i);
            $parent = $node->parentNode;
            $id = NULL;
            $tablename = NULL;
            $queryfield = NULL;
            $queryvalue = NULL;
            $filecolumn = NULL;
            $xattr="";
            
            // dsimage(tablename="dateien",queryfield="name",queryvalue="",filecolumn="datei")
            if ($node->hasAttributes()) {
                if ($node->hasAttribute('tablename'))
                $tablename = $node->getAttribute('tablename');
                if ($node->hasAttribute('queryfield'))
                $queryfield = $node->getAttribute('queryfield');
                if ($node->hasAttribute('queryvalue'))
                $queryvalue = $node->getAttribute('queryvalue');
                if ($node->hasAttribute('filecolumn'))
                $filecolumn = $node->getAttribute('filecolumn');
            
                foreach ($node->attributes as $attr) {
                    $name = $attr->nodeName;
                    if (!in_array($name,array('tablename','queryfield','queryvalue','filecolumn'))){
                        $value = $attr->nodeValue;
                        $xattr.=' '.$name.'="'.$value.'"';
                    }
                }
            }

            if ($filecolumn)
            if ($queryfield)
            if ($queryvalue)
            if ($tablename){
                $res = DSReadRoute::read($db,$tablename,array(
                    'sqlcache'=>true,
                    'filter'=>array(
                        array('operator'=>'=','property'=>$tablename.'__'.$queryfield ,'value'=>$queryvalue)
                    )
                ));
                if(isset($res['data']) && isset($res['data'][0]) && isset($res['data'][0][$tablename.'__'.$filecolumn])) $id = $res['data'][0][$tablename.'__'.$filecolumn];
                if(false)
                if ($id){
                    $mime = DSFileHelper::getFileMimeType($db,$tablename,$id);
                    if (isset($mime['mime'])){
                        $image = DSFileHelper::getFile($db,$tablename,$id,$direct=true,$base64=true);
                        if (isset($image['success']) &&  ($image['success']==true) ){
                            $subhtml = '<img src="data:'.$mime['mime'].';base64,'.$image['data'].'" '.$xattr.' />';
                            /*if($mime['mime']=='image/svg+xml'){
                                $subhtml = str_replace('<svg ','<svg '.$xattr.'',base64_decode($image['data']));
                            }*/
                            $subdoc = new \DOMDocument();
                            if (!empty(trim(chop($subhtml)))){
                                $subdoc->loadHTML('<?xml encoding="utf-8" ?>'.$subhtml,LIBXML_NOWARNING);
                                $subitems = $subdoc->getElementsByTagName('body')[0]->childNodes;
                                for ($it = $subitems->length; --$it >= 0; ) {
                                    $subnode = $subitems->item($it);
                                    if ($importedNode = $doc->importNode($subnode->cloneNode(true),true)) {
                                        $parent->insertBefore($importedNode,$node->nextSibling);
    //                                    $parent->appendChild($importedNode);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $parent->removeChild($node);
        }
        return $doc;
    }


    public static function domReplaceReportImage(&$doc,$idList,$template,$request){
        $db = TualoApplication::get('session')->getDB();

        $items = $doc->getElementsByTagName('reportimage');
        $nodeListLength = $items->length; 
        for ($i =  $nodeListLength-1; $i >= 0; $i--) {
            $node = $items->item($i);
            $parent = $node->parentNode;
            $id = NULL;
            
            
            $id = NULL;
            $page = NULL;
            $filecolumn = NULL;
            $xattr="";
            
            // dsimage(tablename="dateien",queryfield="name",queryvalue="",filecolumn="datei")
            if ($node->hasAttributes()) {
                if ($node->hasAttribute('id'))
                $id = $node->getAttribute('id');
                if ($node->hasAttribute('page'))
                $page = $node->getAttribute('page');
            
                foreach ($node->attributes as $attr) {
                    $name = $attr->nodeName;
                    if (!in_array($name,array('id','page'))){
                        $value = $attr->nodeValue;
                        $xattr.=' '.$name.'="'.$value.'"';
                    }
                }
            }

            if ($id!==NULL)
            if ($page!==NULL){
                $res = DSReadRoute::read($db,'report_images',array(
                    'sqlcache'=>true,
                    'filter'=>array(
                        array('operator'=>'=','property'=>'report_images__id' ,'value'=>$id),
                        array('operator'=>'=','property'=>'report_images__page' ,'value'=>$page)
                    )
                ));

                if(isset($res['data']) && isset($res['data'][0]) && isset($res['data'][0]['report_images__id'])){
                    $subhtml = '<img src="data:'.$res['data'][0]['report_images__mimetype'].';base64,'.$res['data'][0]['report_images__filedata'].'" '.$xattr.' />';
                    
                    $subdoc = new \DOMDocument();
                    if (!empty(trim(chop($subhtml)))){
                        $subdoc->loadHTML('<?xml encoding="utf-8" ?>'.$subhtml,LIBXML_NOWARNING);
                        $subitems = $subdoc->getElementsByTagName('body')[0]->childNodes;
                        for ($it = $subitems->length; --$it >= 0; ) {
                            $subnode = $subitems->item($it);
                            if ($importedNode = $doc->importNode($subnode->cloneNode(true),true)) {
                                $parent->insertBefore($importedNode,$node->nextSibling);
    //                                    $parent->appendChild($importedNode);
                            }
                        }
                    }
                }
            }
            $parent->removeChild($node);
        }
        return $doc;
    }


    public static function dataMerge($data){
        if (isset($_SESSION['pug_session'])) $data=array_merge(array('session'=>$_SESSION['pug_session']),$data);
        if (isset($GLOBALS['pug_merge'])){
            foreach( $GLOBALS['pug_merge'] as $k=>$v) $data=array_merge(array($k=>$v),$data);
        }
        return $data;
    }

    public static function _render($idList,$template,$request){
        $data = $request;
        
        
        $data['idList'] = $idList;
        $data['template'] = $template;
        $data=self::dataMerge($data);
        
        
        $db = TualoApplication::get('session')->getDB();
        $data['ds']= new DS($db);

        
        $pug = self::getPug();
        $html = $pug->renderFile( self::getPUGPath().'/'.$template.'.pug',$data);

        
        if (empty(trim(chop($html)))){
            return $html;
        }

        
        $doc = new \DOMDocument();
        libxml_use_internal_errors(TRUE);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.''.$html.'',LIBXML_NOWARNING);
        PUGRenderingHelper::domReplaceBarcodeImage($doc,$idList,$template,$request);
        PUGRenderingHelper::domReplaceDS($doc,$idList,$template,$request);
        
        PUGRenderingHelper::domReplaceDSImage($doc,$idList,$template,$request);

        PUGRenderingHelper::domReplaceReportImage($doc,$idList,$template,$request);
        PUGRenderingHelper::domReplaceDSSignumImage($doc,$idList,$template,$request);
        
        return str_replace('<?xml encoding="utf-8" ?>','',$doc->saveHTML());
    }

    public static function render($idList,$template,$request){


        TualoApplication::timing("render0",'');
        TualoApplication::appendTiming(true);
        TualoApplication::timing("render1",'');

        $data = $request;
        $data['idList'] = '';
        if(!is_null($idList)&&isset($idList[0])) $data['idList'] = urldecode($idList[0]);
        $data['template'] = $template;

        set_time_limit(10);

        $db = TualoApplication::get('session')->getDB();
        $tablename = $request['tablename'];

        TualoApplication::timing("render2",'');

        $cssread = DSReadRoute::read($db,'ds_renderer_stylesheet_groups_assign',array(
            'start' => 0, 
            'limit' => 1000000,
            'shortfieldnames'=>1, 
            'sqlcache'=>true,
            'sort' => array(),
            'filter' => array(
                array(
                    'property' => 'ds_renderer_stylesheet_groups_assign__active',
                    'operator' => 'eq',
                    'value'    =>  '1'
                ),
                array(
                    'property' => 'ds_renderer_stylesheet_groups_assign__pug_id',
                    'operator' => 'eq',
                    'value'    =>  $template
                )
            )
        ));
        
        
        if ($cssread){
            $data['stylesheets'] = $cssread['data'];
        }
        TualoApplication::timing("render css", '');


        $request = [ 'start' => 0, 'limit' => 1000000, 'filter' => array(), 'sort' => [] ];
        $request['filter'][] = [ 'property'=> '__id', 'value'=> $idList, 'operator'=> 'in' ];
        $request['shortfieldnames']=1;
        $request['sqlcache']=true;
        
        $read = DSReadRoute::read($db,$tablename,$request);
        
        $read['definition'] = $db->direct('select column_name,label from ds_column_list_label where active=1 and hidden=0 and table_name={table_name} order by position',array('table_name'=>$tablename));
        $data=array_merge($read,$data);

        $data=self::dataMerge($data);
        
        TualoApplication::timing("render before",'');


        //self::cachePUGFiles();
        $pug = self::getPug();
        TualoApplication::timing("render self pug",'');


        $missingRequirements = array_keys(array_filter($pug->requirements(), function ($valid) {
            return $valid === false;
        }));
        $missings = count($missingRequirements);
        if ($missings) {
            echo $missings . ' requirements are missing.<br />';
            foreach ($missingRequirements as $requirement) {
                switch($requirement) {
                    case 'streamWhiteListed':
                        echo 'Suhosin is enabled and ' . $pug->getOption('stream') . ' is not in suhosin.executor.include.whitelist, please add it to your php.ini file.<br />';
                        break;
                    case 'cacheFolderExists':
                        echo 'The cache folder does not exists, please enter in a command line : <code>mkdir -p ' . $pug->getOption('cache') . '</code>.<br />';
                        break;
                    case 'cacheFolderIsWritable':
                        echo 'The cache folder is not writable, please enter in a command line : <code>chmod -R +w ' . $pug->getOption('cache') . '</code>.<br />';
                        break;
                    default:
                        echo $requirement . ' is false.<br />';
                }
            }
            exit(1);
        }

        try{
            $html = $pug->renderFile( self::getPUGPath().'/'.$template.'.pug',$data);
            TualoApplication::timing("render after",'');
            if (empty(trim(chop($html)))){
                return $html;
            }
        }catch(\Exception $e){
            $html=$e->getMessage();
            TualoApplication::logger('error')->error($e->getMessage());
        }

        

        $doc = new \DOMDocument();
        libxml_use_internal_errors(TRUE);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.''.$html.'',LIBXML_NOWARNING);
        PUGRenderingHelper::domReplaceBarcodeImage($doc,$idList,$template,$request);
        PUGRenderingHelper::domReplaceDS($doc,$idList,$template,$request);
        PUGRenderingHelper::domReplaceDSImage($doc,$idList,$template,$request);
        PUGRenderingHelper::domReplaceDSSignumImage($doc,$idList,$template,$request);
        TualoApplication::timing("render5",'');

        return str_replace('<?xml encoding="utf-8" ?>','',$doc->saveHTML());
    }
}