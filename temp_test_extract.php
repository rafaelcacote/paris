<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$extractor = $app->make(App\Services\NotaFiscalPdfExtractor::class);
$data = $extractor->extract(__DIR__ . '/storage/app/private/notas_fiscais/1762533027_690e1ea380a79_NF-3915.pdf');
var_export(['inss' => $data['inss'], 'pis' => $data['pis'], 'cofins' => $data['cofins'], 'csll' => $data['csll'], 'irrf' => $data['irrf'], 'issqn' => $data['issqn'], 'outras_deducoes' => $data['outras_deducoes'], 'total_retencoes' => $data['total_retencoes'], 'valor_liquido_nota' => $data['valor_liquido_nota']]);
