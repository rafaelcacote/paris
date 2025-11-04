<script setup lang="ts">
import NotaFiscalController from '@/actions/App/Http/Controllers/NotaFiscalController';
import notasFiscaisRoutes from '@/routes/notas-fiscais';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft } from 'lucide-vue-next';

interface NotaFiscal {
    id: number;
    numero_nota: string | null;
    codigo_verificacao: string;
    data_emissao: string | null;
    prestador_razao_social: string | null;
    tomador_razao_social: string | null;
    valor_total: number | null;
    status_pagamento: string | null;
    data_pagamento: string | null;
    municipio_tributacao: string | null;
    competencia: string | null;
}

interface Props {
    notaFiscal: NotaFiscal;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notas Fiscais',
        href: notasFiscaisRoutes.index().url,
    },
    {
        title: 'Editar Nota Fiscal',
        href: notasFiscaisRoutes.edit({ notaFiscal: props.notaFiscal.id }).url,
    },
];

const formatDate = (date: string | null): string => {
    if (!date) {
        return '';
    }
    return new Date(date).toISOString().split('T')[0];
};

const dataEmissaoValue = ref(formatDate(props.notaFiscal.data_emissao));
const dataPagamentoValue = ref(formatDate(props.notaFiscal.data_pagamento));
const statusPagamentoValue = ref(props.notaFiscal.status_pagamento || 'Pendente');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar Nota Fiscal" />

        <div class="space-y-6 px-6 py-6">
            <div class="flex items-center gap-4">
                <Link :href="notasFiscaisRoutes.index()">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Editar Nota Fiscal</h1>
                    <p class="text-sm text-muted-foreground">
                        Atualizar informações da nota fiscal
                    </p>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <Form
                    v-bind="NotaFiscalController.update.form({ notaFiscal: props.notaFiscal.id })"
                    @submit="
                        (form) => {
                            form.transform((data) => ({
                                ...data,
                                data_emissao: dataEmissaoValue || null,
                                data_pagamento: dataPagamentoValue || null,
                                status_pagamento: statusPagamentoValue,
                            }));
                        }
                    "
                    @success="
                        () => {
                            router.reload({ only: ['notaFiscal'] });
                        }
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <!-- Informações Básicas -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Informações Básicas</h3>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="numero_nota">Número da Nota</Label>
                                <Input
                                    id="numero_nota"
                                    name="numero_nota"
                                    :default-value="props.notaFiscal.numero_nota || ''"
                                    placeholder="Número da nota"
                                />
                                <InputError :message="errors.numero_nota" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="codigo_verificacao">Código de Verificação</Label>
                                <Input
                                    id="codigo_verificacao"
                                    name="codigo_verificacao"
                                    :default-value="props.notaFiscal.codigo_verificacao"
                                    disabled
                                    class="bg-muted"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="data_emissao">Data de Emissão</Label>
                                <Input
                                    id="data_emissao"
                                    type="date"
                                    v-model="dataEmissaoValue"
                                />
                                <InputError :message="errors.data_emissao" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="competencia">Competência</Label>
                                <Input
                                    id="competencia"
                                    name="competencia"
                                    :default-value="props.notaFiscal.competencia || ''"
                                    placeholder="MM/AAAA"
                                />
                                <InputError :message="errors.competencia" />
                            </div>
                        </div>
                    </div>

                    <!-- Status e Pagamento -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Status e Pagamento</h3>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="status_pagamento">Status de Pagamento</Label>
                                <select
                                    id="status_pagamento"
                                    v-model="statusPagamentoValue"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="Pendente">Pendente</option>
                                    <option value="Pago">Pago</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                                <InputError :message="errors.status_pagamento" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="data_pagamento">Data de Pagamento</Label>
                                <Input
                                    id="data_pagamento"
                                    type="date"
                                    v-model="dataPagamentoValue"
                                />
                                <InputError :message="errors.data_pagamento" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="municipio_tributacao">Município de Tributação</Label>
                                <Input
                                    id="municipio_tributacao"
                                    name="municipio_tributacao"
                                    :default-value="props.notaFiscal.municipio_tributacao || ''"
                                    placeholder="Município de tributação"
                                />
                                <InputError :message="errors.municipio_tributacao" />
                            </div>
                        </div>
                    </div>

                    <!-- Informações de Prestador e Tomador (Read-only) -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Prestador e Tomador</h3>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label>Prestador</Label>
                                <Input
                                    :value="props.notaFiscal.prestador_razao_social || '-'"
                                    disabled
                                    class="bg-muted"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label>Tomador</Label>
                                <Input
                                    :value="props.notaFiscal.tomador_razao_social || '-'"
                                    disabled
                                    class="bg-muted"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Link :href="notasFiscaisRoutes.index()">
                            <Button type="button" variant="outline">Cancelar</Button>
                        </Link>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>

