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

        // Total de ISS retido
        $totalIssRetido = NotaFiscal::sum('valor_iss') ?? 0;

        // Total de retenções
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

        // Notas fiscais por mês (últimos 6 meses)
        $notasPorMes = NotaFiscal::selectRaw('
                DATE_TRUNC(\'month\', data_emissao) as mes,
                COUNT(*) as quantidade,
                COALESCE(SUM(valor_total), 0) as valor_total
            ')
            ->whereNotNull('data_emissao')
            ->where('data_emissao', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(function ($item) {
                $carbon = \Carbon\Carbon::parse($item->mes);
                $meses = [
                    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
                    5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                    9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
                ];

                return [
                    'mes' => $meses[$carbon->month].'/'.$carbon->year,
                    'mes_numero' => $carbon->format('m'),
                    'quantidade' => $item->quantidade,
                    'valor_total' => (float) $item->valor_total,
                ];
            });

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
                'total_iss_retido' => (float) $totalIssRetido,
                'total_retencoes' => (float) $totalRetencoes,
            ],
            'status_stats' => $statusStats->toArray(),
            'valor_por_status' => $valorPorStatus->toArray(),
            'notas_por_mes' => $notasPorMes->values()->toArray(),
            'top_tomadores' => $topTomadores->values()->toArray(),
            'notas_recentes' => $notasRecentes->values()->toArray(),
        ]);
    }
}
