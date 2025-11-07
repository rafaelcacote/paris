<?php
$text = file_get_contents(__DIR__ . '/temp_output.txt');
$pos = stripos($text, 'Reten');
var_dump($pos);
if ($pos !== false) {
    echo substr($text, $pos, 200);
    echo "\n---\n";
    preg_match_all('/\d{1,3}(?:\.\d{3})*,\d{2}/', substr($text, $pos, 400), $matches);
    var_dump($matches[0]);
}
