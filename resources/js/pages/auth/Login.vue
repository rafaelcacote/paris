<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { store } from '@/routes/login';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(store.url(), {
        onSuccess: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <AuthSplitLayout
        title="Entre na sua conta"
        description="Digite seu email e senha abaixo para entrar"
    >
        <Head title="Entrar" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Endereço de email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@exemplo.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Senha</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Senha"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="3"
                    :disabled="form.processing"
                    data-test="login-button"
                >
                    <Spinner v-if="form.processing" />
                    Entrar
                </Button>
            </div>
        </form>
    </AuthSplitLayout>
</template>
