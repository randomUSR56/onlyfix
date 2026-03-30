<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <img
        src="/images/brand/auth-background.svg"
        alt=""
        :class="cn('w-full h-full object-cover', props.class)"
    />
</template>
