<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import * as ticketsRoutes from '@/routes/tickets';
import { type BreadcrumbItem } from '@/types';
import type { Ticket } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Wrench, ArrowRight, AlertCircle, Clock, CheckCircle2, Users, Inbox } from 'lucide-vue-next';

const { t } = useI18n();

interface MechanicStats {
    available_tickets: number;
    my_tickets: number;
    in_progress_tickets: number;
    completed_tickets: number;
}

const props = defineProps<{
    stats: MechanicStats;
    availableTickets: Ticket[];
    myTickets: Ticket[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('mechanicDashboard.title'),
        href: dashboard().url,
    },
];

const getStatusBadgeVariant = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        open: 'destructive',
        assigned: 'secondary',
        in_progress: 'default',
        completed: 'outline',
        closed: 'outline',
    };
    return variants[status] || 'secondary';
};

const getPriorityBadgeClass = (priority: string) => {
    const classes: Record<string, string> = {
        urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        low: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    };
    return classes[priority] || 'bg-gray-100 text-gray-800';
};

const getStatusIcon = (status: string) => {
    if (status === 'open') return AlertCircle;
    if (status === 'assigned') return Clock;
    if (status === 'in_progress') return Wrench;
    return CheckCircle2;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
};

const acceptTicket = (ticketId: number) => {
    router.post(ticketsRoutes.accept({ ticket: ticketId }).url);
};
</script>

<template>
    <Head :title="$t('mechanicDashboard.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ $t('mechanicDashboard.welcome') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ $t('mechanicDashboard.subtitle') }}</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-lg bg-orange-100 dark:bg-orange-900/30">
                                <Inbox class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('mechanicDashboard.stats.availableTickets') }}</p>
                                <p class="text-2xl font-bold">{{ stats.available_tickets }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                <Clock class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('mechanicDashboard.stats.myTickets') }}</p>
                                <p class="text-2xl font-bold">{{ stats.my_tickets }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-lg bg-yellow-100 dark:bg-yellow-900/30">
                                <Wrench class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('mechanicDashboard.stats.inProgressTickets') }}</p>
                                <p class="text-2xl font-bold">{{ stats.in_progress_tickets }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/30">
                                <CheckCircle2 class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('mechanicDashboard.stats.completedTickets') }}</p>
                                <p class="text-2xl font-bold">{{ stats.completed_tickets }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Available Tickets -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <div>
                        <CardTitle class="text-lg">{{ $t('mechanicDashboard.availableTickets.title') }}</CardTitle>
                        <CardDescription>{{ $t('mechanicDashboard.availableTickets.description') }}</CardDescription>
                    </div>
                    <Link :href="ticketsRoutes.index().url">
                        <Button variant="ghost" size="sm">
                            {{ $t('common.viewAll') }}
                            <ArrowRight class="ml-1 h-4 w-4" />
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent>
                    <div v-if="availableTickets?.length" class="space-y-2">
                        <div
                            v-for="ticket in availableTickets"
                            :key="ticket.id"
                            class="flex items-center gap-4 p-3 rounded-lg border bg-card"
                        >
                            <!-- Status Icon -->
                            <div class="p-2 rounded-lg shrink-0 bg-orange-100 dark:bg-orange-900/30">
                                <AlertCircle class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                            </div>
                            
                            <!-- Ticket Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">
                                        {{ ticket.car?.make }} {{ ticket.car?.model }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ ticket.car?.license_plate }}
                                    </span>
                                </div>
                                <p class="text-sm text-muted-foreground line-clamp-1">
                                    {{ ticket.description }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <Users class="h-3 w-3 text-muted-foreground" />
                                    <span class="text-xs text-muted-foreground">
                                        {{ ticket.car?.user?.name }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Right side: Priority & Action -->
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-muted-foreground hidden sm:block">
                                    {{ formatDate(ticket.created_at) }}
                                </span>
                                <span :class="['text-xs px-2 py-0.5 rounded-full', getPriorityBadgeClass(ticket.priority)]">
                                    {{ $t(`tickets.priority.${ticket.priority}`) }}
                                </span>
                                <Button size="sm" @click="acceptTicket(ticket.id)">
                                    {{ $t('mechanicDashboard.acceptTicket') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="p-4 rounded-full bg-muted mb-4">
                            <Inbox class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <h3 class="font-medium mb-1">{{ $t('mechanicDashboard.availableTickets.empty') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $t('mechanicDashboard.availableTickets.emptyDescription') }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- My Active Tickets -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <div>
                        <CardTitle class="text-lg">{{ $t('mechanicDashboard.myTickets.title') }}</CardTitle>
                        <CardDescription>{{ $t('mechanicDashboard.myTickets.description') }}</CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="myTickets?.length" class="space-y-2">
                        <Link
                            v-for="ticket in myTickets"
                            :key="ticket.id"
                            :href="ticketsRoutes.show({ ticket: ticket.id }).url"
                            class="flex items-center gap-4 p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors group"
                        >
                            <!-- Status Icon -->
                            <div 
                                class="p-2 rounded-lg shrink-0"
                                :class="{
                                    'bg-blue-100 dark:bg-blue-900/30': ticket.status === 'assigned',
                                    'bg-yellow-100 dark:bg-yellow-900/30': ticket.status === 'in_progress',
                                }"
                            >
                                <component
                                    :is="getStatusIcon(ticket.status)"
                                    class="h-4 w-4"
                                    :class="{
                                        'text-blue-600 dark:text-blue-400': ticket.status === 'assigned',
                                        'text-yellow-600 dark:text-yellow-400': ticket.status === 'in_progress',
                                    }"
                                />
                            </div>
                            
                            <!-- Ticket Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium group-hover:text-primary transition-colors">
                                        {{ ticket.car?.make }} {{ ticket.car?.model }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ ticket.car?.license_plate }}
                                    </span>
                                </div>
                                <p class="text-sm text-muted-foreground line-clamp-1">
                                    {{ ticket.description }}
                                </p>
                                <div class="flex items-center gap-2 mt-1">
                                    <Users class="h-3 w-3 text-muted-foreground" />
                                    <span class="text-xs text-muted-foreground">
                                        {{ ticket.car?.user?.name }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Right side: Status & Priority -->
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-muted-foreground hidden sm:block">
                                    {{ formatDate(ticket.created_at) }}
                                </span>
                                <Badge :variant="getStatusBadgeVariant(ticket.status)" class="text-xs">
                                    {{ $t(`tickets.status.${ticket.status}`) }}
                                </Badge>
                                <span :class="['text-xs px-2 py-0.5 rounded-full hidden sm:block', getPriorityBadgeClass(ticket.priority)]">
                                    {{ $t(`tickets.priority.${ticket.priority}`) }}
                                </span>
                            </div>
                        </Link>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="p-4 rounded-full bg-muted mb-4">
                            <Wrench class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <h3 class="font-medium mb-1">{{ $t('mechanicDashboard.myTickets.empty') }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $t('mechanicDashboard.myTickets.emptyDescription') }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
