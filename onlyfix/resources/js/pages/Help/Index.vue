<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as helpRoutes from '@/routes/help';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { marked } from 'marked';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    role: 'user' | 'mechanic' | 'admin';
}>();

const { t, locale } = useI18n();

// Pre-import all markdown help files as raw strings at build time
const helpFiles = import.meta.glob('../../help/**/*.md', { eager: true, query: '?raw', import: 'default' });

const renderedContent = computed(() => {
    const key = `../../help/${locale.value}/${props.role}.md`;
    const raw = (helpFiles[key] as string) ?? '';
    return marked.parse(raw);
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.help'), href: helpRoutes.index().url },
];
</script>

<template>
    <Head :title="$t('nav.help')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <div class="prose prose-neutral dark:prose-invert max-w-none" v-html="renderedContent" />
        </div>
    </AppLayout>
</template>
