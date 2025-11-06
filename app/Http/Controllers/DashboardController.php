<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // Total de notas fiscais
        $totalNotasFiscais = NotaFiscal::count();

        // Valor total de todas as notas
        $valorTotal = NotaFiscal::sum('valor_total') ?? 0;

        // Valor líquido total
        $valorLiquidoTotal = NotaFiscal::sum('valor_liquido_nota') ?? 0;

        // Total de ISS (issqn + outras_deducoes)
        $totalIssqn = NotaFiscal::sum('issqn') ?? 0;
        $totalOutrasDeducoes = NotaFiscal::sum('outras_deducoes') ?? 0;
        $totalIss = $totalIssqn + $totalOutrasDeducoes;

        // Retenções individuais
        $totalInss = NotaFiscal::sum('inss') ?? 0;
        $totalPis = NotaFiscal::sum('pis') ?? 0;
        $totalCofins = NotaFiscal::sum('cofins') ?? 0;
        $totalCsll = NotaFiscal::sum('csll') ?? 0;
        $totalIrrf = NotaFiscal::sum('irrf') ?? 0;
        $totalRetencoes = NotaFiscal::sum('total_retencoes') ?? 0;

        // Estatísticas por status
        $statusStats = NotaFiscal::selectRaw('status_pagamento, COUNT(*) as count')
            ->groupBy('status_pagamento')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status_pagamento ?? 'Pendente' => $item->count];
            });

        // Valor total por status
        $valorPorStatus = NotaFiscal::selectRaw('status_pagamento, COALESCE(SUM(valor_total), 0) as total')
            ->groupBy('status_pagamento')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status_pagamento ?? 'Pendente' => (float) $item->total];
            });

        // Notas fiscais por trimestre (último ano)
        $notasPorTrimestre = NotaFiscal::selectRaw('
                DATE_TRUNC(\'month\', data_emissao) as mes,
                COUNT(*) as quantidade,
                COALESCE(SUM(valor_total), 0) as valor_total
            ')
            ->whereNotNull('data_emissao')
            ->where('data_emissao', '>=', now()->subYear()->startOfYear())
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->groupBy(function ($item) {
                $carbon = \Carbon\Carbon::parse($item->mes);
                $month = $carbon->month;

                // Determinar trimestre
                if ($month >= 1 && $month <= 3) {
                    return 'Q1_'.$carbon->year;
                } elseif ($month >= 4 && $month <= 6) {
                    return 'Q2_'.$carbon->year;
                } elseif ($month >= 7 && $month <= 9) {
                    return 'Q3_'.$carbon->year;
                } else {
                    return 'Q4_'.$carbon->year;
                }
            })
            ->map(function ($items, $key) {
                [$quarter, $year] = explode('_', $key);

                $trimestreLabels = [
                    'Q1' => 'Jan/Fev/Mar',
                    'Q2' => 'Abr/Maio/Jun',
                    'Q3' => 'Jul/Ago/Set',
                    'Q4' => 'Out/Nov/Dez',
                ];

                return [
                    'trimestre' => $trimestreLabels[$quarter].'/'.$year,
                    'trimestre_key' => $key,
                    'quantidade' => $items->sum('quantidade'),
                    'valor_total' => (float) $items->sum('valor_total'),
                ];
            })
            ->sortBy('trimestre_key')
            ->values();

        // Top 5 tomadores por valor total
        $topTomadores = NotaFiscal::selectRaw('nome_tomador_servico, COUNT(*) as quantidade, COALESCE(SUM(valor_total), 0) as valor_total')
            ->whereNotNull('nome_tomador_servico')
            ->groupBy('nome_tomador_servico')
            ->orderByDesc('valor_total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'nome' => $item->nome_tomador_servico,
                    'quantidade' => $item->quantidade,
                    'valor_total' => (float) $item->valor_total,
                ];
            });

        // Notas fiscais recentes (últimas 5)
        $notasRecentes = NotaFiscal::latest('data_emissao')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($nota) {
                return [
                    'id' => $nota->id,
                    'numero_nota' => $nota->numero_nota,
                    'codigo_verificacao' => $nota->codigo_verificacao,
                    'nome_tomador_servico' => $nota->nome_tomador_servico,
                    'valor_total' => (float) $nota->valor_total,
                    'status_pagamento' => $nota->status_pagamento ?? 'Pendente',
                    'data_emissao' => $nota->data_emissao?->format('d/m/Y'),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_notas_fiscais' => $totalNotasFiscais,
                'valor_total' => (float) $valorTotal,
                'valor_liquido_total' => (float) $valorLiquidoTotal,
                'total_iss' => (float) $totalIss,
                'total_inss' => (float) $totalInss,
                'total_pis' => (float) $totalPis,
                'total_cofins' => (float) $totalCofins,
                'total_csll' => (float) $totalCsll,
                'total_irrf' => (float) $totalIrrf,
                'total_retencoes' => (float) $totalRetencoes,
            ],
            'status_stats' => $statusStats->toArray(),
            'valor_por_status' => $valorPorStatus->toArray(),
            'notas_por_trimestre' => $notasPorTrimestre->toArray(),
            'top_tomadores' => $topTomadores->values()->toArray(),
            'notas_recentes' => $notasRecentes->values()->toArray(),
        ]);
    }
}
