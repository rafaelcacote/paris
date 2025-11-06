<script setup lang="ts">
import notasFiscaisRoutes from '@/routes/notas-fiscais';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Trash2, FileText, Info, DollarSign, Receipt, User, List } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface NotaFiscal {
    id: number;
    codigo_verificacao: string;
    numero_nota: string | null;
    data_emissao: string | null;
    nome_tomador_servico: string | null;
    discriminacao_servico: string | null;
    valor_total: number | null;
    valor_liquido_nota: number | null;
    base_calculo: number | null;
    aliquota: number | null;
    valor_iss: number | null;
    status_pagamento: string | null;
    data_pagamento: string | null;
    arquivo_path: string | null;
    inss: number | null;
    pis: number | null;
    cofins: number | null;
    csll: number | null;
    irrf: number | null;
    issqn: number | null;
    total_retencoes: number | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    notaFiscal: NotaFiscal;
}

const props = defineProps<Props>();

const page = usePage();

// Watch for success messages from server
watch(
    () => (page.props as { flash?: { success?: string } }).flash,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }
    },
    { deep: true, immediate: true },
);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notas Fiscais',
        href: notasFiscaisRoutes.index().url,
    },
    {
        title: 'Detalhes da Nota Fiscal',
        href: notasFiscaisRoutes.show({ notaFiscal: props.notaFiscal.id }).url,
    },
];

const deleteModal = ref(false);

const formatCurrency = (value: number | null): string => {
    if (value === null || value === undefined) {
        return '-';
    }
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const formatDate = (date: string | null): string => {
    if (!date) {
        return '-';
    }
    return new Date(date).toLocaleDateString('pt-BR');
};

const getStatusBadgeVariant = (status: string | null): 'default' | 'destructive' | 'outline' | 'secondary' => {
    if (!status) {
        return 'secondary';
    }
    const statusLower = status.toLowerCase();
    if (statusLower.includes('pago') || statusLower.includes('paga')) {
        return 'default';
    }
    if (statusLower.includes('pendente')) {
        return 'secondary';
    }
    if (statusLower.includes('cancelado') || statusLower.includes('cancelada')) {
        return 'destructive';
    }
    return 'outline';
};

const getStatusBadgeClass = (status: string | null): string => {
    if (!status) {
        return 'bg-gray-100 text-gray-800 hover:bg-gray-100 dark:bg-gray-900/30 dark:text-gray-400';
    }
    const statusLower = status.toLowerCase();
    if (statusLower.includes('pago') || statusLower.includes('paga')) {
        return 'bg-green-100 text-green-800 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400';
    }
    if (statusLower.includes('pendente')) {
        return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400';
    }
    if (statusLower.includes('cancelado') || statusLower.includes('cancelada')) {
        return 'bg-red-100 text-red-800 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400';
    }
    return '';
};

const viewPdf = (id: number) => {
    const url = `/notas-fiscais/${id}/pdf`;
    window.open(url, '_blank');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Detalhes da Nota Fiscal" />

        <div class="space-y-6 px-6 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="notasFiscaisRoutes.index()">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold">Detalhes da Nota Fiscal</h1>
                        <p class="text-sm text-muted-foreground">
                            Visualizar informações completas da nota fiscal
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="notasFiscaisRoutes.index().url">
                        <Button variant="outline">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Voltar
                        </Button>
                    </Link>
                    <Button
                        v-if="props.notaFiscal.arquivo_path"
                        variant="outline"
                        @click="viewPdf(props.notaFiscal.id)"
                    >
                        <FileText class="mr-2 h-4 w-4 text-red-500" />
                        Ver PDF
                    </Button>
                    <Button variant="destructive" @click="deleteModal = true">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Excluir
                    </Button>
                </div>
            </div>

            <!-- Tomador de Serviço -->
            <div class="rounded-lg border bg-card p-6">
                <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                    <User class="h-5 w-5" />
                    Tomador de Serviço
                </h3>
                <div class="grid gap-2">
                    <Label class="text-sm font-medium">Nome</Label>
                    <p class="text-sm">{{ props.notaFiscal.nome_tomador_servico || '-' }}</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Informações Básicas -->
                <div class="rounded-lg border bg-card p-6">
                    <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <Info class="h-5 w-5" />
                        Informações Básicas
                    </h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Número da Nota</Label>
                            <p class="text-sm">{{ props.notaFiscal.numero_nota || '-' }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Código de Verificação</Label>
                            <p class="text-sm font-mono break-all">{{ props.notaFiscal.codigo_verificacao }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">Data de Emissão</Label>
                                <p class="text-sm">{{ formatDate(props.notaFiscal.data_emissao) }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">Data de Pagamento</Label>
                                <p class="text-sm">{{ formatDate(props.notaFiscal.data_pagamento) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Valores -->
                <div class="rounded-lg border bg-card p-6">
                    <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <DollarSign class="h-5 w-5" />
                        Valores
                    </h3>
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Valor Total</Label>
                            <p class="text-lg font-semibold">
                                {{ formatCurrency(props.notaFiscal.valor_total) }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">Valor Líquido</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.valor_liquido_nota) }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">Base de Cálculo</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.base_calculo) }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">Alíquota</Label>
                                <p class="text-sm">
                                    {{ props.notaFiscal.aliquota ? `${props.notaFiscal.aliquota}%` : '-' }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">Status de Pagamento</Label>
                                <Badge 
                                    :variant="getStatusBadgeVariant(props.notaFiscal.status_pagamento)"
                                    :class="getStatusBadgeClass(props.notaFiscal.status_pagamento)"
                                >
                                    {{ props.notaFiscal.status_pagamento || 'Pendente' }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Impostos -->
                <div class="rounded-lg border bg-card p-6">
                    <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                        <Receipt class="h-5 w-5" />
                        Impostos
                    </h3>
                    <div class="grid gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">INSS</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.inss) }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">PIS</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.pis) }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">COFINS</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.cofins) }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">CSLL</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.csll) }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">IRRF</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.irrf) }}</p>
                            </div>

                            <div class="grid gap-2">
                                <Label class="text-sm font-medium">ISS</Label>
                                <p class="text-sm">{{ formatCurrency(props.notaFiscal.issqn || props.notaFiscal.valor_iss) }}</p>
                            </div>
                        </div>

                        <div class="grid gap-2 border-t pt-2">
                            <Label class="text-sm font-semibold">Total de Retenções</Label>
                            <p class="text-base font-semibold">
                                {{ formatCurrency(props.notaFiscal.total_retencoes) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discriminação do Serviço -->
            <div v-if="props.notaFiscal.discriminacao_servico" class="rounded-lg border bg-card p-6">
                <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                    <List class="h-5 w-5" />
                    Discriminação do Serviço
                </h3>
                <p class="text-sm whitespace-pre-wrap">{{ props.notaFiscal.discriminacao_servico }}</p>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar Exclusão</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja excluir a nota fiscal
                        <strong>{{ props.notaFiscal.numero_nota || props.notaFiscal.codigo_verificacao }}</strong>?
                        Esta ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" @click="deleteModal = false">Cancelar</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        @click="
                            router.delete(
                                notasFiscaisRoutes.destroy.url({ notaFiscal: props.notaFiscal.id }),
                                {
                                    onSuccess: () => {
                                        router.visit(notasFiscaisRoutes.index().url);
                                    },
                                },
                            )
                        "
                    >
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

