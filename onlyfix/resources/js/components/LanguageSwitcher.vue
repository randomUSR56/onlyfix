<script setup lang="ts">
import { computed } from 'vue';
import { availableLocales, setLocale, type SupportedLocale } from '@/i18n';
import { i18n } from '@/i18n';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Globe } from 'lucide-vue-next';

const currentLocale = computed<SupportedLocale>(() => (i18n.global.locale as any).value);

const localeFlags: Record<string, string> = {
    en: '\u{1F1EC}\u{1F1E7}',
    hu: '\u{1F1ED}\u{1F1FA}',
};

function changeLocale(locale: SupportedLocale) {
    setLocale(locale);
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
                    <span class="text-base">{{ localeFlags[locale.code] || locale.code }}</span>
                    <span>{{ locale.nativeName }}</span>
                </span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
