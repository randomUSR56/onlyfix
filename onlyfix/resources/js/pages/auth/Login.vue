<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBrandedLayout from '@/layouts/auth/AuthBrandedLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle, Mail, Lock, ArrowRight } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <AuthBrandedLayout
        title="Welcome back"
        description="Sign in to your account to manage your repair tickets"
        hero-title="Your Trusted Auto Repair Partner"
        hero-subtitle="Submit repair requests, track progress, and communicate with certified mechanics - all in one place."
    >
        <Head title="Log in" />

        <!-- Success Status Message -->
        <div
            v-if="status"
            class="rounded-lg bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-900 p-3 text-center text-sm font-medium text-green-700 dark:text-green-400"
        >
            <div class="flex items-center justify-center gap-2">
                <img src="/images/brand/success-circle.svg" alt="" class="h-4 w-4" />
                {{ status }}
            </div>
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <!-- Email Field -->
            <div class="space-y-1.5">
                <Label for="email" class="text-sm font-medium">
                    Email address
                </Label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <Mail class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="you@example.com"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password Field -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-sm font-medium">
                        Password
                    </Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-medium text-primary hover:text-primary/80 transition-colors"
                        :tabindex="5"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <Lock class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center gap-3 cursor-pointer">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span class="text-sm text-muted-foreground">Keep me signed in</span>
                </Label>
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="w-full h-10 text-sm font-semibold bg-primary hover:bg-primary/90 transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/30"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <LoaderCircle
                    v-if="processing"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                <template v-else>
                    Sign in
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
                        New to OnlyFix?
                    </span>
                </div>
            </div>

            <!-- Register Link -->
            <div class="text-center">
                <TextLink
                    :href="register()"
                    class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:text-primary/80 transition-colors"
                    :tabindex="6"
                >
                    Create an account
                    <ArrowRight class="h-3 w-3" />
                </TextLink>
            </div>
        </Form>
    </AuthBrandedLayout>
</template>
