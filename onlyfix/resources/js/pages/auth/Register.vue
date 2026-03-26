<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBrandedLayout from '@/layouts/auth/AuthBrandedLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, User, Mail, Lock, ShieldCheck, ArrowRight } from 'lucide-vue-next';
</script>

<template>
    <AuthBrandedLayout
        :title="$t('auth.register.title')"
        :description="$t('auth.register.description')"
        :hero-title="$t('auth.register.heroTitle')"
        :hero-subtitle="$t('auth.register.heroSubtitle')"
    >
        <Head :title="$t('auth.register.pageTitle')" />

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <!-- Name Field -->
            <div class="space-y-1.5">
                <Label for="name" class="text-sm font-medium">
                    {{ $t('auth.register.nameLabel') }}
                </Label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <User class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        :placeholder="$t('auth.register.namePlaceholder')"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.name" />
            </div>

            <!-- Email Field -->
            <div class="space-y-1.5">
                <Label for="email" class="text-sm font-medium">
                    {{ $t('auth.register.emailLabel') }}
                </Label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <Mail class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        :placeholder="$t('auth.register.emailPlaceholder')"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password Field -->
            <div class="space-y-1.5">
                <Label for="password" class="text-sm font-medium">
                    {{ $t('auth.register.passwordLabel') }}
                </Label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <Lock class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        :placeholder="$t('auth.register.passwordPlaceholder')"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Confirm Password Field -->
            <div class="space-y-1.5">
                <Label for="password_confirmation" class="text-sm font-medium">
                    {{ $t('auth.register.confirmPasswordLabel') }}
                </Label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        :placeholder="$t('auth.register.passwordPlaceholder')"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Terms Notice -->
            <p class="text-xs text-muted-foreground text-center">
                {{ $t('auth.register.termsNotice') }}
                <a href="#" class="text-primary hover:underline" @click.prevent>{{ $t('auth.register.termsOfService') }}</a>
                {{ $t('common.and') }}
                <a href="#" class="text-primary hover:underline" @click.prevent>{{ $t('auth.register.privacyPolicy') }}</a>
            </p>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="w-full h-10 text-sm font-semibold bg-primary hover:bg-primary/90 transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/30"
                :tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <LoaderCircle
                    v-if="processing"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                <template v-else>
                    {{ $t('auth.register.submitButton') }}
                    <ArrowRight class="ml-2 h-4 w-4" />
                </template>
            </Button>

            <!-- Divider -->
            <div class="relative py-1">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-border"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground">
                        {{ $t('auth.register.alreadyHaveAccount') }}
                    </span>
                </div>
            </div>

            <!-- Login Link -->
            <div class="text-center">
                <TextLink
                    :href="login()"
                    class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:text-primary/80 transition-colors"
                    :tabindex="6"
                >
                    {{ $t('auth.register.signInInstead') }}
                    <ArrowRight class="h-3 w-3" />
                </TextLink>
            </div>
        </Form>
    </AuthBrandedLayout>
</template>
