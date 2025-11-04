<script setup lang="ts">
import usersRoutes from '@/routes/users';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import UserController from '@/actions/App/Http/Controllers/UserController';
import { Button } from '@/components/ui/button';
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
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    cpf: string | null;
    status: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
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
        title: 'Detalhes do Usuário',
        href: usersRoutes.show({ user: props.user.id }).url,
    },
];

const deleteModal = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Detalhes do Usuário" />

        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="usersRoutes.index()">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Detalhes do Usuário</h1>
                    <p class="text-sm text-muted-foreground">
                        Visualizar informações do usuário
                    </p>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-6">
                <div class="space-y-6">
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Nome</Label>
                            <p class="text-sm">{{ props.user.name }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Email</Label>
                            <p class="text-sm">{{ props.user.email }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">CPF</Label>
                            <p class="text-sm">
                                {{
                                    props.user.cpf
                                        ? props.user.cpf.replace(
                                              /(\d{3})(\d{3})(\d{3})(\d{2})/,
                                              '$1.$2.$3-$4',
                                          )
                                        : '-'
                                }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm font-medium"
                                >Email Verificado</Label
                            >
                            <p class="text-sm">
                                {{
                                    props.user.email_verified_at
                                        ? 'Sim'
                                        : 'Não'
                                }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Status</Label>
                            <p class="text-sm">
                                <span
                                    v-if="props.user.status"
                                    class="text-green-600 font-medium"
                                >
                                    Ativo
                                </span>
                                <span
                                    v-else
                                    class="text-red-600 font-medium"
                                >
                                    Inativo
                                </span>
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Criado Em</Label>
                            <p class="text-sm">
                                {{
                                    new Date(
                                        props.user.created_at,
                                    ).toLocaleString('pt-BR')
                                }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-sm font-medium">Atualizado Em</Label>
                            <p class="text-sm">
                                {{
                                    new Date(
                                        props.user.updated_at,
                                    ).toLocaleString('pt-BR')
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 border-t pt-6">
                        <Link :href="usersRoutes.index()">
                            <Button variant="secondary">Voltar</Button>
                        </Link>
                        <Link
                            :href="
                                usersRoutes.edit({ user: props.user.id }).url
                            "
                        >
                            <Button>
                                <Pencil class="mr-2 h-4 w-4" />
                                Editar
                            </Button>
                        </Link>
                        <Dialog v-model:open="deleteModal">
                            <DialogTrigger as-child>
                                <Button variant="destructive">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Excluir
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        UserController.destroy.form({
                                            user: props.user.id,
                                        })
                                    "
                                    @success="
                                        () => {
                                            deleteModal = false;
                                            router.visit(
                                                usersRoutes.index().url,
                                            );
                                        }
                                    "
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                >
                                    <DialogHeader>
                                        <DialogTitle>Excluir Usuário</DialogTitle>
                                        <DialogDescription>
                                            Tem certeza que deseja excluir
                                            <strong>{{ props.user.name }}</strong
                                            >? Esta ação não pode ser desfeita.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <DialogFooter>
                                        <DialogClose as-child>
                                            <Button
                                                variant="secondary"
                                                @click="deleteModal = false"
                                            >
                                                Cancelar
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            Excluir
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

