<?php

namespace Tualo\Office\PUG;

use Tualo\Office\Basic\TualoApplication;
use Dompdf\Dompdf;
use Dompdf\Options;
use Ramsey\Uuid\Uuid;

class DomPDFRenderingHelper
{

    public static function  injectPageCount(Dompdf $dompdf)
    {
        /** @var CPDF $canvas */
        $canvas = $dompdf->getCanvas();
        $pdf = $canvas->get_cpdf();

        foreach ($pdf->objects as &$o) {
            if ($o['t'] === 'contents') {
                $o['c'] = str_replace('^T', str_pad($canvas->get_page_count(), 2, " ", STR_PAD_LEFT), $o['c']);

                $rpl = "";
                $l = " " . str_pad($canvas->get_page_count(), 1, " ", STR_PAD_LEFT);
                for ($i = 0; $i < strlen($l); $i++) $rpl .= "\x00" . $l[$i];

                $o['c'] = str_replace("(\x00^\x00T)", "(" . $rpl . ")", $o['c']);
            }
        }
    }

    public static function  injectOMR(Dompdf $dompdf)
    {
        /** @var CPDF $canvas */
    }


    public static function render($matches, $request)
    {
        $db = TualoApplication::get('session')->getDB();
        $template = $matches['template'];


        try {
            $request['pug_dbname'] = $db->dbname;
            $orientation = $db->singleValue('select max(orientation) o from ds_renderer where pug_template={template}', $matches, 'o');

            PUGRenderingHelper::exportPUG($db);
            $idList = PUGRenderingHelper::getIDArray($matches, $request);
            $files = array();
            if (isset($_REQUEST['bulkpdf'])) {
                $bulkpath = preg_replace('/[^0-9a-z\-]/', '', $_REQUEST['bulkpdf']);
                if (!file_exists(TualoApplication::get('tempPath') . '/' . $bulkpath . '')) mkdir(TualoApplication::get('tempPath') . '/' . $bulkpath . '');
                if (!file_exists(TualoApplication::get('tempPath') . '/' . $bulkpath . '/.htfiles')) file_put_contents(TualoApplication::get('tempPath') . '/' . $bulkpath . '/.htfiles', json_encode(array()));
                $files = json_decode(file_get_contents(TualoApplication::get('tempPath') . '/' . $bulkpath . '/.htfiles'), true);
                $request['save'] = TualoApplication::get('tempPath') . '/' . $bulkpath . '/' . Uuid::uuid4()->toString() . '.pdf';
            }

            foreach ($idList as $id) {

                $html = PUGRenderingHelper::render(array($id), $template, $request);
                $options = new Options();
                $options->set('defaultFont', 'Helvetica');
                $options->set('isRemoteEnabled', TRUE);
                //$options->set('isPhpEnabled', TRUE);
                $dompdf = new Dompdf($options);



                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => FALSE,
                        'verify_peer_name' => FALSE,
                        'allow_self_signed' => TRUE
                    ]
                ]);
                $dompdf->setBasePath('erpvfs://');
                $dompdf->setHttpContext($context);

                //$dompdf->set_option("isPhpEnabled", true);



                $dompdf->loadHtml($html);


                $dompdf->setPaper('A4', $orientation); //'portrait');


                $dompdf->render();



                DomPDFRenderingHelper::injectPageCount($dompdf);
                DomPDFRenderingHelper::injectOMR($dompdf);

                if (isset($request['save'])) {
                    $output = $dompdf->output();
                    file_put_contents($request['save'], $output);
                    $files[] = $request['save'];
                } else {
                    TualoApplication::stopbuffering();
                    $dompdf->stream("BN-" . $id . ".pdf", array("Attachment" => false));
                }
            }

            if (isset($_REQUEST['bulkpdf'])) {
                $bulkpath = preg_replace('/[^0-9a-z\-]/', '', $_REQUEST['bulkpdf']);
                file_put_contents(TualoApplication::get('tempPath') . '/' . $bulkpath . '/.htfiles', json_encode($files));

                TualoApplication::result('success', true);
                TualoApplication::contenttype('application/json');
            }
        } catch (\Exception $e) {
            $options = new Options();
            $options->set('defaultFont', 'helvetica');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml('<pre>*' . $e->getMessage() . '</pre>');
            TualoApplication::stopbuffering();
            $dompdf->stream("ERROR.pdf", array("Attachment" => false));
        }
    }
}
