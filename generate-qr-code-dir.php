<?php
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$qrCode = new QrCode('https://example.com/user?id=123');

$writer = new PngWriter();
$result = $writer->write($qrCode);

$savePath = __DIR__ . '/qrcodes/user123.png';

if (!file_exists(__DIR__ . '/qrcodes')) {
    mkdir(__DIR__ . '/qrcodes', 0777, true);
}

$result->saveToFile($savePath);

echo "QR Code saved to <strong>$savePath</strong><br>";
echo "<img src='qrcodes/user123.png' alt='QR Code'>";
