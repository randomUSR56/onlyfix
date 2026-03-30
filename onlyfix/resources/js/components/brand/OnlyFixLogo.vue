<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: HTMLAttributes['class'];
    iconOnly?: boolean;
    size?: 'sm' | 'md' | 'lg' | 'xl';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    iconOnly: false,
});

const sizeClasses = {
    sm: 'h-6',
    md: 'h-8',
    lg: 'h-10',
    xl: 'h-14',
};

const textSizeClasses = {
    sm: 'text-lg',
    md: 'text-xl',
    lg: 'text-2xl',
    xl: 'text-3xl',
};
</script>

<template>
    <div :class="cn('flex items-center gap-2', props.class)">
        <!-- OnlyFix Icon -->
        <img 
            src="/images/brand/onlyfix-icon.svg" 
            alt="OnlyFix"
            :class="cn(sizeClasses[size], 'aspect-square')"
        />
        
        <!-- Brand Text -->
        <span
            v-if="!iconOnly"
            :class="cn(
                textSizeClasses[size],
                'font-bold tracking-tight'
            )"
        >
            <span class="text-primary">Only</span><span class="text-accent">Fix</span>
        </span>
    </div>
</template>
