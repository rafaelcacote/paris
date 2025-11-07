<?php
require 'vendor/autoload.php';
$parser = new Smalot\PdfParser\Parser();
$pdf = $parser->parseFile(__DIR__ . '/storage/app/private/notas_fiscais/1762533027_690e1ea380a79_NF-3915.pdf');
file_put_contents(__DIR__ . '/temp_output.txt', $pdf->getText());
