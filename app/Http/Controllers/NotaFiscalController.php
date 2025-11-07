<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotaFiscalRequest;
use App\Http\Requests\UpdateNotaFiscalRequest;
use App\Http\Requests\UploadNotaFiscalRequest;
use App\Models\NotaFiscal;
use App\Services\NotaFiscalPdfExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class NotaFiscalController extends Controller
{
    public function __construct(
        protected NotaFiscalPdfExtractor $pdfExtractor
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = NotaFiscal::query();
        $this->applyFilters($query, $request);

        $notasFiscais = $query->orderByDesc('numero_nota')->latest('created_at')->paginate(15)->withQueryString();

        // Ensure outras_deducoes is always included in the response (even if null)
        $notasFiscais->getCollection()->transform(function ($notaFiscal) {
            if (! isset($notaFiscal->outras_deducoes)) {
                $notaFiscal->outras_deducoes = null;
            }

            return $notaFiscal;
        });

        // Get available years and months for filter dropdowns
        $anosDisponiveis = NotaFiscal::selectRaw('DISTINCT EXTRACT(YEAR FROM data_emissao)::integer as ano')
            ->whereNotNull('data_emissao')
            ->orderByDesc('ano')
            ->pluck('ano')
            ->map(fn ($ano) => (int) $ano)
            ->toArray();

        return Inertia::render('NotasFiscais/Index', [
            'notasFiscais' => $notasFiscais,
            'filters' => $request->only(['search', 'status_pagamento', 'mes', 'ano', 'trimestre']),
            'anosDisponiveis' => $anosDisponiveis,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('NotasFiscais/Upload');
    }

    /**
     * Upload and process PDF files.
     */
    public function upload(UploadNotaFiscalRequest $request): RedirectResponse
    {
        $files = $request->file('arquivos');
        $uploadResults = [];
        $successCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        foreach ($files as $file) {
            $filePath = null;
            $result = [
                'fileName' => $file->getClientOriginalName(),
                'status' => 'processing',
            ];

            try {
                $fileName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
                $filePath = $file->storeAs('notas_fiscais', $fileName, 'private');

                // Get full path for PDF extraction
                $fullPath = Storage::disk('private')->path($filePath);

                // Extract data from PDF
                $extractedData = $this->pdfExtractor->extract($fullPath);

                // Check if nota fiscal already exists by codigo_verificacao
                $existingNotaFiscal = NotaFiscal::where('codigo_verificacao', $extractedData['codigo_verificacao'])->first();

                if ($existingNotaFiscal) {
                    // Delete uploaded file since it's a duplicate
                    Storage::disk('private')->delete($filePath);

                    $result['status'] = 'duplicate';
                    $result['message'] = 'Nota fiscal já cadastrada';
                    $result['duplicateNotaFiscal'] = [
                        'id' => $existingNotaFiscal->id,
                        'codigo_verificacao' => $existingNotaFiscal->codigo_verificacao,
                        'numero_nota' => $existingNotaFiscal->numero_nota,
                        'data_emissao' => $existingNotaFiscal->data_emissao?->format('d/m/Y'),
                        'valor_total' => $existingNotaFiscal->valor_total,
                    ];
                    $duplicateCount++;
                } else {
                    // Create nota fiscal
                    NotaFiscal::create([
                        ...$extractedData,
                        'arquivo_path' => $filePath,
                        'status_pagamento' => $extractedData['status_pagamento'] ?? 'Pendente',
                        'usuario_id' => $request->user()->id,
                    ]);

                    $result['status'] = 'success';
                    $result['message'] = 'Nota fiscal importada com sucesso!';
                    $successCount++;
                }
            } catch (QueryException $e) {
                // Handle duplicate key violation
                if ($e->getCode() == 23505 || str_contains($e->getMessage(), 'duplicar valor da chave') || str_contains($e->getMessage(), 'Unique violation')) {
                    // Try to extract codigo_verificacao from error message
                    preg_match('/codigo_verificacao.*?[=\(]+\s*([A-Za-z0-9\.\-]+)/', $e->getMessage(), $matches);
                    $codigoVerificacao = $matches[1] ?? null;

                    if ($codigoVerificacao) {
                        $existingNotaFiscal = NotaFiscal::where('codigo_verificacao', $codigoVerificacao)->first();

                        if ($existingNotaFiscal) {
                            // Delete uploaded file if exists
                            if ($filePath && Storage::disk('private')->exists($filePath)) {
                                Storage::disk('private')->delete($filePath);
                            }

                            $result['status'] = 'duplicate';
                            $result['message'] = 'Nota fiscal já cadastrada';
                            $result['duplicateNotaFiscal'] = [
                                'id' => $existingNotaFiscal->id,
                                'codigo_verificacao' => $existingNotaFiscal->codigo_verificacao,
                                'numero_nota' => $existingNotaFiscal->numero_nota,
                                'data_emissao' => $existingNotaFiscal->data_emissao?->format('d/m/Y'),
                                'valor_total' => $existingNotaFiscal->valor_total,
                            ];
                            $duplicateCount++;
                        } else {
                            // Delete uploaded file if exists
                            if ($filePath && Storage::disk('private')->exists($filePath)) {
                                Storage::disk('private')->delete($filePath);
                            }

                            $result['status'] = 'error';
                            $result['message'] = 'Erro ao processar o PDF: '.$e->getMessage();
                            $errorCount++;
                        }
                    } else {
                        // Delete uploaded file if exists
                        if ($filePath && Storage::disk('private')->exists($filePath)) {
                            Storage::disk('private')->delete($filePath);
                        }

                        $result['status'] = 'error';
                        $result['message'] = 'Erro ao processar o PDF: '.$e->getMessage();
                        $errorCount++;
                    }
                } else {
                    // Delete uploaded file if exists
                    if ($filePath && Storage::disk('private')->exists($filePath)) {
                        Storage::disk('private')->delete($filePath);
                    }

                    $result['status'] = 'error';
                    $result['message'] = 'Erro ao processar o PDF: '.$e->getMessage();
                    $errorCount++;
                }
            } catch (\Exception $e) {
                // Delete uploaded file if exists
                if ($filePath && Storage::disk('private')->exists($filePath)) {
                    Storage::disk('private')->delete($filePath);
                }

                $result['status'] = 'error';
                $result['message'] = 'Erro ao processar o PDF: '.$e->getMessage();
                $errorCount++;
            }

            $uploadResults[] = $result;
        }

        // Build success message
        $messages = [];
        if ($successCount > 0) {
            $messages[] = "{$successCount} ".($successCount === 1 ? 'nota fiscal importada' : 'notas fiscais importadas').' com sucesso!';
        }
        if ($duplicateCount > 0) {
            $messages[] = "{$duplicateCount} ".($duplicateCount === 1 ? 'nota fiscal já estava' : 'notas fiscais já estavam').' cadastrada'.($duplicateCount > 1 ? 's' : '').'.';
        }
        if ($errorCount > 0) {
            $messages[] = "{$errorCount} ".($errorCount === 1 ? 'erro' : 'erros').' ao processar arquivo'.($errorCount > 1 ? 's' : '').'.';
        }

        $successMessage = implode(' ', $messages);

        return redirect()
            ->route('notas-fiscais.create')
            ->with('upload_results', $uploadResults)
            ->with('success', $successMessage ?: 'Processamento concluído.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNotaFiscalRequest $request): RedirectResponse
    {
        NotaFiscal::create($request->validated());

        return redirect()
            ->route('notas-fiscais.index')
            ->with('success', 'Nota fiscal criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(NotaFiscal $notaFiscal): Response
    {
        return Inertia::render('NotasFiscais/Show', [
            'notaFiscal' => $notaFiscal,
        ]);
    }

    /**
     * Download or view PDF file.
     */
    public function pdf(NotaFiscal $notaFiscal)
    {
        if (! $notaFiscal->arquivo_path) {
            abort(404, 'Arquivo PDF não encontrado.');
        }

        $disk = Storage::disk('private');

        if (! $disk->exists($notaFiscal->arquivo_path)) {
            abort(404, 'Arquivo PDF não encontrado no servidor.');
        }

        $filePath = $disk->path($notaFiscal->arquivo_path);
        $fileName = 'NF-'.($notaFiscal->numero_nota ?? $notaFiscal->codigo_verificacao).'.pdf';

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NotaFiscal $notaFiscal): Response
    {
        return Inertia::render('NotasFiscais/Edit', [
            'notaFiscal' => $notaFiscal,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotaFiscalRequest $request, NotaFiscal $notaFiscal): RedirectResponse
    {
        $notaFiscal->update($request->validated());

        return redirect()
            ->route('notas-fiscais.index')
            ->with('success', 'Nota fiscal atualizada com sucesso.');
    }

    /**
     * Update status of the specified resource.
     */
    public function updateStatus(Request $request, NotaFiscal $notaFiscal): RedirectResponse
    {
        $request->validate([
            'status_pagamento' => ['required', 'string', 'in:Pendente,Pago,Cancelado'],
        ]);

        $notaFiscal->update([
            'status_pagamento' => $request->status_pagamento,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Status atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NotaFiscal $notaFiscal): RedirectResponse
    {
        // Delete PDF file if exists
        if ($notaFiscal->arquivo_path && Storage::disk('private')->exists($notaFiscal->arquivo_path)) {
            Storage::disk('private')->delete($notaFiscal->arquivo_path);
        }

        $notaFiscal->delete();

        return redirect()
            ->route('notas-fiscais.index')
            ->with('success', 'Nota fiscal excluída com sucesso.');
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero_nota', 'like', "%{$search}%")
                    ->orWhere('codigo_verificacao', 'like', "%{$search}%")
                    ->orWhere('nome_tomador_servico', 'like', "%{$search}%")
                    ->orWhere('discriminacao_servico', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status_pagamento')) {
            $query->where('status_pagamento', $request->get('status_pagamento'));
        }

        // Filter by date - trimestre has priority over mes
        if ($request->filled('trimestre') && $request->filled('ano')) {
            $trimestre = (int) $request->get('trimestre');
            $ano = (int) $request->get('ano');

            // Define os meses de cada trimestre
            $mesesTrimestre = [
                1 => [1, 2, 3],   // Jan, Fev, Mar
                2 => [4, 5, 6],   // Abr, Mai, Jun
                3 => [7, 8, 9],   // Jul, Ago, Set
                4 => [10, 11, 12], // Out, Nov, Dez
            ];

            if (isset($mesesTrimestre[$trimestre]) && $trimestre > 0) {
                $meses = $mesesTrimestre[$trimestre];

                $query->whereNotNull('data_emissao')
                    ->where(function ($q) use ($ano, $meses) {
                        $q->whereRaw('EXTRACT(YEAR FROM data_emissao) = ?', [$ano])
                            ->whereRaw('EXTRACT(MONTH FROM data_emissao) IN ('.implode(',', $meses).')');
                    });
            }
        } elseif ($request->filled('mes') && $request->filled('ano')) {
            // Filter by month and year (only if trimestre is not selected)
            $mes = (int) $request->get('mes');
            $ano = (int) $request->get('ano');
            $query->whereNotNull('data_emissao')
                ->whereRaw('EXTRACT(YEAR FROM data_emissao) = ?', [$ano])
                ->whereRaw('EXTRACT(MONTH FROM data_emissao) = ?', [$mes]);
        } elseif ($request->filled('ano')) {
            // Filter by year only
            $ano = (int) $request->get('ano');
            $query->whereNotNull('data_emissao')
                ->whereRaw('EXTRACT(YEAR FROM data_emissao) = ?', [$ano]);
        } elseif ($request->filled('mes')) {
            // Filter by month only (current year)
            $mes = (int) $request->get('mes');
            $query->whereNotNull('data_emissao')
                ->whereRaw('EXTRACT(YEAR FROM data_emissao) = ?', [now()->year])
                ->whereRaw('EXTRACT(MONTH FROM data_emissao) = ?', [$mes]);
        }
    }

    /**
     * Generate and download PDF report.
     */
    public function printReport(Request $request)
    {
        $query = NotaFiscal::query();
        $this->applyFilters($query, $request);

        $notasFiscais = $query->orderByDesc('numero_nota')->latest('created_at')->get();

        // Build filter description
        $filtersDescription = $this->buildFiltersDescription($request);

        // Calculate totals
        $totals = [
            'valor_total' => $notasFiscais->sum('valor_total') ?? 0,
            'inss' => $notasFiscais->sum('inss') ?? 0,
            'pis' => $notasFiscais->sum('pis') ?? 0,
            'cofins' => $notasFiscais->sum('cofins') ?? 0,
            'csll' => $notasFiscais->sum('csll') ?? 0,
            'irrf' => $notasFiscais->sum('irrf') ?? 0,
            'issqn' => $notasFiscais->sum('issqn') ?? 0,
            'outras_deducoes' => $notasFiscais->sum('outras_deducoes') ?? 0,
            'total_retencoes' => $notasFiscais->sum('total_retencoes') ?? 0,
            'valor_liquido_nota' => $notasFiscais->sum('valor_liquido_nota') ?? 0,
        ];

        $pdf = Pdf::loadView('notas-fiscais.report', [
            'notasFiscais' => $notasFiscais,
            'filters' => $filtersDescription,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'user' => $request->user(),
        ]);

        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 20);
        $pdf->setOption('margin-bottom', 20);
        $pdf->setOption('margin-left', 15);
        $pdf->setOption('margin-right', 15);

        return $pdf->stream('relatorio_notas_fiscais_'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Build filters description for report.
     */
    protected function buildFiltersDescription(Request $request): array
    {
        $description = [];

        if ($request->filled('search')) {
            $description[] = 'Busca: '.$request->get('search');
        }

        if ($request->filled('status_pagamento')) {
            $description[] = 'Status: '.$request->get('status_pagamento');
        }

        if ($request->filled('trimestre') && $request->filled('ano')) {
            $trimestreLabels = [
                1 => '1º Trimestre - Jan/Fev/Mar',
                2 => '2º Trimestre - Abr/Mai/Jun',
                3 => '3º Trimestre - Jul/Ago/Set',
                4 => '4º Trimestre - Out/Nov/Dez',
            ];
            $trimestre = (int) $request->get('trimestre');
            $ano = (int) $request->get('ano');
            $description[] = ($trimestreLabels[$trimestre] ?? 'Trimestre '.$trimestre).' de '.$ano;
        } elseif ($request->filled('mes') && $request->filled('ano')) {
            $meses = [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
            ];
            $mes = (int) $request->get('mes');
            $ano = (int) $request->get('ano');
            $description[] = ($meses[$mes] ?? 'Mês '.$mes).' de '.$ano;
        } elseif ($request->filled('ano')) {
            $description[] = 'Ano: '.$request->get('ano');
        } elseif ($request->filled('mes')) {
            $meses = [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
            ];
            $mes = (int) $request->get('mes');
            $description[] = ($meses[$mes] ?? 'Mês '.$mes).' de '.now()->year;
        }

        if (empty($description)) {
            $description[] = 'Todos os registros';
        }

        return $description;
    }

    /**
     * Generate and download PDF summary report.
     */
    public function printSummary(Request $request)
    {
        $query = NotaFiscal::query();
        $this->applyFilters($query, $request);

        $notasFiscais = $query->orderByDesc('numero_nota')->latest('created_at')->get();

        // Build filter description
        $filtersDescription = $this->buildFiltersDescription($request);

        // Calculate totals
        $totals = [
            'valor_total' => $notasFiscais->sum('valor_total') ?? 0,
            'total_retencoes' => $notasFiscais->sum('total_retencoes') ?? 0,
            'valor_liquido_nota' => $notasFiscais->sum('valor_liquido_nota') ?? 0,
        ];

        $pdf = Pdf::loadView('notas-fiscais.summary', [
            'notasFiscais' => $notasFiscais,
            'filters' => $filtersDescription,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'user' => $request->user(),
        ]);

        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 20);
        $pdf->setOption('margin-bottom', 20);
        $pdf->setOption('margin-left', 15);
        $pdf->setOption('margin-right', 15);

        return $pdf->stream('relatorio_resumido_notas_fiscais_'.now()->format('Y-m-d').'.pdf');
    }
}
