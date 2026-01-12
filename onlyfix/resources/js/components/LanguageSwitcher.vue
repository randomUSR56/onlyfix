<script setup lang="ts">
import { ref, computed } from 'vue';
import { availableLocales, setLocale, getCurrentLocale, type SupportedLocale } from '@/i18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Globe } from 'lucide-vue-next';

const currentLocale = ref<SupportedLocale>(getCurrentLocale());

const currentLocaleDisplay = computed(() => {
    const locale = availableLocales.find(l => l.code === currentLocale.value);
    return locale?.nativeName || 'English';
});

function changeLocale(locale: SupportedLocale) {
    setLocale(locale);
    currentLocale.value = locale;
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="h-9 w-9">
                <Globe class="h-4 w-4" />
                <span class="sr-only">{{ $t('language.select') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="locale in availableLocales"
                :key="locale.code"
                :class="{ 'bg-accent': currentLocale === locale.code }"
                @click="changeLocale(locale.code)"
            >
                <span class="flex items-center gap-2">
                    <span class="text-base">{{ locale.code === 'en' ? '🇬🇧' : '🇭🇺' }}</span>
                    <span>{{ locale.nativeName }}</span>
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
