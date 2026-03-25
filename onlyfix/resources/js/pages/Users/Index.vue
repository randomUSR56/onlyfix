<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';
import { type User, type PaginatedData } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useTicketHelpers } from '@/composables/useTicketHelpers';
import { useFormatting } from '@/composables/useFormatting';
import { ref, watch, onUnmounted } from 'vue';
import * as usersRoutes from '@/routes/users';
import { UserPlus, Search, User as UserIcon, MoreHorizontal, Edit, Trash2 } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const { t } = useI18n();
const { getRoleBadgeVariant } = useTicketHelpers();
const { decodePaginationLabel } = useFormatting();

const props = defineProps<{
    users: PaginatedData<User>;
    filters: {
        search?: string;
        role?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('users.pageTitle'),
        href: usersRoutes.index().url,
    },
];

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');

const getRoleName = (role: string | { name: string }) => {
    return typeof role === 'string' ? role : role.name;
};

const updateFilters = () => {
    router.get(usersRoutes.index().url, {
        search: search.value,
        role: roleFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch([search, roleFilter], () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        updateFilters();
    }, 300);
});

onUnmounted(() => {
    if (searchTimer) clearTimeout(searchTimer);
});

const deleteUser = (user: User) => {
    if (confirm(t('users.delete.description', { name: user.name }))) {
        router.delete(usersRoutes.destroy({ user: user.id }).url);
    }
};
</script>

<template>
    <Head :title="$t('users.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ $t('users.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ $t('users.subtitle') }}</p>
                </div>
                <Link :href="usersRoutes.create().url">
                    <Button>
                        <UserPlus class="mr-2 h-4 w-4" />
                        {{ $t('users.addUser') }}
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader class="pb-3">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="relative flex-1">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                type="search"
                                :placeholder="$t('users.searchPlaceholder')"
                                class="pl-8"
                            />
                        </div>
                        <select
                            v-model="roleFilter"
                            class="flex h-9 w-full sm:w-[180px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">{{ $t('common.all') }}</option>
                            <option value="admin">{{ $t('users.roles.admin') }}</option>
                            <option value="mechanic">{{ $t('users.roles.mechanic') }}</option>
                            <option value="user">{{ $t('users.roles.user') }}</option>
                        </select>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="users.data.length" class="space-y-2">
                        <!-- Desktop Table-like view -->
                        <div class="hidden md:block">
                            <div class="grid grid-cols-12 gap-4 px-4 py-2 text-xs font-medium text-muted-foreground border-b mb-2">
                                <div class="col-span-5">{{ $t('users.form.name') }} / {{ $t('users.form.email') }}</div>
                                <div class="col-span-3">{{ $t('users.role') }}</div>
                                <div class="col-span-3">{{ $t('adminDashboard.completedRepairs') }} / {{ $t('nav.myCars') }}</div>
                                <div class="col-span-1 text-right"></div>
                            </div>
                            <div v-for="user in users.data" :key="user.id" class="grid grid-cols-12 gap-4 items-center px-4 py-3 hover:bg-muted/50 rounded-lg transition-colors border-b last:border-0">
                                <div class="col-span-5 flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-medium">
                                        {{ user.name.charAt(0) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-medium truncate">{{ user.name }}</span>
                                        <span class="text-xs text-muted-foreground truncate">{{ user.email }}</span>
                                    </div>
                                </div>
                                <div class="col-span-3">
                                    <div class="flex gap-1 flex-wrap">
                                        <Badge 
                                            v-for="role in user.roles" 
                                            :key="typeof role === 'string' ? role : (role as any).name" 
                                            :variant="getRoleBadgeVariant(typeof role === 'string' ? role : (role as any).name)"
                                            class="text-[10px] px-1.5 py-0"
                                        >
                                            {{ typeof role === 'string' ? $t(`users.roles.${role}`) : $t(`users.roles.${(role as any).name}`) }}
                                        </Badge>
                                    </div>
                                </div>
                                <div class="col-span-3 text-sm text-muted-foreground">
                                    <span v-if="user.roles.includes('mechanic')">
                                        {{ $t('users.ticketsCount', { count: user.tickets_count || 0 }) }}
                                    </span>
                                    <span v-else>
                                        {{ $t('users.carsCount', { count: user.cars_count || 0 }) }}
                                    </span>
                                </div>
                                <div class="col-span-1 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8" :aria-label="$t('common.actions')">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="router.get(usersRoutes.show({ user: user.id }).url)">
                                                <UserIcon class="mr-2 h-4 w-4" />
                                                {{ $t('common.view') }}
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="router.get(usersRoutes.edit({ user: user.id }).url)">
                                                <Edit class="mr-2 h-4 w-4" />
                                                {{ $t('common.edit') }}
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="$page.props.auth.user.id !== user.id" @click="deleteUser(user)" class="text-destructive">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                {{ $t('common.delete') }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile List view -->
                        <div class="md:hidden space-y-3">
                            <div v-for="user in users.data" :key="user.id" class="p-4 border rounded-lg space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold">{{ user.name }}</span>
                                            <span class="text-xs text-muted-foreground">{{ user.email }}</span>
                                        </div>
                                    </div>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" :aria-label="$t('common.actions')">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="router.get(usersRoutes.show({ user: user.id }).url)">
                                                <UserIcon class="mr-2 h-4 w-4" />
                                                {{ $t('common.view') }}
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="router.get(usersRoutes.edit({ user: user.id }).url)">
                                                <Edit class="mr-2 h-4 w-4" />
                                                {{ $t('common.edit') }}
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-if="$page.props.auth.user.id !== user.id" @click="deleteUser(user)" class="text-destructive">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                {{ $t('common.delete') }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t text-sm">
                                    <div class="flex gap-1">
                                        <Badge
                                            v-for="role in user.roles"
                                            :key="getRoleName(role)"
                                            :variant="getRoleBadgeVariant(getRoleName(role))"
                                            class="text-[10px]"
                                        >
                                            {{ $t(`users.roles.${getRoleName(role)}`) }}
                                        </Badge>
                                    </div>
                                    <span class="text-muted-foreground">
                                        {{ user.roles.includes('mechanic') 
                                            ? $t('users.ticketsCount', { count: user.tickets_count || 0 }) 
                                            : $t('users.carsCount', { count: user.cars_count || 0 }) 
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="users.last_page > 1" class="flex items-center justify-center gap-2 mt-6">
                            <Button
                                v-for="link in users.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :disabled="!link.url"
                                @click="link.url && router.get(link.url)"
                            >
                                {{ decodePaginationLabel(link.label) }}
                            </Button>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="p-4 rounded-full bg-muted mb-4">
                            <UserIcon class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <h3 class="font-medium mb-1">{{ $t('users.empty') }}</h3>
                        <Button variant="outline" @click="search = ''; roleFilter = ''">
                            {{ $t('common.clearFilters') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
