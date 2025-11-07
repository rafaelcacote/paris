<script setup lang="ts">
import UserController from '@/actions/App/Http/Controllers/UserController';
import usersRoutes from '@/routes/users';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { ref, watch, computed } from 'vue';
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
import { Label } from '@/components/ui/label';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import {
    Eye,
    KeyRound,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
    CheckCircle2,
    XCircle,
} from 'lucide-vue-next';

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
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        path: string;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters?: {
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: usersRoutes.index().url,
    },
];

const page = usePage();

// Get authenticated user
const authUser = computed(() => {
    const user = (page.props as any).auth?.user;
    return user ? {
        id: user.id,
        name: user.name,
        email: user.email,
    } : undefined;
});

// Check if a user is admin (Administrador or admin@admin.com)
const isAdmin = (user: User | { name: string; email: string } | undefined): boolean => {
    if (!user) return false;
    return user.name === 'Administrador' || user.email === 'admin@admin.com';
};

// Check if delete button should be disabled for a user
const canDeleteUser = (user: User): boolean => {
    // Only admin users can disable delete button for admin users
    if (isAdmin(authUser.value) && isAdmin(user)) {
        return false;
    }
    return true;
};

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
const passwordModal = ref(false);
const selectedUser = ref<User | null>(null);

// Search filter
const searchQuery = ref(props.filters?.search || '');

