<script setup lang="ts">
import NotaFiscalController from '@/actions/App/Http/Controllers/NotaFiscalController';
import notasFiscaisRoutes from '@/routes/notas-fiscais';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import {
    Eye,
    Plus,
    Search,
    Trash2,
    X,
    FileText,
    Upload,
} from 'lucide-vue-next';

interface NotaFiscal {
    id: number;
    codigo_verificacao: string;
    numero_nota: string | null;
    data_emissao: string | null;
    prestador_razao_social: string | null;
    tomador_razao_social: string | null;
    nome_tomador_servico: string | null;
    valor_total: number | null;
    status_pagamento: string | null;
    arquivo_path: string | null;
    created_at: string;
}

interface Props {
    notasFiscais: {
        data: NotaFiscal[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        search?: string;
        status_pagamento?: string;
        mes?: string;
        ano?: string;
    };
    anosDisponiveis?: number[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notas Fiscais',
        href: notasFiscaisRoutes.index().url,
    },
];

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

// Modal states
const deleteModal = ref(false);
const selectedNotaFiscal = ref<NotaFiscal | null>(null);

// Search filter
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status_pagamento || '');
const mesFilter = ref(props.filters?.mes || '');
const anoFilter = ref(props.filters?.ano || '');

const meses = [
    { value: '', label: 'Todos os meses' },
    { value: '1', label: 'Janeiro' },
    { value: '2', label: 'Fevereiro' },
    { value: '3', label: 'Março' },
    { value: '4', label: 'Abril' },
    { value: '5', label: 'Maio' },
    { value: '6', label: 'Junho' },
    { value: '7', label: 'Julho' },
    { value: '8', label: 'Agosto' },
    { value: '9', label: 'Setembro' },
    { value: '10', label: 'Outubro' },
    { value: '11', label: 'Novembro' },
    { value: '12', label: 'Dezembro' },
];

const anosDisponiveis = props.anosDisponiveis || [];

