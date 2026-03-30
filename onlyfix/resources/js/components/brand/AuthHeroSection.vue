<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
    title: string;
    subtitle?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div
        :class="cn(
            'relative flex items-center justify-center py-12 px-8 text-white',
            'before:absolute before:inset-0 before:bg-gradient-to-b before:from-transparent before:via-transparent before:to-slate-900/50',
            props.class
        )"
    >
        <div class="relative z-10 max-w-md text-center space-y-6">
            <!-- Icon -->
            <div class="flex justify-center">
                <div class="p-4 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20">
                    <img 
                        src="/images/brand/checkmark-circle.svg" 
                        :alt="$t('common.success')"
                        class="w-12 h-12 text-accent"
                    />
                </div>
            </div>
            
            <!-- Title -->
            <h2 class="text-2xl md:text-3xl font-bold tracking-tight">
                {{ title }}
            </h2>
            
            <!-- Subtitle -->
            <p v-if="subtitle" class="text-white/80 text-base md:text-lg leading-relaxed">
                {{ subtitle }}
            </p>
            
            <!-- Feature List -->
            <div class="pt-4 space-y-3">
                <slot name="features">
                    <div class="flex items-center gap-3 text-left">
                        <div class="flex-shrink-0 w-5 h-5 rounded-full bg-accent/20 flex items-center justify-center">
                            <img src="/images/brand/checkmark-small.svg" alt="" class="w-3 h-3" />
                        </div>
                        <span class="text-white/90 text-sm">{{ $t('features.quickTicket') }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-left">
                        <div class="flex-shrink-0 w-5 h-5 rounded-full bg-accent/20 flex items-center justify-center">
                            <img src="/images/brand/checkmark-small.svg" alt="" class="w-3 h-3" />
                        </div>
                        <span class="text-white/90 text-sm">{{ $t('features.realTimeUpdates') }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-left">
                        <div class="flex-shrink-0 w-5 h-5 rounded-full bg-accent/20 flex items-center justify-center">
                            <img src="/images/brand/checkmark-small.svg" alt="" class="w-3 h-3" />
                        </div>
                        <span class="text-white/90 text-sm">{{ $t('features.directCommunication') }}</span>
                    </div>
                </slot>
            </div>
        </div>
    </div>
</template>
