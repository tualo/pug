<?php

namespace Tualo\Office\PUG;

use Dompdf\Dompdf;
use Dompdf\Options;
use Tualo\Office\DS\DSTable as T;
use Tualo\Office\Basic\TualoApplication as App;
use GuzzleHttp\Client;

class PDF2
{
    public static function render(
        string $tablename,
        string $template,
        string $id
    ): string {
        if (App::configuration('browsershot', 'remote_service', '') == '') {

            $pug = new PUG2(App::get('session')->getDB(), PUGOptions::getOptions());
            $data = T::instance($tablename)
                ->f('__id', '=', $id)
                ->read()
                ->get();
            $html = $pug->render($template, $data);
            return self::useDomPDF($html);
        } else {
            return self::useRemoteService($tablename, $template, $id);
        }
    }




    private static function useDomPDF($html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        $dompdf = new Dompdf($options);
        $dompdf->setHttpContext($context);
        $dompdf->loadHtml($html);
        $dompdf->render();
        // $dompdf->setPaper('A4', 'portrait');
        return $dompdf->output();
    }

    private static function useRemoteService($tablename, $template, $id): string
    {
        $db = App::get('session')->getDB();
        $pdf = '';
        $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '' . dirname($_SERVER['SCRIPT_NAME']) . '' . $db->singleValue('select @sessionid s', [], 's') . '/pug2html/' . $tablename . '/' . $template . '/' . $id . '';
        if (isset($_SESSION['tualoapplication']['oauth'])) {
            $session = App::get('session');
            $token = $session->registerOAuth(
                $params = ['cmp' => 'cmp_ds'],
                $force = true,
                $anyclient = false,
                $path = '/pug2html/' . $tablename . '/' . $template . '/' . $id
            );
            $session->oauthSingleUse($token);
            $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/~/' . $token . '' . $db->singleValue('select @sessionid s', [], 's') . '/pug2html/' . $tablename . '/' . $template . '/' . $id . '';
        }

        $client = new Client(
            [
                'base_uri' => App::configuration('browsershot', 'remote_service', ''),
                'timeout'  => floatval(App::configuration('browsershot', 'remote_service_timeout', 3.0)),
            ]
        );

        $cookie = @session_get_cookie_params();
        $cookie['name'] = @session_name();
        $cookie['value'] = @session_id();
        $cookie['domain'] = $_SERVER['HTTP_HOST'];

        $o = [
            'url' => $url,
            'cookies' => [$cookie],
        ];
        if (isset($_SESSION['tualoapplication']['oauth'])) {
            $o = [
                'url' => $url
            ];
        }
        $response = $client->post('/pdf', [
            'json' => $o
        ]);

        $code = $response->getStatusCode(); // 200
        if ($code == 200) {
            $pdf = $response->getBody();
        } else {
            return '';
        }
        if ($token != '') {
            $session->removeToken($token);
        }
        return $pdf;
    }
}
