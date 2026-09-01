<?php
namespace Nilesh\PrintPdf\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Barcode extends AbstractHelper
{
    private $code128Map = [
        ' '=>0, '!'=>1, '"'=>2, '#'=>3, '$'=>4, '%'=>5, '&'=>6, "'"=>7,
        '('=>8, ')'=>9, '*'=>10, '+'=>11, ','=>12, '-'=>13, '.'=>14, '/'=>15,
        '0'=>16, '1'=>17, '2'=>18, '3'=>19, '4'=>20, '5'=>21, '6'=>22, '7'=>23,
        '8'=>24, '9'=>25, ':'=>26, ';'=>27, '<'=>28, '='=>29, '>'=>30, '?'=>31,
        '@'=>32, 'A'=>33, 'B'=>34, 'C'=>35, 'D'=>36, 'E'=>37, 'F'=>38, 'G'=>39,
        'H'=>40, 'I'=>41, 'J'=>42, 'K'=>43, 'L'=>44, 'M'=>45, 'N'=>46, 'O'=>47,
        'P'=>48, 'Q'=>49, 'R'=>50, 'S'=>51, 'T'=>52, 'U'=>53, 'V'=>54, 'W'=>55,
        'X'=>56, 'Y'=>57, 'Z'=>58, '['=>59, '\\'=>60, ']'=>61, '^'=>62, '_'=>63,
        '`'=>64, 'a'=>65, 'b'=>66, 'c'=>67, 'd'=>68, 'e'=>69, 'f'=>70, 'g'=>71,
        'h'=>72, 'i'=>73, 'j'=>74, 'k'=>75, 'l'=>76, 'm'=>77, 'n'=>78, 'o'=>79,
        'p'=>80, 'q'=>81, 'r'=>82, 's'=>83, 't'=>84, 'u'=>85, 'v'=>86, 'w'=>87,
        'x'=>88, 'y'=>89, 'z'=>90, '{'=>91, '|'=>92, '}'=>93, '~'=>94
    ];

    private $code128Patterns = [
        '11011001100','11001101100','11001100110','10010011000','10010001100',
        '10001001100','10011001000','10011000100','10001100100','11001001000',
        '11001000100','11000100100','10110011100','10011011100','10011001110',
        '10111001100','10011101100','10011100110','11001110010','11001011100',
        '11001001110','11011100100','11001110100','11101101110','11101001100',
        '11100101100','11100100110','11101100100','11100110100','11100110010',
        '11011011000','11011000110','11000110110','10100011000','10001011000',
        '10001000110','10110001000','10001101000','10001100010','11010001000',
        '11000101000','11000100010','10110111000','10110001110','10001101110',
        '10111011000','10111000110','10001110110','11101110110','11010001110',
        '11000101110','11011101000','11011100010','11011101110','11101011000',
        '11101000110','11100010110','11101101000','11101100010','11100011010',
        '11101111010','11001000010','11110001010','10100110000','10100001100',
        '10010110000','10010000110','10000101100','10000100110','10110010000',
        '10110000100','10011010000','10011000010','10000110100','10000110010',
        '11000010010','11001010000','11110111010','11000010100','10001111010',
        '10100111100','10010111100','10010011110','10111100100','10011110100',
        '10011110010','11110100100','11110010100','11110010010','11011011110',
        '11011110110','11110110110','10101111000','10100011110','10001011110',
        '10111101000','10111100010','11110101000','11110100010','10111011110',
        '10111101110','11101011110','11110101110','11010000100','11010010000',
        '11010011100','11000111010'
    ];

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Generate Code 128 barcode as base64 PNG data URI
     * Embed directly in Dompdf HTML: <img src="...">
     *
     * @param string $text      Order ID or any short string
     * @param int    $barWidth  Bar width in pixels (2 = normal, 1 = narrow)
     * @param int    $barHeight Barcode height in pixels
     * @return string data:image/png;base64,...
     */
    public function generateBarcodeBase64($text, $barWidth = 2, $barHeight = 60)
    {
        // ── Sanitize: only ASCII 32–126 allowed in Code 128 B ──
        $clean = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $ord = ord($text[$i]);
            if ($ord >= 32 && $ord <= 126) {
                $clean .= $text[$i];
            }
        }
        $text = $clean;

        // ── Build Code 128 B encoding ───────────────────────────
        $codes    = [104]; // Start B symbol
        $checksum = 104;

        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (!isset($this->code128Map[$char])) {
                continue;
            }
            $val       = $this->code128Map[$char];
            $codes[]   = $val;
            $checksum += $val * ($i + 1);
        }

        $codes[] = $checksum % 103; // Checksum symbol
        $codes[] = 106;             // Stop symbol

        // ── Build binary bar string ─────────────────────────────
        $bars = '';
        foreach ($codes as $code) {
            $bars .= $this->code128Patterns[$code];
        }
        $bars .= '11'; // Termination bar

        // ── Draw barcode using GD ───────────────────────────────
        $imgWidth  = strlen($bars) * $barWidth;
        $imgHeight = $barHeight;

        $img   = imagecreatetruecolor($imgWidth, $imgHeight);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        $x = 0;
        for ($i = 0; $i < strlen($bars); $i++) {
            $color = ($bars[$i] === '1') ? $black : $white;
            imagefilledrectangle($img, $x, 0, $x + $barWidth - 1, $imgHeight - 1, $color);
            $x += $barWidth;
        }

        // ── Capture as base64 ───────────────────────────────────
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($pngData);
    }
}