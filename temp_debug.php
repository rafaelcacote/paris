<?php
$text = file_get_contents(__DIR__ . '/temp_output.txt');
$position = stripos($text, 'RetenÃ§Ãµes');
var_dump($position);
$snippet = substr($text, $position, 600);
echo "---\n";
echo $snippet;
preg_match_all('/\d{1,3}(?:\.\d{3})*,\d{2}/', $snippet, $matches);
var_dump($matches[0]);
