<?php

use App\Services\NotaFiscalPdfExtractor;

it('extracts retention values when the PDF separates headers and amounts', function () {
    $extractor = new NotaFiscalPdfExtractor();

    $text = <<<TEXT
Código de verificação 410A.04FB.4D57
Recolhimento Fora 3915
24/09/2025 - 14:34:13
VALOR TOTAL DA NOTA = R$ 63.263,62
BASE DECALCULO ISS: R$ 63.263,62
ISS A RETER: R$ 3.163,18

Retenções
INSS(R$)
PIS(R$)
Cofins(R$)
C.S.L.L(R$)
IRRF(R$)

6.959,00
0,00
0,00
632,64
948,95

ISSQN(R$) Outras Deduções(R$) Total das Retenções (R$) Valor Líquido da Nota(R$)
0,00 3.163,18 11.703,77 51.559,85
TEXT;

    $parseText = (function (string $content) {
        return $this->parseText($content);
    })->bindTo($extractor, NotaFiscalPdfExtractor::class);

    $data = $parseText($text);

    expect($data['inss'])->toBe(6959.0)
        ->and($data['pis'])->toBe(0.0)
        ->and($data['cofins'])->toBe(0.0)
        ->and($data['csll'])->toBe(632.64)
        ->and($data['irrf'])->toBe(948.95)
        ->and($data['total_retencoes'])->toBe(11703.77);
});