const performSearch = () => {
    router.get(
        usersRoutes.index().url,
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearSearch = () => {
    searchQuery.value = '';
    router.get(
        usersRoutes.index().url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

type PaginationEntry =
    | {
          type: 'page';
          value: number;
      }
    | {
          type: 'ellipsis';
          key: string;
      };

const paginationItems = computed<PaginationEntry[]>(() => {
    const totalPages = props.users.last_page;
    const currentPage = props.users.current_page;

    if (totalPages <= 1) {
        return [];
    }

    const pageSet = new Set<number>();
    pageSet.add(1);
    pageSet.add(totalPages);

    for (let pageNumber = currentPage - 1; pageNumber <= currentPage + 1; pageNumber += 1) {
        if (pageNumber > 1 && pageNumber < totalPages) {
            pageSet.add(pageNumber);
        }
    }

    const orderedPages = Array.from(pageSet).sort((a, b) => a - b);

    const items: PaginationEntry[] = [];
    let previousPage: number | null = null;

    for (const pageNumber of orderedPages) {
        if (previousPage !== null && pageNumber - previousPage > 1) {
            items.push({
                type: 'ellipsis',
                key: `ellipsis-${pageNumber}`,
            });
        }

        items.push({
            type: 'page',
            value: pageNumber,
        });

        previousPage = pageNumber;
    }

    return items;
});

const canGoToPreviousPage = computed(() => props.users.current_page > 1);
const canGoToNextPage = computed(
    () => props.users.current_page < props.users.last_page,
);

const changePage = (page: number) => {
    if (page < 1 || page > props.users.last_page || page === props.users.current_page) {
        return;
    }

    router.get(
        usersRoutes.index().url,
        {
            page,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const openDeleteModal = (user: User) => {
    if (!canDeleteUser(user)) {
        return;
    }
    selectedUser.value = user;
    deleteModal.value = true;
};

const closeDeleteModal = () => {
    deleteModal.value = false;
    selectedUser.value = null;
};

const openPasswordModal = (user: User) => {
    selectedUser.value = user;
    passwordModal.value = true;
};

const closePasswordModal = () => {
    passwordModal.value = false;
    selectedUser.value = null;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Usuários" />

        <div class="space-y-6 px-6 py-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Usuários</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Gerencie e pesquise seus usuários
                    </p>
                </div>
                <Link :href="usersRoutes.create().url">
                    <Button
                        class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-lg transition-all duration-200"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Criar Usuário
                    </Button>
                </Link>
            </div>

            <!-- Search Filter Card -->
            <div class="rounded-xl border bg-card p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label
                            for="search"
                            class="mb-2 block text-sm font-medium"
                        >
                            Pesquisar Usuários
                        </label>
                        <div class="relative">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Pesquisar por nome ou email..."
                                class="pl-10 pr-10"
                                @keyup.enter="performSearch"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            @click="performSearch"
                            class="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 shadow-md"
                        >
                            <Search class="mr-2 h-4 w-4" />
                            Pesquisar
                        </Button>
                        <Button
                            v-if="props.filters?.search"
                            @click="clearSearch"
                            variant="outline"
                            class="border-orange-300 text-orange-700 hover:bg-orange-50 dark:border-orange-800 dark:text-orange-400 dark:hover:bg-orange-950"
                        >
                            <X class="mr-2 h-4 w-4" />
                            Limpar
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Users Table Card -->
            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-foreground"
                                >
                                    Nome
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-foreground"
                                >
                                    Email
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-foreground"
                                >
                                    CPF
                                </th>
                                
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-foreground"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-sm font-semibold text-foreground"
                                >
                                    Criado Em
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-sm font-semibold text-foreground"
                                >
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="user in props.users.data"
                                :key="user.id"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium">{{ user.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-muted-foreground">
                                        {{ user.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-muted-foreground">
                                        {{
                                            user.cpf
                                                ? user.cpf.replace(
                                                      /(\d{3})(\d{3})(\d{3})(\d{2})/,
                                                      '$1.$2.$3-$4',
                                                  )
                                                : '-'
                                        }}
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <Badge
                                        v-if="user.status"
                                        variant="default"
                                        class="bg-blue-100 text-blue-800 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400"
                                    >
                                        <CheckCircle2 class="mr-1 h-3 w-3" />
                                        Ativo
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="secondary"
                                        class="bg-red-100 text-red-800 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400"
                                    >
                                        <XCircle class="mr-1 h-3 w-3" />
                                        Inativo
                                    </Badge>
                                </td>
                                <td class="px-6 py-4 text-sm text-muted-foreground">
                                    {{
                                        new Date(
                                            user.created_at,
                                        ).toLocaleDateString('pt-BR', {
                                            day: '2-digit',
                                            month: '2-digit',
                                            year: 'numeric',
                                        })
                                    }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="
                                                usersRoutes.show({
                                                    user: user.id,
                                                }).url
                                            "
                                        >
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                class="border-blue-300 text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-blue-950"
                                                title="Visualizar"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link
                                            :href="
                                                usersRoutes.edit({
                                                    user: user.id,
                                                }).url
                                            "
                                        >
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-400 dark:hover:bg-amber-950"
                                                title="Editar"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            @click="openPasswordModal(user)"
                                            variant="outline"
                                            size="icon"
                                            class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-400 dark:hover:bg-amber-950"
                                            title="Alterar Senha"
                                        >
                                            <KeyRound class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            @click="openDeleteModal(user)"
                                            variant="outline"
                                            size="icon"
                                            :disabled="!canDeleteUser(user)"
                                            class="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950 disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Excluir"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.users.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-6 py-12 text-center"
                                >
                                    <div class="flex flex-col items-center gap-2">
                                        <p class="text-sm font-medium text-muted-foreground">
                                            Nenhum usuário encontrado
                                        </p>
                                        <p
                                            v-if="props.filters?.search"
                                            class="text-xs text-muted-foreground"
                                        >
                                            Tente ajustar os critérios de pesquisa
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="props.users.total > 0"
                class="flex flex-col gap-4 rounded-lg border bg-card px-6 py-4 md:flex-row md:items-center md:justify-between"
            >
                <Pagination v-if="props.users.last_page > 1">
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious
                                type="button"
                                :disabled="!canGoToPreviousPage"
                                @click="changePage(props.users.current_page - 1)"
                            />
                        </PaginationItem>

                        <template v-for="item in paginationItems" :key="item.type === 'page' ? `page-${item.value}` : item.key">
                            <PaginationItem v-if="item.type === 'page'">
                                <PaginationLink
                                    type="button"
                                    :active="item.value === props.users.current_page"
                                    @click="changePage(item.value)"
                                >
                                    {{ item.value }}
                                </PaginationLink>
                            </PaginationItem>
                            <PaginationItem v-else>
                                <PaginationEllipsis />
                            </PaginationItem>
                        </template>

                        <PaginationItem>
                            <PaginationNext
                                type="button"
                                :disabled="!canGoToNextPage"
                                @click="changePage(props.users.current_page + 1)"
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>

                <p class="text-sm text-muted-foreground md:text-right">
                    Mostrando
                    <span class="font-medium text-foreground">
                        {{ props.users.from ?? 0 }}
                    </span>
                    até
                    <span class="font-medium text-foreground">
                        {{ props.users.to ?? 0 }}
                    </span>
                    de
                    <span class="font-medium text-foreground">
                        {{ props.users.total }}
                    </span>
                    resultados
                </p>
            </div>

            <!-- Password Modal -->
            <Dialog v-model:open="passwordModal">
                <DialogContent>
                    <Form
                        v-if="selectedUser"
                        v-bind="
                            UserController.updatePassword.form({
                                user: selectedUser.id,
                            })
                        "
                        @success="
                            () => {
                                closePasswordModal();
                                router.reload({ only: ['users'] });
                            }
                        "
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader>
                            <DialogTitle>Alterar Senha</DialogTitle>
                            <DialogDescription>
                                Digite a nova senha para o usuário
                                <strong>{{ selectedUser.name }}</strong>
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-4">
                            <div class="grid gap-2">
                                <Label for="modal_password">Senha</Label>
                                <Input
                                    id="modal_password"
                                    type="password"
                                    name="password"
                                    required
                                    placeholder="Nova senha"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="modal_password_confirmation">
                                    Confirmar Senha
                                </Label>
                                <Input
                                    id="modal_password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    required
                                    placeholder="Confirmar nova senha"
                                />
                                <InputError
                                    :message="errors.password_confirmation"
                                />
                            </div>
                        </div>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="closePasswordModal"
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

            <!-- Delete Modal -->
            <Dialog v-model:open="deleteModal">
                <DialogContent>
                    <Form
                        v-if="selectedUser"
                        v-bind="
                            UserController.destroy.form({
                                user: selectedUser.id,
                            })
                        "
                        @success="
                            () => {
                                closeDeleteModal();
                                router.reload({ only: ['users'] });
                            }
                        "
                        class="space-y-6"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader>
                            <DialogTitle>Excluir Usuário</DialogTitle>
                            <DialogDescription>
                                Tem certeza que deseja excluir
                                <strong>{{ selectedUser.name }}</strong>? Esta
                                ação não pode ser desfeita.
                            </DialogDescription>
                        </DialogHeader>

                        <DialogFooter>
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="closeDeleteModal"
                                >
                                    Cancelar
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                class="bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800"
                            >
                                Excluir
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
