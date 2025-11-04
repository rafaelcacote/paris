<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
import usersRoutes from '@/routes/users';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft } from 'lucide-vue-next';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: usersRoutes.index().url,
    },
    {
        title: 'Criar Usuário',
        href: usersRoutes.create().url,
    },
];

const formatCpf = (value: string) => {
    const cleaned = value.replace(/\D/g, '');
    if (cleaned.length <= 3) {
        return cleaned;
    }
    if (cleaned.length <= 6) {
        return cleaned.slice(0, 3) + '.' + cleaned.slice(3);
    }
    if (cleaned.length <= 9) {
        return cleaned.slice(0, 3) + '.' + cleaned.slice(3, 6) + '.' + cleaned.slice(6);
    }
    return cleaned.slice(0, 3) + '.' + cleaned.slice(3, 6) + '.' + cleaned.slice(6, 9) + '-' + cleaned.slice(9, 11);
};

const cpfValue = ref('');
const statusValue = ref(true);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Criar Usuário" />

        <div class="space-y-6 px-6 py-6">
            <div class="flex items-center gap-4">
                <Link :href="usersRoutes.index()">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Criar Usuário</h1>
                    <p class="text-sm text-muted-foreground">
                        Criar uma nova conta de usuário
                    </p>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <Form
                    v-bind="UserController.store.form()"
                    @submit="
                        (form) => {
                            form.transform((data) => ({
                                ...data,
                                cpf: cpfValue,
                                status: statusValue,
                            }));
                        }
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <!-- Linha 1: Nome, Email, CPF -->
                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="name">Nome Completo</Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                autofocus
                                placeholder="Nome completo"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                placeholder="email@exemplo.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="cpf">CPF</Label>
                            <Input
                                id="cpf"
                                name="cpf"
                                v-model="cpfValue"
                                @input="
                                    (e) => {
                                        const target = e.target as HTMLInputElement;
                                        cpfValue = formatCpf(target.value);
                                    }
                                "
                                required
                                placeholder="000.000.000-00"
                                maxlength="14"
                            />
                            <InputError :message="errors.cpf" />
                        </div>
                    </div>

                    <!-- Linha 2: Senha e Confirmar Senha -->
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="password">Senha</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                placeholder="Senha"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">
                                Confirmar Senha
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                placeholder="Confirmar senha"
                            />
                            <InputError
                                :message="errors.password_confirmation"
                            />
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center space-x-3 border-t pt-6">
                        <div class="flex items-center space-x-2">
                            <input
                                type="hidden"
                                name="status"
                                :value="statusValue ? '1' : '0'"
                            />
                            <Checkbox
                                id="status"
                                v-model:checked="statusValue"
                            />
                            <Label
                                for="status"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                            >
                                Usuário Ativo
                            </Label>
                        </div>
                        <InputError :message="errors.status" />
                    </div>

                    <div class="flex items-center justify-end gap-4 border-t pt-6">
                        <Link :href="usersRoutes.index()">
                            <Button
                                variant="secondary"
                                type="button"
                                class="bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancelar
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-md"
                        >
                            Criar Usuário
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
