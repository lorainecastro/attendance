<?php
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$qrCode = new QrCode('https://example.com/user?id=123');

$writer = new PngWriter();
$result = $writer->write($qrCode);

$savePath = 'qrcodes/user123.png';  // relative path without __DIR__

if (!file_exists('qrcodes')) {
    mkdir('qrcodes', 0777, true);
}

$result->saveToFile($savePath);

echo "QR Code saved to <strong>$savePath</strong><br>";
echo "<img src='$savePath' alt='QR Code'>";
