<script setup lang="ts">
import notasFiscaisRoutes from '@/routes/notas-fiscais';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    FileText,
    DollarSign,
    TrendingUp,
    Receipt,
    Users,
    Calendar,
    ArrowUpRight,
} from 'lucide-vue-next';

interface Props {
    stats: {
        total_notas_fiscais: number;
        valor_total: number;
        valor_liquido_total: number;
        total_iss: number;
        total_inss: number;
        total_pis: number;
        total_cofins: number;
        total_csll: number;
        total_irrf: number;
        total_retencoes: number;
    };
    status_stats: Record<string, number>;
    valor_por_status: Record<string, number>;
    notas_por_trimestre: Array<{
        trimestre: string;
        trimestre_key: string;
        quantidade: number;
        valor_total: number;
    }>;
    top_tomadores: Array<{
        nome: string;
        quantidade: number;
        valor_total: number;
    }>;
    notas_recentes: Array<{
        id: number;
        numero_nota: string | null;
        codigo_verificacao: string;
        nome_tomador_servico: string | null;
        valor_total: number;
        status_pagamento: string;
        data_emissao: string | null;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const getStatusBadgeVariant = (status: string): 'default' | 'destructive' | 'outline' | 'secondary' => {
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

const getStatusBadgeClass = (status: string): string => {
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

// Calcular valores padrão para evitar erros
const pendentes = props.status_stats?.Pendente || 0;
const pagas = props.status_stats?.Pago || 0;
const canceladas = props.status_stats?.Cancelado || 0;
const totalStatus = pendentes + pagas + canceladas;

const percentualPendentes = totalStatus > 0 ? (pendentes / totalStatus) * 100 : 0;
const percentualPagas = totalStatus > 0 ? (pagas / totalStatus) * 100 : 0;
const percentualCanceladas = totalStatus > 0 ? (canceladas / totalStatus) * 100 : 0;

// Máximo de valor para o gráfico de barras
const maxValorTrimestre = Math.max(...(props.notas_por_trimestre?.map((n) => n.valor_total) || [0]), 1);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-6 py-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Visão geral das notas fiscais importadas
                    </p>
                </div>
                <Link :href="notasFiscaisRoutes.create().url">
                    <Button>
                        <FileText class="mr-2 h-4 w-4" />
                        Importar Nota Fiscal
                    </Button>
                </Link>
            </div>

            <!-- Stats Cards - Primeira Linha -->
            <div class="grid gap-4 md:grid-cols-4">
                <!-- Total de Notas Fiscais -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total de Notas Fiscais</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.stats.total_notas_fiscais }}</div>
                        <p class="text-xs text-muted-foreground">Notas importadas</p>
                    </CardContent>
                </Card>

                <!-- Valor Total -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Valor Total</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.valor_total) }}</div>
                        <p class="text-xs text-muted-foreground">Soma de todas as notas</p>
                    </CardContent>
                </Card>

                <!-- Valor Líquido -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Valor Líquido</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.valor_liquido_total) }}</div>
                        <p class="text-xs text-muted-foreground">Após retenções</p>
                    </CardContent>
                </Card>

                <!-- ISS -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">ISS</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.total_iss) }}</div>
                        <p class="text-xs text-muted-foreground">ISSQN + Outras Deduções</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Stats Cards - Segunda Linha: Retenções -->
            <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
                <!-- INSS -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">INSS</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.total_inss) }}</div>
                        <p class="text-xs text-muted-foreground">Total retido</p>
                    </CardContent>
                </Card>

                <!-- PIS -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">PIS</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.total_pis) }}</div>
                        <p class="text-xs text-muted-foreground">Total retido</p>
                    </CardContent>
                </Card>

                <!-- COFINS -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">COFINS</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.total_cofins) }}</div>
                        <p class="text-xs text-muted-foreground">Total retido</p>
                    </CardContent>
                </Card>

                <!-- CSLL -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">CSLL</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.total_csll) }}</div>
                        <p class="text-xs text-muted-foreground">Total retido</p>
                    </CardContent>
                </Card>

                <!-- IRRF -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">IRRF</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.total_irrf) }}</div>
                        <p class="text-xs text-muted-foreground">Total retido</p>
                    </CardContent>
                </Card>

                <!-- Total Retenções -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Retenções</CardTitle>
                        <Receipt class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(props.stats.total_retencoes) }}</div>
                        <p class="text-xs text-muted-foreground">Soma de todas</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts and Tables Row -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Status Distribution -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle>Status de Pagamento</CardTitle>
                        <CardDescription>Distribuição por status</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Pendente -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Pendente</span>
                                <span class="font-medium">{{ pendentes }} ({{ percentualPendentes.toFixed(1) }}%)</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full bg-yellow-500 transition-all"
                                    :style="{ width: `${percentualPendentes}%` }"
                                />
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ formatCurrency(props.valor_por_status?.Pendente || 0) }}
                            </div>
                        </div>

                        <!-- Pago -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Pago</span>
                                <span class="font-medium">{{ pagas }} ({{ percentualPagas.toFixed(1) }}%)</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full bg-green-500 transition-all"
                                    :style="{ width: `${percentualPagas}%` }"
                                />
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ formatCurrency(props.valor_por_status?.Pago || 0) }}
                            </div>
                        </div>

                        <!-- Cancelado -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Cancelado</span>
                                <span class="font-medium">{{ canceladas }} ({{ percentualCanceladas.toFixed(1) }}%)</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full bg-red-500 transition-all"
                                    :style="{ width: `${percentualCanceladas}%` }"
                                />
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ formatCurrency(props.valor_por_status?.Cancelado || 0) }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notas por Trimestre -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Notas Fiscais por Trimestre</CardTitle>
                        <CardDescription>Último ano</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.notas_por_trimestre && props.notas_por_trimestre.length > 0" class="space-y-4">
                            <div
                                v-for="item in props.notas_por_trimestre"
                                :key="item.trimestre_key"
                                class="space-y-2"
                            >
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ item.trimestre }}</span>
                                    <div class="flex items-center gap-4">
                                        <span class="text-muted-foreground">{{ item.quantidade }} notas</span>
                                        <span class="font-semibold">{{ formatCurrency(item.valor_total) }}</span>
                                    </div>
                                </div>
                                <div class="relative h-3 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all"
                                        :style="{ width: `${(item.valor_total / maxValorTrimestre) * 100}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                            Nenhum dado disponível
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Tomadores -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-4 w-4" />
                            Top Tomadores
                        </CardTitle>
                        <CardDescription>Por valor total</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.top_tomadores && props.top_tomadores.length > 0" class="space-y-4">
                            <div
                                v-for="(tomador, index) in props.top_tomadores"
                                :key="tomador.nome"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-xs font-bold"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ tomador.nome }}</p>
                                        <p class="text-xs text-muted-foreground">{{ tomador.quantidade }} notas</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold">{{ formatCurrency(tomador.valor_total) }}</p>
                                </div>
                </div>
            </div>
                        <div v-else class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                            Nenhum dado disponível
                        </div>
                    </CardContent>
                </Card>

                <!-- Notas Fiscais Recentes -->
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                Notas Fiscais Recentes
                            </CardTitle>
                            <CardDescription>Últimas 5 notas importadas</CardDescription>
                        </div>
                        <Link :href="notasFiscaisRoutes.index().url">
                            <Button variant="outline" size="sm">Ver todas</Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.notas_recentes && props.notas_recentes.length > 0" class="space-y-3">
                            <div
                                v-for="nota in props.notas_recentes"
                                :key="nota.id"
                                class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">
                                            NF {{ nota.numero_nota || nota.codigo_verificacao }}
                                        </p>
                                        <Badge
                                            :variant="getStatusBadgeVariant(nota.status_pagamento)"
                                            :class="getStatusBadgeClass(nota.status_pagamento)"
                                            class="text-xs"
                                        >
                                            {{ nota.status_pagamento }}
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ nota.nome_tomador_servico || '-' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ nota.data_emissao || 'Data não disponível' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="font-semibold">{{ formatCurrency(nota.valor_total) }}</p>
                                    </div>
                                    <Link :href="notasFiscaisRoutes.show({ notaFiscal: nota.id }).url">
                                        <Button variant="ghost" size="icon">
                                            <ArrowUpRight class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                            Nenhuma nota fiscal encontrada
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
