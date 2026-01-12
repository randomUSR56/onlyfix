<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import * as carsRoutes from '@/routes/cars';
import * as ticketsRoutes from '@/routes/tickets';
import { type BreadcrumbItem } from '@/types';
import type { DashboardStats, Ticket, Car } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Car as CarIcon, Ticket as TicketIcon, Clock, CheckCircle2, Plus, ArrowRight, AlertCircle, Wrench } from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps<{
    stats: DashboardStats;
    recentTickets: Ticket[];
    cars: (Car & { tickets_count?: number })[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head :title="$t('dashboard.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Welcome Section -->
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight">{{ $t('dashboard.welcome') }}</h1>
                <p class="text-muted-foreground">{{ $t('dashboard.subtitle') }}</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Cars -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('dashboard.stats.totalCars') }}</CardTitle>
                        <CarIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.total_cars ?? 0 }}</div>
                        <p class="text-xs text-muted-foreground">{{ $t('dashboard.stats.registeredVehicles') }}</p>
                    </CardContent>
                </Card>

                <!-- Total Tickets -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('dashboard.stats.totalTickets') }}</CardTitle>
                        <TicketIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.total_tickets ?? 0 }}</div>
                        <p class="text-xs text-muted-foreground">{{ $t('dashboard.stats.serviceRequests') }}</p>
                    </CardContent>
                </Card>

                <!-- Open Tickets -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('dashboard.stats.openTickets') }}</CardTitle>
                        <Clock class="h-4 w-4 text-orange-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats?.open_tickets ?? 0 }}</div>
                        <p class="text-xs text-muted-foreground">{{ $t('dashboard.stats.awaitingService') }}</p>
                    </CardContent>
                </Card>

                <!-- Completed Tickets -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ $t('dashboard.stats.completedTickets') }}</CardTitle>
                        <CheckCircle2 class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats?.completed_tickets ?? 0 }}</div>
                        <p class="text-xs text-muted-foreground">{{ $t('dashboard.stats.successfulRepairs') }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card class="border-dashed border-2 hover:border-primary/50 transition-colors cursor-pointer">
                    <Link :href="ticketsRoutes.create().url" class="block">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <div class="p-2 rounded-lg bg-primary/10">
                                    <Plus class="h-5 w-5 text-primary" />
                                </div>
                                {{ $t('dashboard.quickActions.newTicket') }}
                            </CardTitle>
                            <CardDescription>{{ $t('dashboard.quickActions.newTicketDescription') }}</CardDescription>
                        </CardHeader>
                    </Link>
                </Card>

                <Card class="border-dashed border-2 hover:border-primary/50 transition-colors cursor-pointer">
                    <Link :href="carsRoutes.create().url" class="block">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <div class="p-2 rounded-lg bg-accent/10">
                                    <CarIcon class="h-5 w-5 text-accent" />
                                </div>
                                {{ $t('dashboard.quickActions.addCar') }}
                            </CardTitle>
                            <CardDescription>{{ $t('dashboard.quickActions.addCarDescription') }}</CardDescription>
                        </CardHeader>
                    </Link>
                </Card>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Tickets -->
                <Card>
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
                        <div v-if="recentTickets?.length" class="space-y-4">
                            <div
                                v-for="ticket in recentTickets"
                                :key="ticket.id"
                                class="flex items-center justify-between p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
                            >
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="p-2 rounded-lg bg-muted">
                                        <Wrench class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <div class="min-w-0">
                                        <Link
                                            :href="ticketsRoutes.show({ ticket: ticket.id }).url"
                                            class="font-medium hover:text-primary transition-colors line-clamp-1"
                                        >
                                            {{ ticket.car?.make }} {{ ticket.car?.model }}
                                        </Link>
                                        <p class="text-sm text-muted-foreground line-clamp-1">
                                            {{ ticket.description }}
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            {{ formatDate(ticket.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <Badge :variant="getStatusBadgeVariant(ticket.status)">
                                        {{ $t(`tickets.status.${ticket.status}`) }}
                                    </Badge>
                                    <span :class="['text-xs px-2 py-0.5 rounded-full', getPriorityBadgeClass(ticket.priority)]">
                                        {{ $t(`tickets.priority.${ticket.priority}`) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                            <AlertCircle class="h-12 w-12 text-muted-foreground/50 mb-3" />
                            <p class="text-muted-foreground">{{ $t('dashboard.recentTickets.empty') }}</p>
                            <Link :href="ticketsRoutes.create().url" class="mt-2">
                                <Button variant="outline" size="sm">
                                    <Plus class="mr-1 h-4 w-4" />
                                    {{ $t('dashboard.quickActions.newTicket') }}
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- My Cars -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>{{ $t('dashboard.myCars.title') }}</CardTitle>
                            <CardDescription>{{ $t('dashboard.myCars.description') }}</CardDescription>
                        </div>
                        <Link :href="carsRoutes.index().url">
                            <Button variant="ghost" size="sm">
                                {{ $t('common.viewAll') }}
                                <ArrowRight class="ml-1 h-4 w-4" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="cars?.length" class="space-y-4">
                            <div
                                v-for="car in cars"
                                :key="car.id"
                                class="flex items-center justify-between p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="p-2 rounded-lg bg-muted">
                                        <CarIcon class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <div class="min-w-0">
                                        <Link
                                            :href="carsRoutes.show({ car: car.id }).url"
                                            class="font-medium hover:text-primary transition-colors"
                                        >
                                            {{ car.make }} {{ car.model }}
                                        </Link>
                                        <p class="text-sm text-muted-foreground">
                                            {{ car.year }} • {{ car.license_plate }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="car.color" class="text-xs text-muted-foreground px-2 py-1 rounded bg-muted">
                                        {{ car.color }}
                                    </span>
                                    <Badge v-if="car.tickets_count" variant="secondary">
                                        {{ car.tickets_count }} {{ $t('dashboard.myCars.tickets') }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-8 text-center">
                            <CarIcon class="h-12 w-12 text-muted-foreground/50 mb-3" />
                            <p class="text-muted-foreground">{{ $t('dashboard.myCars.empty') }}</p>
                            <Link :href="carsRoutes.create().url" class="mt-2">
                                <Button variant="outline" size="sm">
                                    <Plus class="mr-1 h-4 w-4" />
                                    {{ $t('dashboard.quickActions.addCar') }}
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
