<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as helpRoutes from '@/routes/help';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { marked } from 'marked';
import { ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    role: 'user' | 'mechanic' | 'admin';
}>();

const { t, locale } = useI18n();

// Pre-import all markdown help files as raw strings at build time
const helpFiles = import.meta.glob('../../help/**/*.md', { eager: true, query: '?raw', import: 'default' });

const renderedContent = ref('');

watchEffect(async () => {
    const key = `../../help/${locale.value}/${props.role}.md`;
    const raw = (helpFiles[key] as string) ?? '';
    const result = marked.parse(raw);
    renderedContent.value = result instanceof Promise ? await result : result;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('nav.help'), href: helpRoutes.index().url },
];
</script>

<template>
    <Head :title="$t('nav.help')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <div class="help-content max-w-none" v-html="renderedContent" />
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

.help-content :deep(h1) {
    @apply text-3xl font-bold mb-4 mt-6 text-foreground;
}
.help-content :deep(h2) {
    @apply text-2xl font-semibold mb-3 mt-6 pb-2 border-b border-border text-foreground;
}
.help-content :deep(h3) {
    @apply text-lg font-semibold mb-2 mt-4 text-foreground;
}
.help-content :deep(p) {
    @apply mb-4 leading-7;
}
.help-content :deep(ul) {
    @apply list-disc list-outside ml-6 mb-4 space-y-1;
}
.help-content :deep(ol) {
    @apply list-decimal list-outside ml-6 mb-4 space-y-1;
}
.help-content :deep(li) {
    @apply leading-7;
}
.help-content :deep(strong) {
    @apply font-semibold;
}
.help-content :deep(em) {
    @apply italic;
}
.help-content :deep(code) {
    @apply bg-muted text-foreground px-1.5 py-0.5 rounded text-sm font-mono;
}
.help-content :deep(pre) {
    @apply bg-muted p-4 rounded-lg mb-4 overflow-x-auto;
}
.help-content :deep(pre code) {
    @apply bg-transparent p-0;
}
.help-content :deep(blockquote) {
    @apply border-l-4 border-primary pl-4 italic text-muted-foreground my-4;
}
.help-content :deep(hr) {
    @apply border-border my-6;
}
.help-content :deep(a) {
    @apply text-primary underline hover:opacity-80;
}
.help-content :deep(table) {
    @apply w-full border-collapse mb-4;
}
.help-content :deep(th) {
    @apply border border-border px-3 py-2 bg-muted font-semibold text-left;
}
.help-content :deep(td) {
    @apply border border-border px-3 py-2;
}
</style>
