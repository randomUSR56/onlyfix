<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/images/brand/onlyfix-icon.svg"
        alt="OnlyFix"
        :class="cn(className)"
        v-bind="$attrs"
    />
</template>