const performSearch = () => {
    router.get(
        notasFiscaisRoutes.index().url,
        {
            search: searchQuery.value || undefined,
            status_pagamento: statusFilter.value || undefined,
            mes: mesFilter.value || undefined,
            ano: anoFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearSearch = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    mesFilter.value = '';
    anoFilter.value = '';
    router.get(
        notasFiscaisRoutes.index().url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const openDeleteModal = (notaFiscal: NotaFiscal) => {
    selectedNotaFiscal.value = notaFiscal;
    deleteModal.value = true;
};

const closeDeleteModal = () => {
    deleteModal.value = false;
    selectedNotaFiscal.value = null;
};

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

const updateStatus = (notaFiscalId: number, newStatus: string) => {
    router.patch(`/notas-fiscais/${notaFiscalId}/status`, {
        status_pagamento: newStatus,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Status atualizado com sucesso!');
        },
        onError: () => {
            toast.error('Erro ao atualizar o status.');
        },
    });
};

const viewPdf = (id: number) => {
    const url = `/notas-fiscais/${id}/pdf`;
    window.open(url, '_blank');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notas Fiscais" />

        <div class="space-y-6 px-6 py-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Notas Fiscais</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Gerencie e importe suas notas fiscais
                    </p>
                </div>
                <Link :href="notasFiscaisRoutes.create().url">
                    <Button
                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-lg transition-all duration-200"
                    >
                        <Upload class="mr-2 h-4 w-4" />
                        Importar Nota Fiscal
                    </Button>
                </Link>
            </div>

            <!-- Search Filter Card -->
            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-end">
                    <div class="flex-1">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar por número, código, prestador ou tomador..."
                                class="pl-10"
                                @keyup.enter="performSearch"
                            />
                        </div>
                    </div>

                    <div class="w-full md:w-48">
                        <select
                            v-model="statusFilter"
                            @change="performSearch"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">Todos os status</option>
                            <option value="Pendente">Pendente</option>
                            <option value="Pago">Pago</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="w-full md:w-48">
                        <select
                            v-model="mesFilter"
                            @change="performSearch"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option v-for="mes in meses" :key="mes.value" :value="mes.value">
                                {{ mes.label }}
                            </option>
                        </select>
                    </div>

                    <div class="w-full md:w-48">
                        <select
                            v-model="anoFilter"
                            @change="performSearch"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">Todos os anos</option>
                            <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">
                                {{ ano }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <Button @click="performSearch" variant="default">
                            <Search class="mr-2 h-4 w-4" />
                            Buscar
                        </Button>
                        <Button @click="clearSearch" variant="outline">
                            <X class="mr-2 h-4 w-4" />
                            Limpar
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Notas Fiscais Table Card -->
            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-foreground">
                                    Número
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-foreground">
                                    Código Verificação
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-foreground">
                                    Tomador de Serviço
                                </th>
                               
                                <th class="px-6 py-4 text-left text-sm font-semibold text-foreground">
                                    Data Emissão
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-foreground">
                                    Valor Total
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-foreground">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-foreground">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="notaFiscal in props.notasFiscais.data"
                                :key="notaFiscal.id"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium">
                                        {{ notaFiscal.numero_nota || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-muted-foreground">
                                        {{ notaFiscal.codigo_verificacao }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        {{ notaFiscal.nome_tomador_servico || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-muted-foreground">
                                        {{ formatDate(notaFiscal.data_emissao) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium">
                                        {{ formatCurrency(notaFiscal.valor_total) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <select
                                        :value="notaFiscal.status_pagamento || 'Pendente'"
                                        @change="updateStatus(notaFiscal.id, ($event.target as HTMLSelectElement).value)"
                                        class="cursor-pointer rounded-md border-0 px-2.5 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        :class="getStatusBadgeClass(notaFiscal.status_pagamento)"
                                    >
                                        <option value="Pendente">Pendente</option>
                                        <option value="Pago">Pago</option>
                                        <option value="Cancelado">Cancelado</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            v-if="notaFiscal.arquivo_path"
                                            variant="ghost"
                                            size="icon"
                                            @click="viewPdf(notaFiscal.id)"
                                            title="Ver PDF"
                                        >
                                            <FileText class="h-4 w-4 text-red-500" />
                                        </Button>
                                        <Link v-if="notaFiscal.id" :href="notasFiscaisRoutes.show({ notaFiscal: notaFiscal.id }).url">
                                            <Button variant="ghost" size="icon" title="Ver detalhes">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title="Excluir"
                                            @click="openDeleteModal(notaFiscal)"
                                        >
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.notasFiscais.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <FileText class="h-12 w-12 text-muted-foreground" />
                                        <p class="text-sm text-muted-foreground">
                                            Nenhuma nota fiscal encontrada
                                        </p>
                                        <Link :href="notasFiscaisRoutes.create().url">
                                            <Button variant="outline" size="sm" class="mt-2">
                                                <Upload class="mr-2 h-4 w-4" />
                                                Importar Primeira Nota Fiscal
                                            </Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="props.notasFiscais.last_page > 1"
                    class="border-t bg-muted/30 px-6 py-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Mostrando
                            {{ (props.notasFiscais.current_page - 1) * props.notasFiscais.per_page + 1 }}
                            até
                            {{
                                Math.min(
                                    props.notasFiscais.current_page * props.notasFiscais.per_page,
                                    props.notasFiscais.total,
                                )
                            }}
                            de {{ props.notasFiscais.total }} resultados
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-if="props.notasFiscais.current_page > 1"
                                variant="outline"
                                size="sm"
                                @click="
                                    router.get(
                                        notasFiscaisRoutes.index().url,
                                        {
                                            page: props.notasFiscais.current_page - 1,
                                            search: searchQuery,
                                            status_pagamento: statusFilter,
                                            mes: mesFilter,
                                            ano: anoFilter,
                                        },
                                        { preserveState: true },
                                    )
                                "
                            >
                                Anterior
                            </Button>
                            <Button
                                v-if="props.notasFiscais.current_page < props.notasFiscais.last_page"
                                variant="outline"
                                size="sm"
                                @click="
                                    router.get(
                                        notasFiscaisRoutes.index().url,
                                        {
                                            page: props.notasFiscais.current_page + 1,
                                            search: searchQuery,
                                            status_pagamento: statusFilter,
                                            mes: mesFilter,
                                            ano: anoFilter,
                                        },
                                        { preserveState: true },
                                    )
                                "
                            >
                                Próxima
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar Exclusão</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja excluir a nota fiscal
                        <strong>{{ selectedNotaFiscal?.numero_nota || selectedNotaFiscal?.codigo_verificacao }}</strong>?
                        Esta ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" @click="closeDeleteModal">Cancelar</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        @click="
                            () => {
                                if (selectedNotaFiscal) {
                                    router.delete(
                                        notasFiscaisRoutes.destroy.url({ notaFiscal: selectedNotaFiscal.id }),
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                closeDeleteModal();
                                            },
                                        },
                                    );
                                }
                            }
                        "
                    >
                        Excluir
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

