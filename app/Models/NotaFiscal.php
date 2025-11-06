<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaFiscal extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'financeiro.notas_fiscais';

    protected $fillable = [
        'codigo_verificacao',
        'numero_nota',
        'data_emissao',
        'discriminacao_servico',
        'valor_total',
        'base_calculo',
        'aliquota',
        'valor_iss',
        'total',
        'inss',
        'pis',
        'cofins',
        'csll',
        'irrf',
        'issqn',
        'outras_deducoes',
        'total_retencoes',
        'valor_liquido_nota',
        'status_pagamento',
        'data_pagamento',
        'arquivo_path',
        'nome_tomador_servico',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'data_pagamento' => 'date',
            'valor_total' => 'decimal:2',
            'base_calculo' => 'decimal:2',
            'aliquota' => 'decimal:2',
            'valor_iss' => 'decimal:2',
            'total' => 'decimal:2',
            'inss' => 'decimal:2',
            'pis' => 'decimal:2',
            'cofins' => 'decimal:2',
            'csll' => 'decimal:2',
            'irrf' => 'decimal:2',
            'issqn' => 'decimal:2',
            'outras_deducoes' => 'decimal:2',
            'total_retencoes' => 'decimal:2',
            'valor_liquido_nota' => 'decimal:2',
        ];
    }

    /**
     * Get the user that imported this nota fiscal.
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
