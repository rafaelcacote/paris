<?php

namespace App\Console\Commands;

use App\Services\NotaFiscalPdfExtractor;
use Illuminate\Console\Command;

class TestPdfExtraction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-pdf-extraction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PDF extraction from NF files';

    /**
     * Execute the console command.
     */
    public function handle(NotaFiscalPdfExtractor $extractor): int
    {
        $files = [
            'nfs/NF_3956_AME_TRANSPORTE - MAUÉS - RDR-25-0284.pdf',
            'nfs/NF_3957_AME_TRANSPORTE - MANICORÉ - BEIRADÃO - RDR-25-0328.pdf',
            'nfs/NF_3958_AME_MANUT - ITAPIRANGA - RUA TEREZA DA COSTA - PMI-25-2219.pdf',
        ];

        foreach ($files as $file) {
            $this->info("\n" . str_repeat('=', 80));
            $this->info("Arquivo: $file");
            $this->info(str_repeat('=', 80));

            try {
                // Extract raw text first
                $parser = new \Smalot\PdfParser\Parser;
                $pdf = $parser->parseFile($file);
                $text = $pdf->getText();
                
                $this->info("\nTexto bruto extraído (primeiros 3000 caracteres):");
                $this->line(substr($text, 0, 3000));
                $this->info("\n" . str_repeat('-', 80));
                
                $result = $extractor->extract($file);
                $this->info("\nDados extraídos:");
                $this->table(
                    ['Campo', 'Valor'],
                    collect($result)->map(fn ($value, $key) => [
                        $key,
                        is_array($value) ? json_encode($value) : (string) $value,
                    ])->toArray()
                );
            } catch (\Exception $e) {
                $this->error("ERRO: " . $e->getMessage());
                $this->error($e->getTraceAsString());
            }
        }

        return Command::SUCCESS;
    }
}
