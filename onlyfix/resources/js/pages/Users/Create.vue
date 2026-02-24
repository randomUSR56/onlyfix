<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('users.pageTitle'),
        href: '/users',
    },
    {
        title: t('users.addUser'),
        href: '/users/create',
    },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const submit = () => {
    form.post('/users', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="$t('users.create.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6 max-w-2xl mx-auto w-full">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ $t('users.create.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ $t('users.subtitle') }}</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('users.form.name') }}</CardTitle>
                    <CardDescription>{{ $t('users.create.title') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">{{ $t('users.form.name') }}</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                :placeholder="$t('auth.register.namePlaceholder')"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">{{ $t('users.form.email') }}</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                :placeholder="$t('auth.register.emailPlaceholder')"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="role">{{ $t('users.role') }}</Label>
                            <select
                                id="role"
                                v-model="form.role"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                required
                            >
                                <option value="user">{{ $t('users.roles.user') }}</option>
                                <option value="mechanic">{{ $t('users.roles.mechanic') }}</option>
                                <option value="admin">{{ $t('users.roles.admin') }}</option>
                            </select>
                            <InputError :message="form.errors.role" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="password">{{ $t('users.form.password') }}</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="••••••••"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="space-y-2">
                                <Label for="password_confirmation">{{ $t('users.form.passwordConfirmation') }}</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    placeholder="••••••••"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link href="/users">
                                <Button variant="ghost" type="button">
                                    {{ $t('common.cancel') }}
                                </Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">
                                {{ $t('users.create.submitButton') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
