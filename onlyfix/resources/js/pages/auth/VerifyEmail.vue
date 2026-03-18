<script setup lang="ts">
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        :title="$t('auth.verifyEmail.title')"
        :description="$t('auth.verifyEmail.description')"
    >
        <Head :title="$t('auth.verifyEmail.pageTitle')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ $t('auth.verifyEmail.resendSuccess') }}
        </div>

        <Form
            v-bind="EmailVerificationNotificationController.store.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                {{ $t('auth.verifyEmail.resendButton') }}
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                {{ $t('auth.verifyEmail.logout') }}
            </TextLink>
        </Form>
    </AuthLayout>
</template>
