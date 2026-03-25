<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { dashboard } from '@/routes';
import * as ticketsRoutes from '@/routes/tickets';
import * as carsRoutes from '@/routes/cars';
import { type BreadcrumbItem } from '@/types';
import type { Ticket, Problem } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useAuth } from '@/composables/useAuth';
import { useTicketHelpers } from '@/composables/useTicketHelpers';
import { useFormatting } from '@/composables/useFormatting';
import {
    Ticket as TicketIcon, ArrowLeft, Edit, Trash2, Car as CarIcon,
    Clock, CheckCircle2, Wrench, User,
    MessageSquare, Play, UserPlus
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const { t } = useI18n();
const { isMechanic, isAdmin, user } = useAuth();
const { getStatusBadgeVariant, getPriorityBadgeClass, getStatusIcon, translateProblem } = useTicketHelpers();
const { formatLongDate, formatDate } = useFormatting();

const props = defineProps<{
    ticket: Ticket;
    canEdit: boolean;
    canDelete: boolean;
    canClose: boolean;
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
    {
        title: `#${props.ticket.id}`,
        href: ticketsRoutes.show({ ticket: props.ticket.id }).url,
    },
];

const deleteDialogOpen = ref(false);
const closeDialogOpen = ref(false);

const deleteTicket = () => {
    router.delete(ticketsRoutes.destroy({ ticket: props.ticket.id }).url, {
        onSuccess: () => {
            deleteDialogOpen.value = false;
        },
    });
};

const closeTicket = () => {
    router.post(ticketsRoutes.close({ ticket: props.ticket.id }).url, {}, {
        onSuccess: () => {
            closeDialogOpen.value = false;
        },
    });
};

// Mechanic actions
const acceptTicket = () => {
    router.post(ticketsRoutes.accept({ ticket: props.ticket.id }).url);
};

const startWork = () => {
    router.post(ticketsRoutes.start({ ticket: props.ticket.id }).url);
};

const completeTicket = () => {
    router.post(ticketsRoutes.complete({ ticket: props.ticket.id }).url);
};

// Mechanic action permissions
const canAcceptTicket = computed(() => 
    isMechanic.value && props.ticket.status === 'open'
);

const canStartWork = computed(() =>
    isMechanic.value &&
    props.ticket.status === 'assigned' &&
    props.ticket.mechanic_id === user.value?.id
);

const canCompleteTicket = computed(() =>
    isMechanic.value &&
    props.ticket.status === 'in_progress' &&
    props.ticket.mechanic_id === user.value?.id
);

const statusTimeline = computed(() => {
    const timeline = [];
    
    timeline.push({
        status: 'created',
        label: t('tickets.timeline.created'),
        date: props.ticket.created_at,
        icon: TicketIcon,
        completed: true,
    });

    if (props.ticket.accepted_at) {
        timeline.push({
            status: 'assigned',
            label: t('tickets.timeline.assigned'),
            date: props.ticket.accepted_at,
            icon: User,
            completed: true,
        });
    }

    if (props.ticket.status === 'in_progress' || props.ticket.status === 'completed' || props.ticket.status === 'closed') {
        timeline.push({
            status: 'in_progress',
            label: t('tickets.timeline.inProgress'),
            date: props.ticket.updated_at,
            icon: Wrench,
            completed: true,
        });
    }

    if (props.ticket.completed_at) {
        timeline.push({
            status: 'completed',
            label: t('tickets.timeline.completed'),
            date: props.ticket.completed_at,
            icon: CheckCircle2,
            completed: true,
        });
    }

    if (props.ticket.status === 'closed') {
        timeline.push({
            status: 'closed',
            label: t('tickets.timeline.closed'),
            date: props.ticket.updated_at,
            icon: CheckCircle2,
            completed: true,
        });
    }

    return timeline;
});

const canCloseTicket = computed(() => 
    props.canClose && props.ticket.status === 'completed'
);
</script>

<template>
    <Head :title="`${$t('tickets.ticket')} #${ticket.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <Link :href="ticketsRoutes.index().url">
                        <Button variant="ghost" size="icon" :aria-label="$t('common.goBack')">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div class="flex items-start gap-4">
                        <div class="p-3 rounded-xl" :class="{
                            'bg-orange-100 dark:bg-orange-900/30': ticket.status === 'open',
                            'bg-blue-100 dark:bg-blue-900/30': ticket.status === 'assigned' || ticket.status === 'in_progress',
                            'bg-green-100 dark:bg-green-900/30': ticket.status === 'completed' || ticket.status === 'closed',
                        }">
                            <component
                                :is="getStatusIcon(ticket.status)"
                                class="h-8 w-8"
                                :class="{
                                    'text-orange-600 dark:text-orange-400': ticket.status === 'open',
                                    'text-blue-600 dark:text-blue-400': ticket.status === 'assigned' || ticket.status === 'in_progress',
                                    'text-green-600 dark:text-green-400': ticket.status === 'completed' || ticket.status === 'closed',
                                }"
                            />
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-2xl font-bold tracking-tight">{{ $t('tickets.ticket') }} #{{ ticket.id }}</h1>
                                <Badge :variant="getStatusBadgeVariant(ticket.status)">
                                    {{ $t(`tickets.status.${ticket.status}`) }}
                                </Badge>
                                <span :class="['text-xs px-2 py-1 rounded-full', getPriorityBadgeClass(ticket.priority)]">
                                    {{ $t(`tickets.priority.${ticket.priority}`) }}
                                </span>
                            </div>
                            <p class="text-muted-foreground">
                                {{ $t('tickets.show.createdOn') }} {{ formatDate(ticket.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Mechanic Actions -->
                    <Button
                        v-if="canAcceptTicket"
                        @click="acceptTicket"
                    >
                        <UserPlus class="mr-2 h-4 w-4" />
                        {{ $t('tickets.actions.accept') }}
                    </Button>
                    <Button
                        v-if="canStartWork"
                        @click="startWork"
                    >
                        <Play class="mr-2 h-4 w-4" />
                        {{ $t('tickets.actions.startWork') }}
                    </Button>
                    <Button
                        v-if="canCompleteTicket"
                        variant="default"
                        class="bg-green-600 hover:bg-green-700"
                        @click="completeTicket"
                    >
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        {{ $t('tickets.actions.complete') }}
                    </Button>
                    
                    <!-- User Actions -->
                    <Button
                        v-if="canCloseTicket"
                        variant="outline"
                        @click="closeDialogOpen = true"
                    >
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        {{ $t('tickets.show.closeTicket') }}
                    </Button>
                    <Link v-if="canEdit" :href="ticketsRoutes.edit({ ticket: ticket.id }).url">
                        <Button variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            {{ $t('common.edit') }}
                        </Button>
                    </Link>
                    <Button v-if="canDelete" variant="destructive" @click="deleteDialogOpen = true">
                        <Trash2 class="mr-2 h-4 w-4" />
                        {{ $t('common.delete') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <MessageSquare class="h-5 w-5" />
                                {{ $t('tickets.show.description') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-wrap">{{ ticket.description }}</p>
                        </CardContent>
                    </Card>

                    <!-- Problems -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Wrench class="h-5 w-5" />
                                {{ $t('tickets.show.reportedProblems') }}
                            </CardTitle>
                            <CardDescription>{{ $t('tickets.show.reportedProblemsDescription') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="ticket.problems?.length" class="space-y-3">
                                <div
                                    v-for="problem in ticket.problems"
                                    :key="problem.id"
                                    class="p-4 rounded-lg border bg-muted/30"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <h4 class="font-medium">{{ translateProblem(problem).name }}</h4>
                                            <p class="text-xs text-muted-foreground">{{ $t(`problems.categories.${problem.category}`) }}</p>
                                        </div>
                                    </div>
                                    <p v-if="problem.description" class="text-sm text-muted-foreground mb-2">
                                        {{ translateProblem(problem).description }}
                                    </p>
                                    <div v-if="problem.pivot?.notes" class="p-2 rounded bg-background border-l-2 border-primary">
                                        <p class="text-sm">
                                            <span class="font-medium">{{ $t('tickets.show.customerNote') }}</span>
                                            {{ problem.pivot.notes }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-muted-foreground">{{ $t('tickets.show.noProblems') }}</p>
                        </CardContent>
                    </Card>

                    <!-- Timeline -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5" />
                                {{ $t('tickets.show.timeline') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="relative">
                                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-border" />
                                <div class="space-y-6">
                                    <div
                                        v-for="(item, index) in statusTimeline"
                                        :key="index"
                                        class="relative flex items-start gap-4 pl-10"
                                    >
                                        <div
                                            class="absolute left-0 flex items-center justify-center w-8 h-8 rounded-full"
                                            :class="item.completed ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                                        >
                                            <component :is="item.icon" class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ item.label }}</p>
                                            <p v-if="item.date" class="text-sm text-muted-foreground">{{ formatLongDate(item.date) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Car Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CarIcon class="h-5 w-5" />
                                {{ $t('tickets.show.vehicleInfo') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent v-if="ticket.car" class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-muted">
                                    <CarIcon class="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ ticket.car.make }} {{ ticket.car.model }}</p>
                                    <p class="text-sm text-muted-foreground">{{ ticket.car.year }}</p>
                                </div>
                            </div>
                            <Separator />
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ $t('cars.licensePlate') }}</span>
                                    <span class="font-mono font-medium">{{ ticket.car.license_plate }}</span>
                                </div>
                                <div v-if="ticket.car.color" class="flex justify-between">
                                    <span class="text-muted-foreground">{{ $t('cars.color') }}</span>
                                    <span>{{ ticket.car.color }}</span>
                                </div>
                            </div>
                            <Link :href="carsRoutes.show({ car: ticket.car.id }).url">
                                <Button variant="outline" size="sm" class="w-full">
                                    {{ $t('tickets.show.viewCarDetails') }}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    <!-- Mechanic Info -->
                    <Card v-if="ticket.mechanic">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                {{ $t('tickets.show.assignedMechanic') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                    <User class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ ticket.mechanic.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ ticket.mechanic.email }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Status Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ $t('tickets.show.statusInfo') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ $t('tickets.show.status') }}</span>
                                    <Badge :variant="getStatusBadgeVariant(ticket.status)">
                                        {{ $t(`tickets.status.${ticket.status}`) }}
                                    </Badge>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ $t('tickets.show.priority') }}</span>
                                    <span :class="['text-xs px-2 py-1 rounded-full', getPriorityBadgeClass(ticket.priority)]">
                                        {{ $t(`tickets.priority.${ticket.priority}`) }}
                                    </span>
                                </div>
                                <Separator />
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ $t('tickets.show.created') }}</span>
                                    <span>{{ formatDate(ticket.created_at) }}</span>
                                </div>
                                <div v-if="ticket.accepted_at" class="flex justify-between">
                                    <span class="text-muted-foreground">{{ $t('tickets.show.accepted') }}</span>
                                    <span>{{ formatDate(ticket.accepted_at) }}</span>
                                </div>
                                <div v-if="ticket.completed_at" class="flex justify-between">
                                    <span class="text-muted-foreground">{{ $t('tickets.show.completed') }}</span>
                                    <span>{{ formatDate(ticket.completed_at) }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ $t('tickets.delete.title') }}</DialogTitle>
                    <DialogDescription>
                        {{ $t('tickets.delete.description') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button variant="destructive" @click="deleteTicket">
                        {{ $t('common.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Close Ticket Confirmation Dialog -->
        <Dialog v-model:open="closeDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ $t('tickets.close.title') }}</DialogTitle>
                    <DialogDescription>
                        {{ $t('tickets.close.description') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="closeDialogOpen = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button @click="closeTicket">
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        {{ $t('tickets.show.closeTicket') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
