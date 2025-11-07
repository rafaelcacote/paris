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

    public function extract(string $filePath): array
    {
        try {
            $pdf = $this->parser->parseFile($filePath);
            $text = $pdf->getText();

            Log::debug('PDF Text extracted', [
                'preview' => substr($text, 0, 1500),
            ]);

            return $this->parseText($text);

        } catch (\Exception $e) {
            Log::error('Erro ao extrair dados do PDF', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Erro ao processar o PDF: '.$e->getMessage());
        }
    }

    protected function parseText(string $text): array
    {
        $data = [];

        // Dados básicos
        $data['codigo_verificacao'] = $this->extractCodigoVerificacao($text);
        $data['numero_nota'] = $this->extractNumeroNota($text);
        $data['data_emissao'] = $this->extractDataEmissao($text);

        // Empresas - extrair e mapear para campos do modelo
        $prestador = $this->extractPrestador($text);
        $tomador = $this->extractTomador($text);

        // Mapear tomador para o campo do modelo
        $data['nome_tomador_servico'] = $tomador['nome'] ?? null;

        // Valores principais
        $data['valor_total'] = $this->extractValorTotal($text);
        $data['base_calculo'] = $this->extractBaseCalculoISS($text);
        $data['valor_iss'] = $this->extractValorISS($text);

        // Retenções - mapear array para campos individuais do modelo
        $retencoes = $this->extractRetencoesEstruturadas($text);
        $data['inss'] = $retencoes['inss'] ?? 0.00;
        $data['pis'] = $retencoes['pis'] ?? 0.00;
        $data['cofins'] = $retencoes['cofins'] ?? 0.00;
        $data['csll'] = $retencoes['csll'] ?? 0.00;
        $data['irrf'] = $retencoes['irrf'] ?? 0.00;

        // Segunda tabela: ISSQN, Outras Deduções, Total das Retenções, Valor Líquido da Nota
        $segundaTabela = $this->extractSegundaTabelaEstruturada($text);
        $data['issqn'] = $segundaTabela['issqn'] ?? 0.00;
        $data['outras_deducoes'] = $segundaTabela['outras_deducoes'] ?? 0.00;
        // Usar total_retencoes da segunda tabela se disponível, senão usar o calculado das retenções
        $data['total_retencoes'] = $segundaTabela['total_retencoes'] > 0 ? $segundaTabela['total_retencoes'] : ($retencoes['total'] ?? 0.00);
        $data['valor_liquido_nota'] = $segundaTabela['valor_liquido_nota'] ?? null;

        // Serviço
        $data['discriminacao_servico'] = $this->extractDiscriminacaoServico($text);

        return $data;
    }

    protected function extractCodigoVerificacao(string $text): ?string
    {
        // Padrão para capturar código após "Código de verificação" e antes da data
        if (preg_match('/Código de verificação\s+([A-Z0-9]{4}\.[A-Z0-9]{4}\.[A-Z0-9]{4})/i', $text, $matches)) {
            return $matches[1];
        }
        // Fallback: tentar padrão alternativo
        if (preg_match('/([A-Z0-9]{4}\.[A-Z0-9]{4}\.[A-Z0-9]{4})\s+\d{2}\/\d{2}\/\d{4}/', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function extractNumeroNota(string $text): ?string
    {
        // Foco no padrão específico do PDF
        if (preg_match('/Recolhimento\s+Fora\s+(\d{3,})/i', $text, $matches)) {
            return $matches[1];
        }

        if (preg_match('/Número\s+da\s+Nota\s+(\d{3,})/i', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function extractDataEmissao(string $text): ?string
    {
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2}:\d{2})/', $text, $matches)) {
            try {
                $date = \DateTime::createFromFormat('d/m/Y H:i:s', $matches[1].' '.$matches[2]);

                return $date ? $date->format('Y-m-d') : null;
            } catch (\Exception $e) {
                return null;
            }
        }

        // Fallback: tentar apenas data sem hora
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $text, $matches)) {
            try {
                $date = \DateTime::createFromFormat('d/m/Y', $matches[1]);

                return $date ? $date->format('Y-m-d') : null;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    protected function extractPrestador(string $text): array
    {
        $prestador = [];

        // CNPJ do prestador - padrão específico após "CPF/CNPJ"
        if (preg_match('/Prestador de Serviços.*?CPF\/CNPJ\s+([\d]{2}\.[\d]{3}\.[\d]{3}\/[\d]{4}-[\d]{2})/s', $text, $matches)) {
            $prestador['cnpj'] = $matches[1];
        }

        // Nome - entre "Prestador de Serviços" e "CPF/CNPJ"
        if (preg_match('/Prestador de Serviços\s+(.*?)\s+CPF\/CNPJ/s', $text, $matches)) {
            $nome = trim($matches[1]);
            // Limpar quebras de linha e espaços extras
            $nome = preg_replace('/\s+/', ' ', $nome);
            $prestador['nome'] = trim($nome);
        }

        // Inscrição municipal - após CNPJ
        if (preg_match('/CPF\/CNPJ\s+[\d.\/\-]+\s+Inscrição Municipal\s+(\d+)/', $text, $matches)) {
            $prestador['inscricao_municipal'] = $matches[1];
        }

        return $prestador;
    }

    protected function extractTomador(string $text): array
    {
        $tomador = [];

        // Nome do tomador - padrão específico após "Nome do tomador do serviço"
        if (preg_match('/Nome do tomador do serviço\s+([^\n\r]+)/i', $text, $matches)) {
            $nome = trim($matches[1]);
            // Limpar espaços extras
            $nome = preg_replace('/\s+/', ' ', $nome);
            $tomador['nome'] = trim($nome);
        }

        // CNPJ do tomador - após "CPF/CNPJ" na seção do tomador
        if (preg_match('/Tomador de Serviço.*?CPF\/CNPJ\s+([\d]{2}\.[\d]{3}\.[\d]{3}\/[\d]{4}-[\d]{2})/s', $text, $matches)) {
            $tomador['cnpj'] = $matches[1];
        }

        return $tomador;
    }

    protected function extractValorTotal(string $text): ?float
    {
        if (preg_match('/VALOR\s*TOTAL\s*DA\s*NOTA\s*=\s*R\$\s*([\d.,]+)/i', $text, $matches)) {
            return $this->parseDecimal($matches[1]);
        }

        return null;
    }

    protected function extractBaseCalculoISS(string $text): ?float
    {
        if (preg_match('/BASE\s*DECALCULO\s*ISS:\s*R\$\s*([\d.,]+)/i', $text, $matches)) {
            return $this->parseDecimal($matches[1]);
        }

        return null;
    }

    protected function extractValorISS(string $text): ?float
    {
        if (preg_match('/ISS\s+A\s+RETER:\s*R\$\s*([\d.,]+)/i', $text, $matches)) {
            return $this->parseDecimal($matches[1]);
        }

        return null;
    }

    protected function extractRetencoesEstruturadas(string $text): array
    {
        $retencoes = [
            'inss' => 0.00,
            'pis' => 0.00,
            'cofins' => 0.00,
            'csll' => 0.00,
            'irrf' => 0.00,
            'total' => 0.00,
        ];

        // Procura pela tabela de retenções - formato tabular com valores separados por tabulação
        // Padrão: INSS(R$) PIS(R$) Cofins(R$) C.S.L.L(R$) IRRF(R$) seguido pelos valores na próxima linha (podendo ter linha vazia entre)
        // Primeiro tentar capturar diretamente após os headers
        if (preg_match('/INSS\(R\$\)[\s\t]+PIS\(R\$\)[\s\t]+Cofins\(R\$\)[\s\t]+C\.S\.L\.L\(R\$\)[\s\t]+IRRF\(R\$\)[\r\n]+[\s\t]*[\r\n]*[\s\t]*([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)/i', $text, $matches)) {
            $retencoes['inss'] = $this->parseDecimal($matches[1]) ?? 0.00;
            $retencoes['pis'] = $this->parseDecimal($matches[2]) ?? 0.00;
            $retencoes['cofins'] = $this->parseDecimal($matches[3]) ?? 0.00;
            $retencoes['csll'] = $this->parseDecimal($matches[4]) ?? 0.00;
            $retencoes['irrf'] = $this->parseDecimal($matches[5]) ?? 0.00;
        } elseif (preg_match('/INSS\(R\$\)[\s\t]+PIS\(R\$\)[\s\t]+Cofins\(R\$\)[\s\t]+C\.S\.L\.L\(R\$\)[\s\t]+IRRF\(R\$\)[\r\n]+.*?[\r\n]+[\s\t]*([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)/is', $text, $matches)) {
            $retencoes['inss'] = $this->parseDecimal($matches[1]) ?? 0.00;
            $retencoes['pis'] = $this->parseDecimal($matches[2]) ?? 0.00;
            $retencoes['cofins'] = $this->parseDecimal($matches[3]) ?? 0.00;
            $retencoes['csll'] = $this->parseDecimal($matches[4]) ?? 0.00;
            $retencoes['irrf'] = $this->parseDecimal($matches[5]) ?? 0.00;
        } else {
            // Fallback: procura valores individuais próximos aos labels
            $retencoes['inss'] = $this->extractDecimalSimples($text, 'INSS\(R\$\)') ?? 0.00;
            $retencoes['pis'] = $this->extractDecimalSimples($text, 'PIS\(R\$\)') ?? 0.00;
            $retencoes['cofins'] = $this->extractDecimalSimples($text, 'Cofins\(R\$\)') ?? 0.00;
            $retencoes['csll'] = $this->extractDecimalSimples($text, 'C\.S\.L\.L\(R\$\)') ?? 0.00;
            $retencoes['irrf'] = $this->extractDecimalSimples($text, 'IRRF\(R\$\)') ?? 0.00;
            
            if ($this->retencoesTodasZeradas($retencoes)) {
                $sequencia = $this->extractNumericSequence($text, 'INSS(R$)', 5);

                if ($sequencia) {
                    [$retencoes['inss'], $retencoes['pis'], $retencoes['cofins'], $retencoes['csll'], $retencoes['irrf']] = $sequencia;
                }
            }
        }

        // Total das retenções - padrão específico
        if (preg_match('/Total das Retenções\s*\(R\$\)\s*([\d.,]+)/i', $text, $matches)) {
            $retencoes['total'] = $this->parseDecimal($matches[1]) ?? 0.00;
        } elseif (preg_match('/Total das Retenções\s*\(R\$\)[\s\t]+([\d.,]+)/i', $text, $matches)) {
            $retencoes['total'] = $this->parseDecimal($matches[1]) ?? 0.00;
        } else {
            // Calcular total se não encontrado
            $retencoes['total'] = $retencoes['inss'] + $retencoes['pis'] + $retencoes['cofins'] + $retencoes['csll'] + $retencoes['irrf'];
        }

        return $retencoes;
    }

    protected function extractDecimalSimples(string $text, string $label): ?float
    {
        $pattern = '/'.preg_quote($label, '/').'\s*([\d.,]+)/i';
        if (preg_match($pattern, $text, $matches)) {
            return $this->parseDecimal($matches[1]);
        }

        return null;
    }

    protected function extractSegundaTabelaEstruturada(string $text): array
    {
        $dados = [
            'issqn' => 0.00,
            'outras_deducoes' => 0.00,
            'total_retencoes' => 0.00,
            'valor_liquido_nota' => null,
        ];

        // Procura pela segunda tabela - formato tabular com valores separados por tabulação
        // Padrão: ISSQN(R$) Outras Deduções(R$) Total das Retenções (R$) Valor Líquido da Nota(R$)
        // seguido pelos valores na próxima linha (podendo ter linha vazia entre)
        // Primeiro tentar capturar diretamente após os headers
        if (preg_match('/ISSQN\(R\$\)[\s\t]+Outras Deduções\s*\(R\$\)[\s\t]+Total das Retenções\s*\(R\$\)[\s\t]+Valor Líquido da Nota\s*\(R\$\)[\r\n]+[\s\t]*[\r\n]*[\s\t]*([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)/i', $text, $matches)) {
            $dados['issqn'] = $this->parseDecimal($matches[1]) ?? 0.00;
            $dados['outras_deducoes'] = $this->parseDecimal($matches[2]) ?? 0.00;
            $dados['total_retencoes'] = $this->parseDecimal($matches[3]) ?? 0.00;
            $dados['valor_liquido_nota'] = $this->parseDecimal($matches[4]);
        } elseif (preg_match('/ISSQN\(R\$\)[\s\t]+Outras Deduções\s*\(R\$\)[\s\t]+Total das Retenções\s*\(R\$\)[\s\t]+Valor Líquido da Nota\s*\(R\$\)[\r\n]+.*?[\r\n]+[\s\t]*([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)[\s\t]+([\d.,]+)/is', $text, $matches)) {
            $dados['issqn'] = $this->parseDecimal($matches[1]) ?? 0.00;
            $dados['outras_deducoes'] = $this->parseDecimal($matches[2]) ?? 0.00;
            $dados['total_retencoes'] = $this->parseDecimal($matches[3]) ?? 0.00;
            $dados['valor_liquido_nota'] = $this->parseDecimal($matches[4]);
        } else {
            // Fallback: procura valores individuais próximos aos labels
            $dados['issqn'] = $this->extractDecimalSimples($text, 'ISSQN\(R\$\)') ?? 0.00;
            $dados['outras_deducoes'] = $this->extractDecimalSimples($text, 'Outras Deduções\s*\(R\$\)') ?? 0.00;
            $dados['total_retencoes'] = $this->extractDecimalSimples($text, 'Total das Retenções\s*\(R\$\)') ?? 0.00;
            $dados['valor_liquido_nota'] = $this->extractDecimalSimples($text, 'Valor Líquido da Nota\s*\(R\$\)');

            if (($dados['issqn'] ?? 0.00) === 0.00 && ($dados['outras_deducoes'] ?? 0.00) === 0.00 && ($dados['total_retencoes'] ?? 0.00) === 0.00 && ! $dados['valor_liquido_nota']) {
                $sequencia = $this->extractNumericSequence($text, 'ISSQN(R$)', 4);

                if ($sequencia) {
                    [$dados['issqn'], $dados['outras_deducoes'], $dados['total_retencoes'], $dados['valor_liquido_nota']] = $sequencia;
                }
            }
        }

        return $dados;
    }

    protected function retencoesTodasZeradas(array $retencoes): bool
    {
        return ($retencoes['inss'] ?? 0.00) === 0.00
            && ($retencoes['pis'] ?? 0.00) === 0.00
            && ($retencoes['cofins'] ?? 0.00) === 0.00
            && ($retencoes['csll'] ?? 0.00) === 0.00
            && ($retencoes['irrf'] ?? 0.00) === 0.00;
    }

    protected function extractNumericSequence(string $text, string $anchor, int $expectedCount): ?array
    {
        $position = stripos($text, $anchor);

        if ($position === false) {
            return null;
        }

        $snippet = substr($text, $position, 600);

        preg_match_all('/\d{1,3}(?:\.\d{3})*,\d{2}/', $snippet, $matches);

        if (count($matches[0]) < $expectedCount) {
            return null;
        }

        $numbers = array_slice($matches[0], 0, $expectedCount);

        return array_map(function ($value) {
            return $this->parseDecimal($value) ?? 0.00;
        }, $numbers);
    }

    protected function extractDiscriminacaoServico(string $text): ?string
    {
        // Capturar desde "Discriminação do Serviço/Dados Adicionais" até "Descrição do serviço"
        if (preg_match('/Discriminação do Serviço\/Dados Adicionais\s*(.*?)(?=Descrição do serviço|Valor do Serviço)/is', $text, $matches)) {
            $discriminacao = trim($matches[1]);
            // Limpar espaços extras e quebras de linha múltiplas
            $discriminacao = preg_replace('/\s+/', ' ', $discriminacao);

            return trim($discriminacao);
        }

        return null;
    }

    protected function parseDecimal(?string $value): ?float
    {
        if (! $value) {
            return null;
        }

        // Remove R$ e espaços
        $value = preg_replace('/R\$\s*/', '', $value);
        $value = trim($value);

        // Converte formato brasileiro para float
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return floatval($value);
    }
}
