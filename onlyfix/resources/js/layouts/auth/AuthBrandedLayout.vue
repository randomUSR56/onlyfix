<script setup lang="ts">
import OnlyFixLogo from '@/components/brand/OnlyFixLogo.vue';
import AuthBackground from '@/components/brand/AuthBackground.vue';
import AuthHeroSection from '@/components/brand/AuthHeroSection.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

interface Props {
    title: string;
    description?: string;
    heroTitle?: string;
    heroSubtitle?: string;
}

withDefaults(defineProps<Props>(), {
    heroTitle: 'Professional Car Repair Management',
    heroSubtitle: 'Streamline your auto repair workflow with our comprehensive ticketing system. Connect customers with mechanics efficiently.',
});
</script>

<template>
    <div class="flex h-svh w-full overflow-hidden">
        <!-- Left Side - Hero Section (Hidden on mobile) -->
        <div class="relative hidden lg:flex lg:w-1/2 xl:w-[55%]">
            <!-- Background -->
            <AuthBackground class="absolute inset-0" />
            
            <!-- Content Overlay -->
            <AuthHeroSection
                :title="heroTitle"
                :subtitle="heroSubtitle"
                class="relative z-10 w-full"
            />
        </div>
        
        <!-- Right Side - Form -->
        <div class="flex h-full w-full flex-col lg:w-1/2 xl:w-[45%]">
            <!-- Header with Logo -->
            <header class="flex flex-shrink-0 items-center justify-between p-4 lg:p-6">
                <Link :href="home()" class="flex items-center">
                    <OnlyFixLogo size="md" />
                </Link>
                
                <!-- Mobile Menu / Theme Toggle placeholder -->
                <div class="flex items-center gap-2">
                    <slot name="header-actions" />
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex flex-1 flex-col items-center justify-center overflow-y-auto px-6 py-4 lg:px-12">
                <div class="w-full max-w-md space-y-6">
                    <!-- Page Title -->
                    <div class="space-y-1 text-center lg:text-left">
                        <h1 class="text-xl font-bold tracking-tight text-foreground lg:text-2xl">
                            {{ title }}
                        </h1>
                        <p v-if="description" class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    
                    <!-- Form Slot -->
                    <slot />
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="flex-shrink-0 border-t border-border p-4 text-center text-xs text-muted-foreground">
                <p>
                    © {{ new Date().getFullYear() }} OnlyFix. All rights reserved.
                </p>
            </footer>
        </div>
    </div>
</template>
