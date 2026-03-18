<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import * as ticketsRoutes from '@/routes/tickets';
import { type BreadcrumbItem } from '@/types';
import type { Ticket, PaginatedData } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useAuth } from '@/composables/useAuth';
import { 
    Ticket as TicketIcon, Plus, Search, Eye, MoreHorizontal, Filter,
    Clock, CheckCircle2, AlertCircle, Wrench, Car as CarIcon, X, UserPlus
} from 'lucide-vue-next';
import { ref, watch, computed, onUnmounted } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';

const { t } = useI18n();
const { isMechanic, isAdmin, user } = useAuth();

// Mechanics can't create tickets, only users (and admins)
const canCreateTicket = computed(() => !isMechanic.value || isAdmin.value);

const props = defineProps<{
    tickets: PaginatedData<Ticket>;
    filters?: {
        search?: string;
        status?: string;
        priority?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
        href: dashboard().url,
    },
    {
        title: t('tickets.title'),
        href: ticketsRoutes.index().url,
    },
];

const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');

const statuses = ['open', 'assigned', 'in_progress', 'completed', 'closed'];
const priorities = ['low', 'medium', 'high', 'urgent'];

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

onUnmounted(() => {
    if (searchTimeout) clearTimeout(searchTimeout);
});

const applyFilters = () => {
    router.get(ticketsRoutes.index().url, {
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const setStatusFilter = (status: string) => {
    statusFilter.value = status;
    applyFilters();
};

const setPriorityFilter = (priority: string) => {
    priorityFilter.value = priority;
    applyFilters();
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    priorityFilter.value = '';
    applyFilters();
};

const hasActiveFilters = computed(() => 
    searchQuery.value || statusFilter.value || priorityFilter.value
);

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

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
    const icons: Record<string, any> = {
        open: AlertCircle,
        assigned: Clock,
        in_progress: Wrench,
        completed: CheckCircle2,
        closed: CheckCircle2,
    };
    return icons[status] || Clock;
};

// Only mechanics can accept open tickets (admin manages but doesn't do mechanic work)
const canAcceptTicket = (ticket: Ticket) => {
    return isMechanic.value && ticket.status === 'open' && !ticket.mechanic_id;
};

const decodePaginationLabel = (label: string) => {
    return label.replace(/&laquo;/g, '\u00AB').replace(/&raquo;/g, '\u00BB').replace(/&amp;/g, '&');
};

const acceptTicket = (ticketId: number) => {
    router.post(ticketsRoutes.accept({ ticket: ticketId }).url);
};
</script>

<template>
    <Head :title="$t('tickets.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ $t('tickets.title') }}</h1>
                    <p class="text-muted-foreground">{{ $t('tickets.subtitle') }}</p>
                </div>
                <Link v-if="canCreateTicket" :href="ticketsRoutes.create().url">
                    <Button class="shadow-lg shadow-primary/25">
                        <Plus class="mr-2 h-4 w-4" />
                        {{ $t('tickets.createTicket') }}
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        :placeholder="$t('tickets.searchPlaceholder')"
                        class="pl-10"
                    />
                </div>

                <!-- Status Filter -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="sm" :class="{ 'border-primary': statusFilter }">
                            <Filter class="mr-2 h-4 w-4" />
                            {{ statusFilter ? $t(`tickets.status.${statusFilter}`) : $t('tickets.filterByStatus') }}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        <DropdownMenuLabel>{{ $t('tickets.status.label') }}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="setStatusFilter('')">
                            {{ $t('common.all') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-for="status in statuses"
                            :key="status"
                            @click="setStatusFilter(status)"
                        >
                            <component :is="getStatusIcon(status)" class="mr-2 h-4 w-4" />
                            {{ $t(`tickets.status.${status}`) }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Priority Filter -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="sm" :class="{ 'border-primary': priorityFilter }">
                            <Filter class="mr-2 h-4 w-4" />
                            {{ priorityFilter ? $t(`tickets.priority.${priorityFilter}`) : $t('tickets.filterByPriority') }}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        <DropdownMenuLabel>{{ $t('tickets.priority.label') }}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="setPriorityFilter('')">
                            {{ $t('common.all') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-for="priority in priorities"
                            :key="priority"
                            @click="setPriorityFilter(priority)"
                        >
                            {{ $t(`tickets.priority.${priority}`) }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Clear Filters -->
                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    @click="clearFilters"
                >
                    <X class="mr-2 h-4 w-4" />
                    {{ $t('common.clearFilters') }}
                </Button>
            </div>

            <!-- Tickets List -->
            <div v-if="tickets.data.length" class="space-y-4">
                <Card
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    class="hover:shadow-lg transition-all duration-200"
                >
                    <CardContent class="p-4 md:p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <!-- Main Info -->
                            <div class="flex items-start gap-4 min-w-0 flex-1">
                                <div class="p-3 rounded-lg" :class="{
                                    'bg-orange-100 dark:bg-orange-900/30': ticket.status === 'open',
                                    'bg-blue-100 dark:bg-blue-900/30': ticket.status === 'assigned' || ticket.status === 'in_progress',
                                    'bg-green-100 dark:bg-green-900/30': ticket.status === 'completed' || ticket.status === 'closed',
                                }">
                                    <component
                                        :is="getStatusIcon(ticket.status)"
                                        class="h-6 w-6"
                                        :class="{
                                            'text-orange-600 dark:text-orange-400': ticket.status === 'open',
                                            'text-blue-600 dark:text-blue-400': ticket.status === 'assigned' || ticket.status === 'in_progress',
                                            'text-green-600 dark:text-green-400': ticket.status === 'completed' || ticket.status === 'closed',
                                        }"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <Link
                                            :href="ticketsRoutes.show({ ticket: ticket.id }).url"
                                            class="font-semibold text-lg hover:text-primary transition-colors line-clamp-1"
                                        >
                                            #{{ ticket.id }} - {{ ticket.car?.make }} {{ ticket.car?.model }}
                                        </Link>
                                    </div>
                                    <p class="text-muted-foreground line-clamp-2 mb-2">
                                        {{ ticket.description }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <CarIcon class="h-4 w-4" />
                                            {{ ticket.car?.license_plate }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Clock class="h-4 w-4" />
                                            {{ formatDate(ticket.created_at) }}
                                        </span>
                                        <span v-if="ticket.problems?.length" class="flex items-center gap-1">
                                            <Wrench class="h-4 w-4" />
                                            {{ ticket.problems.length }} {{ $t('tickets.problems') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status & Actions -->
                            <div class="flex items-center gap-3 md:flex-col md:items-end">
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getStatusBadgeVariant(ticket.status)">
                                        {{ $t(`tickets.status.${ticket.status}`) }}
                                    </Badge>
                                    <span :class="['text-xs px-2 py-1 rounded-full', getPriorityBadgeClass(ticket.priority)]">
                                        {{ $t(`tickets.priority.${ticket.priority}`) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Button 
                                        v-if="canAcceptTicket(ticket)" 
                                        size="sm"
                                        @click="acceptTicket(ticket.id)"
                                    >
                                        <UserPlus class="mr-2 h-4 w-4" />
                                        {{ $t('tickets.actions.accept') }}
                                    </Button>
                                    <Link :href="ticketsRoutes.show({ ticket: ticket.id }).url">
                                        <Button variant="outline" size="sm">
                                            <Eye class="mr-2 h-4 w-4" />
                                            {{ $t('common.view') }}
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else class="flex flex-col items-center justify-center py-12">
                <TicketIcon class="h-16 w-16 text-muted-foreground/50 mb-4" />
                <h3 class="text-lg font-semibold mb-1">{{ $t('tickets.empty.title') }}</h3>
                <p class="text-muted-foreground mb-4">{{ $t('tickets.empty.description') }}</p>
                <Link v-if="canCreateTicket" :href="ticketsRoutes.create().url">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        {{ $t('tickets.createTicket') }}
                    </Button>
                </Link>
            </Card>

            <!-- Pagination -->
            <div v-if="tickets.last_page > 1" class="flex items-center justify-center gap-2">
                <Button
                    v-for="link in tickets.links"
                    :key="link.label"
                    variant="outline"
                    size="sm"
                    :disabled="!link.url || link.active"
                    :class="{ 'bg-primary text-primary-foreground': link.active }"
                    @click="link.url && router.get(link.url)"
                >
                    {{ decodePaginationLabel(link.label) }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
