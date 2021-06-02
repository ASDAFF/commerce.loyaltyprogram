<?
namespace Commerce\Loyaltyprogram\Tools;

include 'phpqrcode.php';

class Qrcode{

    /**
     * @param string $text for qr code
     * @param string $level maybe L - low(7%), M - average(15%), Q - quarter(25%), H - high(30%)
     * @param int $size pixel size
     * @param int $margin to the border of the image
     */
    public static function show($text='test', $level='Q', $size=4, $margin=5){
        $saveandprint=false; //true, if to browser and to file
        $outfile=$_SERVER["DOCUMENT_ROOT"].'/upload/qrl'.time().'.png';
        \QRcode::png($text, $outfile, $level, $size, $margin, $saveandprint);
        $imageData = base64_encode(file_get_contents($outfile));
        $src = 'data:image/png;base64,'.$imageData;
        return '<img src="'.$src.'">';
        unlink($outfile);
    }

}