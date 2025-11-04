<script setup lang="ts">
import notasFiscaisRoutes from '@/routes/notas-fiscais';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Upload, FileText, CheckCircle2, AlertCircle, Eye } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notas Fiscais',
        href: notasFiscaisRoutes.index().url,
    },
    {
        title: 'Importar Nota Fiscal',
        href: notasFiscaisRoutes.create().url,
    },
];

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const dragOver = ref(false);
const processing = ref(false);
const duplicateDialog = ref(false);
const duplicateNotaFiscal = ref<{
    id: number;
    codigo_verificacao: string;
    numero_nota: string | null;
    data_emissao: string | null;
    valor_total: number | null;
} | null>(null);

const page = usePage();

const errors = computed(() => {
    const pageErrors = (page.props as { errors?: Record<string, string> }).errors;
    return pageErrors || {};
});

// Watch for duplicate nota fiscal flash message
watch(
    () => page.props.flash as { duplicate_nota_fiscal?: any; success?: string } | undefined,
    (flash) => {
        if (flash?.duplicate_nota_fiscal) {
            duplicateNotaFiscal.value = flash.duplicate_nota_fiscal;
            duplicateDialog.value = true;
            selectedFile.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
        if (flash?.success) {
            toast.success(flash.success);
            // Limpar arquivo selecionado após sucesso
            selectedFile.value = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        }
    },
    { deep: true, immediate: true },
);

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        selectedFile.value = target.files[0];
    }
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = false;
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        selectedFile.value = event.dataTransfer.files[0];
    }
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    dragOver.value = true;
};

const handleDragLeave = () => {
    dragOver.value = false;
};

const submitForm = () => {
    if (!selectedFile.value) {
        toast.error('Por favor, selecione um arquivo PDF.');
        return;
    }

    processing.value = true;

    router.post(
        '/notas-fiscais/upload',
        {
            arquivo: selectedFile.value,
        },
        {
            forceFormData: true,
            onSuccess: () => {
                processing.value = false;
                // A mensagem de sucesso será exibida via watch quando o flash message chegar
            },
            onError: () => {
                processing.value = false;
            },
        },
    );
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

const viewExistingNotaFiscal = () => {
    if (duplicateNotaFiscal.value?.id) {
        router.visit(notasFiscaisRoutes.show({ notaFiscal: duplicateNotaFiscal.value.id }).url);
    }
};

const closeDuplicateDialog = () => {
    duplicateDialog.value = false;
    duplicateNotaFiscal.value = null;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Importar Nota Fiscal" />

        <div class="space-y-6 px-6 py-6">
            <div class="flex items-center gap-4">
                <Link :href="notasFiscaisRoutes.index()">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Importar Nota Fiscal</h1>
                    <p class="text-sm text-muted-foreground">
                        Faça upload de um arquivo PDF da nota fiscal. O sistema irá extrair
                        automaticamente os dados do documento.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <form @submit.prevent="submitForm" class="space-y-6">
                    <!-- File Upload Area -->
                    <div class="space-y-4">
                        <Label>Arquivo PDF</Label>
                        <div
                            @drop="handleDrop"
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                            :class="[
                                'relative flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 transition-colors',
                                dragOver
                                    ? 'border-primary bg-primary/5'
                                    : 'border-muted-foreground/25 hover:border-primary/50',
                            ]"
                            @click="fileInput?.click()"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                name="arquivo"
                                accept=".pdf"
                                class="hidden"
                                @change="handleFileSelect"
                                required
                            />

                            <div v-if="!selectedFile" class="flex flex-col items-center gap-4">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-full bg-muted"
                                >
                                    <Upload class="h-8 w-8 text-muted-foreground" />
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-medium">
                                        Clique para selecionar ou arraste o arquivo aqui
                                    </p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        Apenas arquivos PDF (máx. 10MB)
                                    </p>
                                </div>
                            </div>

                            <div v-else class="flex flex-col items-center gap-4">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900"
                                >
                                    <CheckCircle2 class="h-8 w-8 text-green-600 dark:text-green-400" />
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-medium">{{ selectedFile.name }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ (selectedFile.size / 1024 / 1024).toFixed(2) }} MB
                                    </p>
                                </div>
                            </div>
                        </div>
                        <InputError :message="errors.arquivo" />
                    </div>

                    <!-- Info Card -->
                    <div class="rounded-lg border bg-muted/30 p-4">
                        <div class="flex gap-3">
                            <FileText class="h-5 w-5 text-muted-foreground" />
                            <div class="flex-1 space-y-1">
                                <p class="text-sm font-medium">Como funciona a importação?</p>
                                <p class="text-xs text-muted-foreground">
                                    O sistema analisa automaticamente o PDF e extrai informações como
                                    número da nota, data de emissão, prestador, tomador, valores e
                                    outros dados relevantes. Após a importação, você poderá revisar e
                                    editar os dados extraídos se necessário.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Link :href="notasFiscaisRoutes.index()">
                            <Button type="button" variant="outline">Cancelar</Button>
                        </Link>
                        <Button type="submit" :disabled="!selectedFile || processing">
                            <Upload v-if="!processing" class="mr-2 h-4 w-4" />
                            {{ processing ? 'Importando...' : 'Importar Nota Fiscal' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Duplicate Nota Fiscal Dialog -->
        <Dialog v-model:open="duplicateDialog">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/20"
                        >
                            <AlertCircle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl">Nota Fiscal Já Cadastrada</DialogTitle>
                            <DialogDescription class="mt-1">
                                Esta nota fiscal já foi importada anteriormente no sistema.
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div v-if="duplicateNotaFiscal" class="space-y-4 py-4">
                    <div class="rounded-lg border bg-muted/30 p-4">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Código de Verificação</span>
                                <span class="text-sm font-mono font-semibold">
                                    {{ duplicateNotaFiscal.codigo_verificacao }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Número da Nota</span>
                                <span class="text-sm font-semibold">
                                    {{ duplicateNotaFiscal.numero_nota || '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Data de Emissão</span>
                                <span class="text-sm font-semibold">
                                    {{ duplicateNotaFiscal.data_emissao || '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground">Valor Total</span>
                                <span class="text-sm font-semibold">
                                    {{ formatCurrency(duplicateNotaFiscal.valor_total) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button variant="outline" @click="closeDuplicateDialog">Fechar</Button>
                    </DialogClose>
                    <Button
                        v-if="duplicateNotaFiscal?.id"
                        @click="viewExistingNotaFiscal"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800"
                    >
                        <Eye class="mr-2 h-4 w-4" />
                        Ver Nota Fiscal
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

