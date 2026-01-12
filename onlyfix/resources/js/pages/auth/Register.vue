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
        title="Create your account"
        description="Join OnlyFix and start managing your car repairs efficiently"
        hero-title="Get Started in Minutes"
        hero-subtitle="Whether you're a car owner seeking repairs or a mechanic managing work orders, OnlyFix streamlines the entire process."
    >
        <Head title="Register" />

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <!-- Name Field -->
            <div class="space-y-1.5">
                <Label for="name" class="text-sm font-medium">
                    Full name
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
                        placeholder="John Doe"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.name" />
            </div>

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
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="you@example.com"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password Field -->
            <div class="space-y-1.5">
                <Label for="password" class="text-sm font-medium">
                    Password
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
                        placeholder="••••••••"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Confirm Password Field -->
            <div class="space-y-1.5">
                <Label for="password_confirmation" class="text-sm font-medium">
                    Confirm password
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
                        placeholder="••••••••"
                        class="pl-10"
                    />
                </div>
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Terms Notice -->
            <p class="text-xs text-muted-foreground text-center">
                By creating an account, you agree to our
                <a href="#" class="text-primary hover:underline">Terms of Service</a>
                and
                <a href="#" class="text-primary hover:underline">Privacy Policy</a>
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
                    Create account
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
                        Already have an account?
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
                    Sign in instead
                    <ArrowRight class="h-3 w-3" />
                </TextLink>
            </div>
        </Form>
    </AuthBrandedLayout>
</template>
