<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import * as carsRoutes from '@/routes/cars';
import * as ticketsRoutes from '@/routes/tickets';
import { type BreadcrumbItem } from '@/types';
import type { DashboardStats, Ticket, Car } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useTicketHelpers } from '@/composables/useTicketHelpers';
import { useFormatting } from '@/composables/useFormatting';
import { Car as CarIcon, Plus, ArrowRight, Wrench, Clock, CheckCircle2 } from 'lucide-vue-next';

const { t } = useI18n();
const { getStatusBadgeVariant, getPriorityBadgeClass, getStatusIcon, getStatusBgColorClass, getStatusIconColorClass } = useTicketHelpers();
const { formatDate } = useFormatting();

defineProps<{
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


</script>

<template>
    <Head :title="$t('dashboard.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header with welcome and quick action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ $t('dashboard.welcome') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ $t('dashboard.subtitle') }}</p>
                </div>
                <Link :href="ticketsRoutes.create().url">
                    <Button class="shadow-lg shadow-primary/25">
                        <Plus class="mr-2 h-4 w-4" />
                        {{ $t('dashboard.quickActions.newTicket') }}
                    </Button>
                </Link>
            </div>

            <!-- Mini Stats Bar - compact, inline -->
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-orange-100 dark:bg-orange-900/30">
                    <Clock class="h-3.5 w-3.5 text-orange-600 dark:text-orange-400" />
                    <span class="font-medium text-orange-700 dark:text-orange-300">
                        {{ stats?.open_tickets ?? 0 }} {{ $t('dashboard.stats.openTickets').toLowerCase() }}
                    </span>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-green-100 dark:bg-green-900/30">
                    <CheckCircle2 class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                    <span class="font-medium text-green-700 dark:text-green-300">
                        {{ stats?.completed_tickets ?? 0 }} {{ $t('dashboard.stats.completedTickets').toLowerCase() }}
                    </span>
                </div>
            </div>

            <!-- My Tickets - Main focus -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-3">
                    <CardTitle class="text-lg">{{ $t('dashboard.recentTickets.title') }}</CardTitle>
                    <Link :href="ticketsRoutes.index().url">
                        <Button variant="ghost" size="sm">
                            {{ $t('common.viewAll') }}
                            <ArrowRight class="ml-1 h-4 w-4" />
                        </Button>
                    </Link>
                </CardHeader>
                <CardContent>
                    <div v-if="recentTickets?.length" class="space-y-2">
                        <Link
                            v-for="ticket in recentTickets"
                            :key="ticket.id"
                            :href="ticketsRoutes.show({ ticket: ticket.id }).url"
                            class="flex items-center gap-4 p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors group"
                        >
                            <!-- Status Icon -->
                            <div
                                class="p-2 rounded-lg shrink-0"
                                :class="getStatusBgColorClass(ticket.status)"
                            >
                                <component
                                    :is="getStatusIcon(ticket.status)"
                                    class="h-4 w-4"
                                    :class="getStatusIconColorClass(ticket.status)"
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
                    <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="p-4 rounded-full bg-muted mb-4">
                            <Wrench class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <h3 class="font-medium mb-1">{{ $t('dashboard.recentTickets.empty') }}</h3>
                        <p class="text-sm text-muted-foreground mb-4">{{ $t('dashboard.quickActions.newTicketDescription') }}</p>
                        <Link :href="ticketsRoutes.create().url">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                {{ $t('dashboard.quickActions.newTicket') }}
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- My Cars - Secondary, smaller section -->
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-medium text-muted-foreground">{{ $t('dashboard.myCars.title') }}</h2>
                <Link :href="carsRoutes.index().url" class="text-xs text-muted-foreground hover:text-primary transition-colors">
                    {{ $t('common.viewAll') }} →
                </Link>
            </div>
            
            <div v-if="cars?.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="car in cars"
                    :key="car.id"
                    :href="carsRoutes.show({ car: car.id }).url"
                    class="flex items-center gap-3 p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors group"
                >
                    <div class="p-2 rounded-md bg-muted group-hover:bg-primary/10 transition-colors">
                        <CarIcon class="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-sm truncate group-hover:text-primary transition-colors">
                            {{ car.make }} {{ car.model }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ car.license_plate }}
                        </p>
                    </div>
                </Link>
                
                <!-- Add Car Card -->
                <Link
                    :href="carsRoutes.create().url"
                    class="flex items-center gap-3 p-3 rounded-lg border border-dashed hover:border-primary/50 transition-colors group"
                >
                    <div class="p-2 rounded-md bg-muted/50 group-hover:bg-primary/10 transition-colors">
                        <Plus class="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </div>
                    <p class="text-sm text-muted-foreground group-hover:text-primary transition-colors">
                        {{ $t('cars.addCar') }}
                    </p>
                </Link>
            </div>
            
            <!-- No cars yet -->
            <div v-else class="flex items-center gap-4 p-4 rounded-lg border border-dashed">
                <div class="p-2 rounded-md bg-muted">
                    <CarIcon class="h-5 w-5 text-muted-foreground" />
                </div>
                <div class="flex-1">
                    <p class="text-sm text-muted-foreground">{{ $t('dashboard.myCars.empty') }}</p>
                </div>
                <Link :href="carsRoutes.create().url">
                    <Button variant="outline" size="sm">
                        <Plus class="mr-1 h-4 w-4" />
                        {{ $t('cars.addCar') }}
                    </Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
