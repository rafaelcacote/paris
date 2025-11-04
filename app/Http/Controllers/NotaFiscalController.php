<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotaFiscalRequest;
use App\Http\Requests\UpdateNotaFiscalRequest;
use App\Http\Requests\UploadNotaFiscalRequest;
use App\Models\NotaFiscal;
use App\Services\NotaFiscalPdfExtractor;
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

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero_nota', 'like', "%{$search}%")
                    ->orWhere('codigo_verificacao', 'like', "%{$search}%")
                    ->orWhere('prestador_razao_social', 'like', "%{$search}%")
                    ->orWhere('tomador_razao_social', 'like', "%{$search}%")
                    ->orWhere('nome_tomador_servico', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status_pagamento')) {
            $query->where('status_pagamento', $request->get('status_pagamento'));
        }

        // Filter by month and year
        if ($request->filled('mes') && $request->filled('ano')) {
            $mes = $request->get('mes');
            $ano = $request->get('ano');
            $query->whereYear('data_emissao', $ano)
                ->whereMonth('data_emissao', $mes);
        } elseif ($request->filled('ano')) {
            $ano = $request->get('ano');
            $query->whereYear('data_emissao', $ano);
        }

        $notasFiscais = $query->latest('data_emissao')->latest('created_at')->paginate(15)->withQueryString();

        // Get available years and months for filter dropdowns
        $anosDisponiveis = NotaFiscal::selectRaw('DISTINCT EXTRACT(YEAR FROM data_emissao)::integer as ano')
            ->whereNotNull('data_emissao')
            ->orderByDesc('ano')
            ->pluck('ano')
            ->map(fn ($ano) => (int) $ano)
            ->toArray();

        return Inertia::render('NotasFiscais/Index', [
            'notasFiscais' => $notasFiscais,
            'filters' => $request->only(['search', 'status_pagamento', 'mes', 'ano']),
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
     * Upload and process PDF file.
     */
    public function upload(UploadNotaFiscalRequest $request): RedirectResponse
    {
        try {
            $file = $request->file('arquivo');
            $fileName = time().'_'.$file->getClientOriginalName();
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

                return redirect()
                    ->back()
                    ->with('duplicate_nota_fiscal', [
                        'id' => $existingNotaFiscal->id,
                        'codigo_verificacao' => $existingNotaFiscal->codigo_verificacao,
                        'numero_nota' => $existingNotaFiscal->numero_nota,
                        'data_emissao' => $existingNotaFiscal->data_emissao?->format('d/m/Y'),
                        'valor_total' => $existingNotaFiscal->valor_total,
                    ])
                    ->withInput();
            }

            // Create nota fiscal
            $notaFiscal = NotaFiscal::create([
                ...$extractedData,
                'arquivo_path' => $filePath,
                'status_pagamento' => $extractedData['status_pagamento'] ?? 'Pendente',
                'usuario_id' => $request->user()->id,
            ]);

            return redirect()
                ->route('notas-fiscais.show', $notaFiscal->id)
                ->with('success', 'Nota fiscal importada com sucesso! Os dados foram extraídos automaticamente do PDF.');
        } catch (QueryException $e) {
            // Handle duplicate key violation
            if ($e->getCode() == 23505 || str_contains($e->getMessage(), 'duplicar valor da chave') || str_contains($e->getMessage(), 'Unique violation')) {
                // Try to extract codigo_verificacao from error message
                // Pattern: codigo_verificacao)=(494C.8F9E.7F09) or similar
                preg_match('/codigo_verificacao.*?[=\(]+\s*([A-Za-z0-9\.\-]+)/', $e->getMessage(), $matches);
                $codigoVerificacao = $matches[1] ?? null;

                if ($codigoVerificacao) {
                    $existingNotaFiscal = NotaFiscal::where('codigo_verificacao', $codigoVerificacao)->first();

                    if ($existingNotaFiscal) {
                        // Delete uploaded file if exists
                        if (isset($filePath) && Storage::disk('private')->exists($filePath)) {
                            Storage::disk('private')->delete($filePath);
                        }

                        return redirect()
                            ->back()
                            ->with('duplicate_nota_fiscal', [
                                'id' => $existingNotaFiscal->id,
                                'codigo_verificacao' => $existingNotaFiscal->codigo_verificacao,
                                'numero_nota' => $existingNotaFiscal->numero_nota,
                                'data_emissao' => $existingNotaFiscal->data_emissao?->format('d/m/Y'),
                                'valor_total' => $existingNotaFiscal->valor_total,
                            ])
                            ->withInput();
                    }
                }
            }

            // Delete uploaded file if exists
            if (isset($filePath) && Storage::disk('private')->exists($filePath)) {
                Storage::disk('private')->delete($filePath);
            }

            return redirect()
                ->back()
                ->withErrors(['arquivo' => 'Erro ao processar o PDF: '.$e->getMessage()])
                ->withInput();
        } catch (\Exception $e) {
            // Delete uploaded file if exists
            if (isset($filePath) && Storage::disk('private')->exists($filePath)) {
                Storage::disk('private')->delete($filePath);
            }

            return redirect()
                ->back()
                ->withErrors(['arquivo' => 'Erro ao processar o PDF: '.$e->getMessage()])
                ->withInput();
        }
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
}
