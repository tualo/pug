<?php

namespace Tualo\Office\PUG;

use Tualo\Office\Basic\TualoApplication;
use Tualo\Office\DS\DataRenderer;
use Picqer\Barcode\BarcodeGeneratorPNG;
use chillerlan\QRCode\QRCode;

class Barcode
{

    public static function get($type, $data)
    {
        if ($type == 'qr') {
            return (new QRCode)->render($data);
        }
        $generator = new BarcodeGeneratorPNG();
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

        if (!isset($types[$type])) throw new \Exception('Barcode type not found');
        return 'data:image/png;base64,' . base64_encode($generator->getBarcode($data, $types[$type]));
    }
}
