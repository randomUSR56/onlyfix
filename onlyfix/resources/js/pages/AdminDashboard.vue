<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { type Ticket, type User } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useTicketHelpers } from '@/composables/useTicketHelpers';
import { useFormatting } from '@/composables/useFormatting';
import { dashboard } from '@/routes';
import * as usersRoutes from '@/routes/users';
import * as ticketsRoutes from '@/routes/tickets';
import {
    Users,
    ClipboardList,
    CheckCircle2,
    Clock,
    AlertCircle,
    ArrowRight,
    UserPlus,
    Plus
} from 'lucide-vue-next';

const { t } = useI18n();
const { getStatusBadgeVariant } = useTicketHelpers();
const { formatSimpleDate } = useFormatting();

const props = defineProps<{
    stats: {
        total_users: number;
        total_mechanics: number;
        total_tickets: number;
        open_tickets: number;
        in_progress_tickets: number;
        completed_tickets: number;
    };
    recentTickets: Ticket[];
    recentUsers: User[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('adminDashboard.pageTitle'),
        href: dashboard().url,
    },
];


</script>

<template>
    <Head :title="$t('adminDashboard.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ $t('adminDashboard.welcome') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ $t('adminDashboard.subtitle') }}</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="usersRoutes.create().url">
                        <Button variant="outline" size="sm">
                            <UserPlus class="mr-2 h-4 w-4" />
                            {{ $t('users.addUser') }}
                        </Button>
                    </Link>
                    <Link :href="ticketsRoutes.create().url">
                        <Button size="sm">
                            <Plus class="mr-2 h-4 w-4" />
                            {{ $t('tickets.createTicket') }}
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('adminDashboard.stats.totalUsers') }}</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                        <p class="text-xs text-muted-foreground">{{ stats.total_mechanics }} {{ $t('users.roles.mechanic') }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('dashboard.stats.totalTickets') }}</CardTitle>
                        <ClipboardList class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_tickets }}</div>
                        <p class="text-xs text-muted-foreground">{{ stats.open_tickets }} {{ $t('tickets.status.open').toLowerCase() }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('mechanicDashboard.stats.inProgressTickets') }}</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.in_progress_tickets }}</div>
                        <p class="text-xs text-muted-foreground">{{ $t('adminDashboard.activeWorkflows') }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('dashboard.stats.completedTickets') }}</CardTitle>
                        <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.completed_tickets }}</div>
                        <p class="text-xs text-muted-foreground">{{ $t('adminDashboard.completedRepairs') }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <!-- Recent Tickets -->
                <Card class="lg:col-span-4">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>{{ $t('dashboard.recentTickets.title') }}</CardTitle>
                            <CardDescription>{{ $t('dashboard.recentTickets.description') }}</CardDescription>
                        </div>
                        <Link :href="ticketsRoutes.index().url">
                            <Button variant="ghost" size="sm">
                                {{ $t('common.viewAll') }}
                                <ArrowRight class="ml-1 h-4 w-4" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="ticket in recentTickets" :key="ticket.id" class="flex items-center gap-4">
                                <div class="h-9 w-9 rounded-full bg-muted flex items-center justify-center">
                                    <AlertCircle v-if="ticket.status === 'open'" class="h-5 w-5 text-destructive" />
                                    <Clock v-else-if="ticket.status === 'in_progress'" class="h-5 w-5 text-primary" />
                                    <CheckCircle2 v-else class="h-5 w-5 text-green-500" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium leading-none truncate">
                                        {{ ticket.car?.make }} {{ ticket.car?.model }} - {{ ticket.user?.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground truncate">
                                        {{ ticket.description }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <Badge :variant="getStatusBadgeVariant(ticket.status)">
                                        {{ $t(`tickets.status.${ticket.status}`) }}
                                    </Badge>
                                    <p class="text-[10px] text-muted-foreground mt-1">{{ formatSimpleDate(ticket.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Users -->
                <Card class="lg:col-span-3">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>{{ $t('adminDashboard.recentUsers') }}</CardTitle>
                            <CardDescription>{{ $t('adminDashboard.recentUsersDescription') }}</CardDescription>
                        </div>
                        <Link :href="usersRoutes.index().url">
                            <Button variant="ghost" size="sm">
                                {{ $t('common.viewAll') }}
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="user in recentUsers" :key="user.id" class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                    {{ user.name.charAt(0) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium leading-none truncate">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ user.email }}</p>
                                </div>
                                <Badge variant="outline" class="text-[10px]">
                                    {{ typeof user.roles[0] === 'string' ? $t(`users.roles.${user.roles[0]}`) : $t('users.roles.user') }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
