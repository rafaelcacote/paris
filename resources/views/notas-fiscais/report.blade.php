<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Notas Fiscais</title>
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
            font-size: 8pt;
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
            font-size: 7pt;
        }

        thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
        }

        thead th {
            padding: 8px 5px;
            text-align: left;
            font-weight: 600;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: white !important;
            background: #3b82f6 !important;
        }

        tbody td {
            padding: 6px 5px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 7pt;
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

        .totals-section {
            background: #f8fafc;
            padding: 15px;
            margin-top: 20px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
        }

        .totals-section h2 {
            font-size: 12pt;
            color: #1e293b;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
            font-weight: bold;
        }

        .totals-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 8px;
        }

        .totals-grid .total-item {
            display: table-cell;
            width: 16.66%;
            background: white;
            padding: 10px;
            border-left: 3px solid #3b82f6;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            vertical-align: top;
            border-radius: 4px;
        }

        .total-item {
            background: white;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #3b82f6;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .total-item label {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .total-item .value {
            font-size: 10pt;
            font-weight: bold;
            color: #1e293b;
        }

        .totals-main {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 10px;
        }

        .totals-main .total-item-main {
            display: table-cell;
            width: 33.33%;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            vertical-align: middle;
        }

        .total-item-main {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
        }

        .total-item-main label {
            font-size: 8pt;
            opacity: 0.9;
            display: block;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .total-item-main .value {
            font-size: 14pt;
            font-weight: bold;
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

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 6pt;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-pago {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-cancelado {
            background: #fee2e2;
            color: #991b1b;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="logo-section">
            <img src="{{ public_path('img/logo.jpeg') }}" alt="Logo">
        </div>
        <div class="title-section">
            <h1>Relatório de Notas Fiscais</h1>
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
                <tr style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
                    <th style="padding: 8px 5px; text-align: left; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Número</th>
                    <th style="padding: 8px 5px; text-align: left; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Tomador de Serviço</th>
                    <th style="padding: 8px 5px; text-align: left; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Data Emissão</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Valor Total</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">INSS</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">PIS</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">COFINS</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">CSLL</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">IRRF</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">ISS</th>
                    <th style="padding: 8px 5px; text-align: right; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Total Retenções</th>
                    <th style="padding: 8px 5px; text-align: center; font-weight: 600; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.3px; color: white; background: #3b82f6;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notasFiscais as $nota)
                    <tr>
                        <td>{{ $nota->numero_nota ?? '-' }}</td>
                        <td style="max-width: 80px; overflow: hidden; text-overflow: ellipsis;">{{ Str::limit($nota->nome_tomador_servico ?? '-', 25) }}</td>
                        <td>{{ $nota->data_emissao ? \Carbon\Carbon::parse($nota->data_emissao)->format('d/m/Y') : '-' }}</td>
                        <td class="text-right">R$ {{ number_format($nota->valor_total ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->inss ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->pis ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->cofins ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->csll ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->irrf ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format(($nota->issqn ?? 0) + ($nota->outras_deducoes ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($nota->total_retencoes ?? 0, 2, ',', '.') }}</td>
                        <td class="text-center">
                            @php
                                $status = strtolower($nota->status_pagamento ?? 'pendente');
                                $badgeClass = 'badge-pendente';
                                if ($status === 'pago' || $status === 'paga') {
                                    $badgeClass = 'badge-pago';
                                } elseif ($status === 'cancelado' || $status === 'cancelada') {
                                    $badgeClass = 'badge-cancelado';
                                }
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $nota->status_pagamento ?? 'Pendente' }}</span>
                        </td>
                    </tr>
                @endforeach
                <tr style="background: #f8fafc; font-weight: bold; border-top: 2px solid #3b82f6;">
                    <td colspan="3" style="padding: 10px 8px; text-align: right; font-size: 8pt; color: #1e293b;">TOTAL:</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['valor_total'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['inss'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['pis'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['cofins'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['csll'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['irrf'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['issqn'] + $totals['outras_deducoes'], 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 10px 8px; font-size: 8pt; color: #1e293b;">R$ {{ number_format($totals['total_retencoes'], 2, ',', '.') }}</td>
                    <td class="text-center" style="padding: 10px 8px;">-</td>
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
