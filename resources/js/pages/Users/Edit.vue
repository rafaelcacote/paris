<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
import usersRoutes from '@/routes/users';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, KeyRound } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    cpf: string | null;
    status: boolean;
    email_verified_at: string | null;
    created_at: string;
}

interface Props {
    user: User;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: usersRoutes.index().url,
    },
    {
        title: 'Editar Usuário',
        href: usersRoutes.edit({ user: props.user.id }).url,
    },
];

const passwordModal = ref(false);
const cpfValue = ref(
    props.user.cpf
        ? props.user.cpf.replace(
              /(\d{3})(\d{3})(\d{3})(\d{2})/,
              '$1.$2.$3-$4',
          )
        : '',
);
const statusValue = ref(props.user.status ?? true);

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

watch(cpfValue, (newValue) => {
    if (newValue) {
        cpfValue.value = formatCpf(newValue);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar Usuário" />

        <div class="space-y-6 px-6 py-6">
            <div class="flex items-center gap-4">
                <Link :href="usersRoutes.index()">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Editar Usuário</h1>
                    <p class="text-sm text-muted-foreground">
                        Atualizar informações do usuário
                    </p>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <Form
                    v-bind="
                        UserController.update.form({ user: props.user.id })
                    "
                    @submit="
                        (form) => {
                            form.transform((data) => ({
                                ...data,
                                cpf: cpfValue,
                                status: statusValue,
                            }));
                        }
                    "
                    @success="
                        () => {
                            router.reload({ only: ['user'] });
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
                                :default-value="props.user.name"
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
                                :default-value="props.user.email"
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

                    <!-- Status -->
                    <div class="flex items-center space-x-3 border-t pt-6">
                        <div class="flex items-center space-x-2">
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
                        <Dialog v-model:open="passwordModal">
                            <DialogTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-400 dark:hover:bg-amber-950"
                                >
                                    <KeyRound class="mr-2 h-4 w-4" />
                                    Alterar Senha
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        UserController.updatePassword.form({
                                            user: props.user.id,
                                        })
                                    "
                                    @success="
                                        () => {
                                            passwordModal = false;
                                            router.reload({ only: ['user'] });
                                        }
                                    "
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                >
                                    <DialogHeader>
                                        <DialogTitle>Alterar Senha</DialogTitle>
                                        <DialogDescription>
                                            Digite a nova senha para o usuário
                                            <strong>{{ props.user.name }}</strong>
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-4">
                                        <div class="grid gap-2">
                                            <Label for="password">Senha</Label>
                                            <Input
                                                id="password"
                                                type="password"
                                                name="password"
                                                required
                                                placeholder="Nova senha"
                                            />
                                            <InputError
                                                :message="errors.password"
                                            />
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
                                                placeholder="Confirmar nova senha"
                                            />
                                            <InputError
                                                :message="
                                                    errors.password_confirmation
                                                "
                                            />
                                        </div>
                                    </div>

                                    <DialogFooter>
                                        <DialogClose as-child>
                                            <Button
                                                variant="secondary"
                                                @click="passwordModal = false"
                                            >
                                                Cancelar
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            :disabled="processing"
                                            class="bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800"
                                        >
                                            Alterar Senha
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-md"
                        >
                            Atualizar Usuário
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
