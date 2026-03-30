<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, Globe, Check } from 'lucide-vue-next';
import { computed } from 'vue';
import { availableLocales, setLocale, type SupportedLocale } from '@/i18n';
import { i18n } from '@/i18n';

interface Props {
    user: User;
}

const currentLocale = computed<SupportedLocale>(() => (i18n.global.locale as any).value);

const localeFlags: Record<string, string> = {
    en: '\u{1F1EC}\u{1F1E7}',
    hu: '\u{1F1ED}\u{1F1FA}',
};

const handleLogout = () => {
    router.flushAll();
};

function changeLocale(locale: SupportedLocale) {
    setLocale(locale);
}

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="edit()" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                {{ $t('nav.settings') }}
            </Link>
        </DropdownMenuItem>
        <DropdownMenuSub>
            <DropdownMenuSubTrigger>
                <Globe class="mr-2 h-4 w-4" />
                {{ $t('language.title') }}
            </DropdownMenuSubTrigger>
            <DropdownMenuSubContent>
                <DropdownMenuItem
                    v-for="locale in availableLocales"
                    :key="locale.code"
                    @click="changeLocale(locale.code)"
                >
                    <span class="flex items-center gap-2 w-full">
                        <span class="text-base">{{ localeFlags[locale.code] || locale.code }}</span>
                        <span>{{ locale.nativeName }}</span>
                        <Check v-if="currentLocale === locale.code" class="ml-auto h-4 w-4" />
                    </span>
                </DropdownMenuItem>
            </DropdownMenuSubContent>
        </DropdownMenuSub>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ $t('nav.logout') }}
        </Link>
    </DropdownMenuItem>
</template>
