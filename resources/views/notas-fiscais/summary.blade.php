<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Resumido de Notas Fiscais</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
            padding: 0;
            margin: 0;
        }

        .report-header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            margin-top: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .report-header .logo-section {
            display: table-cell;
            width: 150px;
            vertical-align: middle;
        }

        .report-header .logo-section img {
            max-width: 120px;
            max-height: 60px;
            object-fit: contain;
        }

        .report-header .title-section {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }

        .report-header .title-section h1 {
            font-size: 18pt;
            color: #1e293b;
            font-weight: bold;
            margin: 0;
        }

        .report-header .empty-section {
            display: table-cell;
            width: 150px;
        }

        .info-section {
            background: #f8fafc;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
        }

        .info-section p {
            margin: 3px 0;
            font-size: 8pt;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            font-size: 8pt;
        }

        thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
        }

        thead th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: white !important;
            background: #3b82f6 !important;
        }

        tbody td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #334155;
        }

        tbody tr:nth-child(even) {
            background: #fafbfc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 25px;
            padding-top: 12px;
            padding-bottom: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 7pt;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-size: 10pt;
        }

        .total-row {
            background: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #3b82f6;
        }

        .total-row td {
            padding: 10px 8px;
            font-size: 9pt;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="logo-section">
            <img src="{{ public_path('img/logo.jpeg') }}" alt="Logo">
        </div>
        <div class="title-section">
            <h1>Relatório Resumido de Notas Fiscais</h1>
        </div>
        <div class="empty-section"></div>
    </div>

    <div class="info-section">
        <p><strong>Total de registros:</strong> {{ count($notasFiscais) }} nota(s) fiscal(is)</p>
        <p><strong>Período:</strong> {{ implode(' | ', $filters) }}</p>
        <p><strong>Gerado por:</strong> {{ $user->name ?? 'Usuário' }}</p>
        <p><strong>Gerado em:</strong> {{ $generatedAt }}</p>
    </div>

    @if(count($notasFiscais) > 0)
        <table>
            <thead>
                <tr>
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Data Emissão</th>
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Número NF</th>
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Tomador</th>
                    <th style="padding: 10px 8px; text-align: right; font-weight: 600; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Valor Bruto</th>
                    <th style="padding: 10px 8px; text-align: right; font-weight: 600; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Impostos</th>
                    <th style="padding: 10px 8px; text-align: right; font-weight: 600; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Valor Líquido</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notasFiscais as $nota)
                    <tr>
                        <td>{{ $nota->data_emissao ? \Carbon\Carbon::parse($nota->data_emissao)->format('d/m/Y') : '-' }}</td>
                        <td>{{ $nota->numero_nota ?? '-' }}</td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">{{ Str::limit($nota->nome_tomador_servico ?? '-', 40) }}</td>
                        <td class="text-right">R$ {{ number_format($nota->valor_total ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->total_retencoes ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->valor_liquido_nota ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="padding: 10px 8px; text-align: right; font-size: 9pt; color: #1e293b;">TOTAL:</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 9pt; color: #1e293b;">R$ {{ number_format($totals['valor_total'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 9pt; color: #1e293b;">R$ {{ number_format($totals['total_retencoes'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 9pt; color: #1e293b;">R$ {{ number_format($totals['valor_liquido_nota'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="no-data">
            <p>Nenhuma nota fiscal encontrada com os filtros aplicados.</p>
        </div>
    @endif

    <div class="footer">
        <p>Relatório gerado por {{ $user->name ?? 'Usuário' }} em {{ $generatedAt }} - Sistema Paris</p>
    </div>
</body>
</html>

