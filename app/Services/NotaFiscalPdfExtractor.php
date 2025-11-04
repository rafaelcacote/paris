<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class NotaFiscalPdfExtractor
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser;
    }

    /**
     * Extract data from PDF file.
     */
    public function extract(string $filePath): array
    {
        try {
            $pdf = $this->parser->parseFile($filePath);
            $text = $pdf->getText();
            $details = $pdf->getDetails();

            // Log extracted text for debugging (first 2000 chars)
            Log::debug('PDF Text extracted', [
                'preview' => substr($text, 0, 2000),
                'length' => strlen($text),
            ]);

            $data = $this->parseText($text, $details);

            Log::debug('PDF Data extracted', $data);

            return $data;
        } catch (\Exception $e) {
            Log::error('Erro ao extrair dados do PDF', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Erro ao processar o PDF: '.$e->getMessage());
        }
    }

    /**
     * Parse text content from PDF.
     */
    protected function parseText(string $text, array $details): array
    {
        $data = [];

        // Normalize text - preserve structure but normalize spaces
        // Primeiro vamos normalizar espaços múltiplos para um único espaço
        $textNormalized = preg_replace('/\s+/', ' ', $text);

        // Extract código de verificação - está na linha seguinte ao texto "Código de verificação"
        // Padrão do código: XXXX.XXXX.XXXX ou similar (ex: 5880.7878.DA08, A783.560F.8094)
        // Procurar pelo padrão do código próximo ao texto "Código de verificação"
        $codigoVerificacao = null;

        // Procurar padrão do código diretamente (formato: XXXX.XXXX.XXXX ou similar)
        if (preg_match('/C[óo\digo\s]+\s+de\s+verifica[çc\sãao]+\s*\n?\s*([A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4,})/is', $text, $matches)) {
            $codigoVerificacao = $matches[1];
        } elseif (preg_match('/([A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4,})/s', $text, $matches)) {
            // Se não encontrou próximo ao texto, procurar qualquer código no formato correto
            // Mas verificar se está próximo a "Código de verificação"
            $posCodigoTexto = stripos($text, 'Código de verificação');
            if ($posCodigoTexto !== false) {
                $contexto = substr($text, $posCodigoTexto, 200);
                if (preg_match('/([A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4,})/s', $contexto, $matches)) {
                    $codigoVerificacao = $matches[1];
                }
            }
        }

        if ($codigoVerificacao) {
            // Limpar espaços e manter apenas o código
            $codigoLimpo = preg_replace('/\s+/', '', trim($codigoVerificacao));
            $data['codigo_verificacao'] = $this->truncate($codigoLimpo, 100);
        } else {
            // Generate unique code if not found
            $data['codigo_verificacao'] = 'NF-'.substr(uniqid(), 0, 16);
        }

        // Extract número da nota - usar texto original para capturar melhor
        // Padrão encontrado: "Recolhimento Fora 3956" ou "Número da Nota 3956"
        $numeroNota = null;

        // Tentar primeiro no texto original (com quebras de linha) para capturar melhor
        // Padrão específico: "Número da Nota" seguido de número na mesma linha ou próxima
        $numeroNota = $this->extractPattern($text, [
            '/N[úu]mero\s+da\s+Nota\s+(\d{3,10})/is',
            '/N[úu]mero\s+da\s+nota\s+(\d{3,10})/is',
            '/N[úu]mero\s*da\s*Nota\s*(\d{3,10})/is',
            '/N[úu]mero\s*da\s*nota\s*(\d{3,10})/is',
            // Padrão alternativo: "Natureza da operação Número da Nota" seguido de número
            '/Natureza\s+da\s+opera[çc][ãa]o\s+N[úu]mero\s+da\s+Nota\s+(\d{3,10})/is',
            '/Recolhimento\s+Fora\s+(\d{3,10})/is',
        ]);

        // Se não encontrou, tentar no texto normalizado
        if (! $numeroNota) {
            $numeroNota = $this->extractPattern($textNormalized, [
                '/N[úu]mero\s+da\s+Nota[:\s]+(\d{3,10})/i',
                '/N[úu]mero\s+da\s+nota[:\s]+(\d{3,10})/i',
                '/N[úu]mero\s*da\s*Nota[:\s]*(\d{3,10})/i',
                '/N[úu]mero\s*da\s*nota[:\s]*(\d{3,10})/i',
                '/Natureza\s+da\s+opera[çc][ãa]o\s+N[úu]mero\s+da\s+Nota[:\s]+(\d{3,10})/i',
                '/Recolhimento\s+Fora\s+(\d{3,10})/i',
            ]);
        }

        // Padrões alternativos caso os anteriores não funcionem
        if (! $numeroNota) {
            $numeroNota = $this->extractPattern($textNormalized, [
                '/N[º°]?\s*NF[:\s]*(\d{3,10})/i',
                '/Nota\s*Fiscal\s*N[º°]?\s*[:\s]*(\d{3,10})/i',
            ]);
        }

        if ($numeroNota) {
            $data['numero_nota'] = $this->truncate($numeroNota, 100);
        }

        // Extract data de emissão - usar texto original para capturar melhor
        // Padrão: Código seguido de data e hora na mesma linha
        $dataEmissao = null;
        $horaEmissao = null;

        // Procurar padrão: código de verificação na linha anterior, depois data e hora
        // Exemplo: "5880.7878.DA08 14/10/2025 - 13:13:34"
        if (preg_match('/([A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4}[\.\-][A-Za-z0-9]{4,})\s+(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2}:\d{2})/s', $text, $matches)) {
            $dataEmissao = $matches[2];
            $horaEmissao = $matches[3];
        } elseif (preg_match('/Data\/Hora\s*da\s*emiss[ãa]o\s*\n?\s*([A-Za-z0-9\.\-]{4,})\s+(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2}:\d{2})/is', $text, $matches)) {
            $dataEmissao = $matches[2];
            $horaEmissao = $matches[3];
        } elseif (preg_match('/Data\/Hora\s*da\s*emiss[ãa]o\s*\n?\s*(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2}:\d{2})/is', $text, $matches)) {
            $dataEmissao = $matches[1];
            $horaEmissao = $matches[2];
        }

        if ($dataEmissao) {
            if ($horaEmissao) {
                $data['data_emissao'] = $this->parseDate($dataEmissao.' '.$horaEmissao);
            } else {
            $data['data_emissao'] = $this->parseDate($dataEmissao);
            }
        }

        // Extract nome do tomador do serviço - usar texto original
        // Padrão: "Nome do tomador do serviço" seguido do nome na mesma linha até encontrar "CPF/CNPJ"
        // Exemplo: "Nome do tomador do serviço AMAZONAS ENERGIA S.A CPF/CNPJ"
        $nomeTomadorServico = null;

        // Procurar pela posição do texto "Nome do tomador do serviço"
        $posInicio = stripos($text, 'Nome do tomador do serviço');
        if ($posInicio === false) {
            $posInicio = stripos($text, 'Nome do tomador do servi');
        }
        if ($posInicio === false) {
            $posInicio = stripos($text, 'Nome do tomador');
        }

        if ($posInicio !== false) {
            // Extrair contexto a partir da posição encontrada
            $contexto = substr($text, $posInicio, 200);

            // Tentar extrair o nome usando regex mais flexível no contexto
            if (preg_match('/Nome\s+do\s+tomador\s+do\s+servi[çc][ãa]o\s+([^\n]+?)(?=\s+CPF\/CNPJ|\n)/is', $contexto, $matches)) {
                $nomeTomadorServico = $matches[1];
            } elseif (preg_match('/Nome\s+do\s+tomador\s+do\s+servi[^\n]{0,5}o\s+([A-Z][^\n]{0,100}?)(?=\s+CPF|\n)/is', $contexto, $matches)) {
                $nomeTomadorServico = $matches[1];
            } elseif (preg_match('/Nome\s+do\s+tomador\s+([A-Z][^\n]{0,100}?)(?=\s+CPF|\n)/is', $contexto, $matches)) {
                $nomeTomadorServico = $matches[1];
            } else {
                // Se não encontrou com regex, tentar extrair manualmente
                // Procurar por "CPF/CNPJ" após o texto
                $posCpf = stripos($contexto, 'CPF/CNPJ');
                if ($posCpf === false) {
                    $posCpf = stripos($contexto, 'CPF');
                }
                if ($posCpf !== false) {
                    // Extrair tudo entre "serviço" (ou "servi") e "CPF"
                    $linha = substr($contexto, 0, $posCpf);
                    if (preg_match('/servi[çc][ãa]o\s+(.+)$/is', $linha, $matches)) {
                        $nomeTomadorServico = trim($matches[1]);
                    } elseif (preg_match('/servi[^\n]{0,5}o\s+(.+)$/is', $linha, $matches)) {
                        $nomeTomadorServico = trim($matches[1]);
                    }
                }
            }
        }

        // Se ainda não encontrou, tentar no texto normalizado
        if (! $nomeTomadorServico) {
            $nomeTomadorServico = $this->extractPattern($textNormalized, [
                '/Nome\s+do\s+tomador\s+do\s+servi[çc][ãa]o\s+([^\n]+?)(?=\s+CPF\/CNPJ|\n)/is',
                '/Nome\s+do\s+tomador\s+([^\n]+?)(?=\s+CPF\/CNPJ|\n)/is',
            ]);
        }

        if ($nomeTomadorServico) {
            // Limpar e limitar o nome do tomador
            $nomeLimpo = preg_replace('/\s+/', ' ', trim($nomeTomadorServico));
            // Remover caracteres especiais de encoding que podem ter sobrado
            $nomeLimpo = preg_replace('/[├º├ú├â├ë├ç├ì├â├ö├┤├║├â├║├║├║├║]/', '', $nomeLimpo);
            // Remover endereço se estiver incluído
            $nomeLimpo = preg_replace('/\s+(RUA|AVENIDA|CEP|TELEFONE|EMAIL|DJALMA|ENDERE[ÇC]O|ENDERE├ºO|CPF|CNPJ).*$/i', '', $nomeLimpo);
            // Remover espaços múltiplos finais
            $nomeLimpo = trim($nomeLimpo);
            $data['nome_tomador_servico'] = $this->truncate($nomeLimpo, 200);
        }

        // Extract discriminação do serviço - usar texto original
        // Capturar tudo até encontrar "Descrição do serviço" ou similar
        $discriminacaoServico = $this->extractPattern($text, [
            '/Discrimina[çc][ãa]o\s*do\s*Servi[çc]o\/Dados\s*Adicionais\s*(.*?)(?=Descri[çc][ãa]o\s*do\s*servi[çc]o|Valor\s*do\s*Servi[çc]o|Servi[çc]o:|TRANSPORTE|M[ÁA]O\s*DE\s*OBRA)/is',
            '/Discrimina[çc][ãa]o\s*do\s*Servi[çc]o\s*(.*?)(?=Descri[çc][ãa]o\s*do\s*servi[çc]o|Valor\s*do\s*Servi[çc]o|Servi[çc]o:|TRANSPORTE|M[ÁA]O\s*DE\s*OBRA)/is',
            '/Discrimina[çc][ãa]o\s*Servi[çc]o\s*(.*?)(?=Descri[çc][ãa]o\s*do\s*servi[çc]o|Valor\s*do\s*Servi[çc]o|Servi[çc]o:|TRANSPORTE|M[ÁA]O\s*DE\s*OBRA)/is',
        ]);
        if ($discriminacaoServico) {
            // Limpar espaços múltiplos mas manter estrutura
            $discriminacaoLimpa = preg_replace('/\s{2,}/', ' ', trim($discriminacaoServico));
            // Limitar tamanho
            $data['discriminacao_servico'] = $this->truncate($discriminacaoLimpa, 2000);
        }

        // Usar texto normalizado para extração de valores
        $text = $textNormalized;

        // Extract valores - usar padrões exatos da NF
        $this->extractValues($text, $data);

        // Extract status de pagamento
        $statusPagamento = $this->extractPattern($text, [
            '/Status\s*de\s*Pagamento[:\s]*([^\n]{1,50})/i',
            '/Status\s*Pagamento[:\s]*([^\n]{1,50})/i',
            '/Situação[:\s]*([^\n]{1,50})/i',
        ]);
        if ($statusPagamento) {
            $data['status_pagamento'] = $this->truncate(trim($statusPagamento), 100);
        } else {
            $data['status_pagamento'] = 'Pendente';
        }

        return $data;
    }

    /**
     * Extract monetary values from text - usando padrões exatos da NF.
     */
    protected function extractValues(string $text, array &$data): void
    {
        // Extract valor total - usar padrão exato da NF
        $valorTotal = $this->extractDecimal($text, [
            '/VALOR\s*TOTAL\s*DA\s*NOTA\s*=\s*R\$\s*([\d.,]+)/i',
            '/Total\s*da\s*Nota[:\s]*R\$\s*([\d.,]+)/i',
            '/Valor\s*Total[:\s]*R\$\s*([\d.,]+)/i',
            '/Total[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($valorTotal !== null) {
            $data['valor_total'] = $valorTotal;
        }

        // Extract valor líquido - usar padrão exato da NF
        // Procurar primeiro na seção de retenções onde aparece "Valor Líquido da Nota(R$)"
        $valorLiquido = $this->extractDecimal($text, [
            '/Valor\s*L[íi]quido\s*da\s*Nota\(R\$\)\s+([\d.,]+)/i',
            '/Valor\s*L[íi]quido\s*da\s*Nota\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/Valor\s*L[íi]quido\s*da\s*Nota[:\s]*R\$\s*([\d.,]+)/i',
            '/Valor\s*L[íi]quido[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($valorLiquido !== null) {
            $data['valor_liquido_nota'] = $valorLiquido;
        } else {
            // Calcular valor líquido se não encontrado: valor total - total de retenções
            if (isset($data['valor_total']) && isset($data['total_retencoes'])) {
                $data['valor_liquido_nota'] = $data['valor_total'] - $data['total_retencoes'];
            } elseif (isset($data['valor_total'])) {
                // Se não tem total de retenções, calcular com as retenções individuais
                $retencoes = ($data['inss'] ?? 0) + ($data['pis'] ?? 0) + ($data['cofins'] ?? 0) +
                            ($data['csll'] ?? 0) + ($data['irrf'] ?? 0) + ($data['valor_iss'] ?? 0);
                $data['valor_liquido_nota'] = $data['valor_total'] - $retencoes;
            }
        }

        // Extract base de cálculo - usar padrão exato da NF
        $baseCalculo = $this->extractDecimal($text, [
            '/BASE\s*DE\s*CALCULO\s*ISS[:\s]*R\$\s*([\d.,]+)/i',
            '/BASE\s*DECALCULO\s*ISS[:\s]*R\$\s*([\d.,]+)/i',
            '/BASE\s*DE\s*C[áa]LCULO\s*ISS[:\s]*R\$\s*([\d.,]+)/i',
            '/Base\s*de\s*C[áa]lculo\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/Base\s*de\s*C[áa]lculo[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($baseCalculo !== null) {
            $data['base_calculo'] = $baseCalculo;
        }

        // Extract valor ISS - usar padrão exato da NF
        // IMPORTANTE: Procurar "ISS A RETER" primeiro, pois é mais específico
        $valorIss = $this->extractDecimal($text, [
            '/ISS\s+A\s+RETER[:\s]*R\$\s*([\d.,]+)/i',
            '/ISS\s+A\s+RETER[:\s]*([\d.,]+)/i',
            '/ISS\s+RETIDO[:\s]*R\$\s*([\d.,]+)/i',
            '/Valor\s*do\s*ISS\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/Valor\s*ISS[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($valorIss !== null) {
            $data['valor_iss'] = $valorIss;
        }

        // Extract alíquota - usar padrão exato da NF
        $aliquota = $this->extractDecimal($text, [
            '/Aliquota\s*\(%\)[:\s]*([\d.,]+)/i',
            '/Al[íi]quota\s*\(%\)[:\s]*([\d.,]+)/i',
            '/Al[íi]quota[:\s]*([\d.,]+)\s*%/i',
        ]);
        if ($aliquota !== null) {
            $data['aliquota'] = $aliquota;
        } else {
            // Calcular alíquota se temos valor ISS e base de cálculo
            if (isset($data['valor_iss']) && isset($data['base_calculo']) && $data['base_calculo'] > 0) {
                $data['aliquota'] = ($data['valor_iss'] / $data['base_calculo']) * 100;
            }
        }

        // Extract total - usar padrão exato da NF
        $total = $this->extractDecimal($text, [
            '/Total\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/Total[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($total !== null && ! isset($data['valor_total'])) {
            $data['total'] = $total;
        }

        // Extract retenções - usar padrões exatos da NF
        // IMPORTANTE: Priorizar valores da seção "Retenções" ao invés de bases de cálculo
        // Procurar primeiro na seção de retenções (formato: INSS(R$) seguido de valor)
        $inss = $this->extractDecimal($text, [
            '/Reten[çc][õo]es\s+INSS\(R\$\)\s+([\d.,]+)/is',
            '/INSS\(R\$\)\s+([\d.,]+)/i',
            '/INSS\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/INSS[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        // Só usar base de cálculo se não encontrou na seção de retenções
        if ($inss === null) {
            $inss = $this->extractDecimal($text, [
                '/BASE\s*DE\s*CALCULO\s*INSS[:\s]*R\$\s*([\d.,]+)/i',
                '/BASE\s*DECALCULO\s*INSS[:\s]*R\$\s*([\d.,]+)/i',
            ]);
        }
        if ($inss !== null) {
            $data['inss'] = $inss;
        }

        $pis = $this->extractDecimal($text, [
            '/PIS\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/PIS[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($pis !== null) {
            $data['pis'] = $pis;
        }

        $cofins = $this->extractDecimal($text, [
            '/Cofins\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/COFINS\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/COFINS[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($cofins !== null) {
            $data['cofins'] = $cofins;
        }

        $csll = $this->extractDecimal($text, [
            '/C\.S\.L\.L\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/CSLL\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/CSLL[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($csll !== null) {
            $data['csll'] = $csll;
        }

        $irrf = $this->extractDecimal($text, [
            '/IRRF\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/IRRF[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($irrf !== null) {
            $data['irrf'] = $irrf;
        }

        $issqn = $this->extractDecimal($text, [
            '/ISSQN\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/ISSQN[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($issqn !== null) {
            $data['issqn'] = $issqn;
        }

        $totalRetencoes = $this->extractDecimal($text, [
            '/Total\s*das\s*Retenções\s*\(R\$\)[:\s]*([\d.,]+)/i',
            '/Total\s*Retenções[:\s]*R\$\s*([\d.,]+)/i',
        ]);
        if ($totalRetencoes !== null) {
            $data['total_retencoes'] = $totalRetencoes;
        }
    }

    /**
     * Extract a pattern from text using multiple regex patterns.
     */
    protected function extractPattern(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $matches[1] ?? null;
            }
        }

        return null;
    }

    /**
     * Extract a number from text using multiple regex patterns.
     */
    protected function extractNumber(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $matches[1] ?? null;
            }
        }

        return null;
    }

    /**
     * Extract a decimal value from text using multiple regex patterns.
     */
    protected function extractDecimal(string $text, array $patterns): ?float
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $this->parseDecimal($matches[1]);
            }
        }

        return null;
    }

    /**
     * Parse date string to datetime.
     */
    protected function parseDate(string $dateString): ?string
    {
        try {
            // Normalize separators
            $dateString = str_replace(['-', '.'], '/', trim($dateString));

            // Se tem hora no formato "dd/mm/yyyy HH:ii:ss"
            if (preg_match('/^(\d{2}\/\d{2}\/\d{4})\s+(\d{2}:\d{2}:\d{2})$/', $dateString, $matches)) {
                $date = \DateTime::createFromFormat('d/m/Y H:i:s', $matches[1].' '.$matches[2]);
                if ($date) {
                    return $date->format('Y-m-d H:i:s');
                }
            }

            // Try multiple date formats
            $formats = ['d/m/Y H:i:s', 'd/m/Y', 'd/m/y', 'Y-m-d'];

            foreach ($formats as $format) {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date) {
                    return $date->format('Y-m-d H:i:s');
                }
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao parsear data', ['date' => $dateString]);
        }

        return null;
    }

    /**
     * Parse decimal string to float.
     */
    protected function parseDecimal(string $value): ?float
    {
        // Remove any non-digit characters except comma and dot
        $value = preg_replace('/[^\d.,]/', '', $value);

        // Handle Brazilian format (1.234,56)
        if (preg_match('/^(\d{1,3}(?:\.\d{3})*),(\d{2})$/', $value, $matches)) {
            $value = str_replace('.', '', $matches[1]).'.'.$matches[2];
        }
        // Handle US format (1234.56) or just numbers
        elseif (strpos($value, ',') !== false) {
            // If there's a comma, assume it's decimal separator
            $value = str_replace(',', '.', str_replace('.', '', $value));
        }

        return $value ? (float) $value : null;
    }

    /**
     * Truncate string to specified length.
     */
    protected function truncate(?string $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, $length);
    }
}
