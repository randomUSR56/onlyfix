<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import * as carsRoutes from '@/routes/cars';
import * as ticketsRoutes from '@/routes/tickets';
import { type BreadcrumbItem } from '@/types';
import type { Car, Ticket } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    Car as CarIcon, ArrowLeft, Edit, Trash2, Calendar, Hash, Palette,
    FileText, Plus, Wrench
} from 'lucide-vue-next';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTicketHelpers } from '@/composables/useTicketHelpers';
import { useFormatting } from '@/composables/useFormatting';

const { t } = useI18n();
const { getStatusBadgeVariant, getStatusIcon } = useTicketHelpers();
const { formatDate } = useFormatting();

const props = defineProps<{
    car: Car & { tickets?: Ticket[] };
    canEdit?: boolean;
    canDelete?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
        href: dashboard().url,
    },
    {
        title: t('cars.title'),
        href: carsRoutes.index().url,
    },
    {
        title: `${props.car.make} ${props.car.model}`,
        href: carsRoutes.show({ car: props.car.id }).url,
    },
];

const deleteDialogOpen = ref(false);

const deleteCar = () => {
    router.delete(carsRoutes.destroy({ car: props.car.id }).url, {
        onSuccess: () => {
            deleteDialogOpen.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`${car.make} ${car.model}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="carsRoutes.index().url">
                        <Button variant="ghost" size="icon" :aria-label="$t('common.goBack')">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-xl bg-primary/10">
                            <CarIcon class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight">{{ car.make }} {{ car.model }}</h1>
                            <p class="text-muted-foreground">{{ car.year }} • {{ car.license_plate }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link v-if="canEdit !== false" :href="carsRoutes.edit({ car: car.id }).url">
                        <Button variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            {{ $t('common.edit') }}
                        </Button>
                    </Link>
                    <Button v-if="canDelete !== false" variant="destructive" @click="deleteDialogOpen = true">
                        <Trash2 class="mr-2 h-4 w-4" />
                        {{ $t('common.delete') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Car Details -->
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle>{{ $t('cars.show.details') }}</CardTitle>
                        <CardDescription>{{ $t('cars.show.detailsDescription') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                            <CarIcon class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('cars.form.make') }} / {{ $t('cars.form.model') }}</p>
                                <p class="font-medium">{{ car.make }} {{ car.model }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                            <Calendar class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('cars.form.year') }}</p>
                                <p class="font-medium">{{ car.year }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                            <Hash class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('cars.form.licensePlate') }}</p>
                                <p class="font-medium font-mono">{{ car.license_plate }}</p>
                            </div>
                        </div>

                        <div v-if="car.vin" class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                            <FileText class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('cars.form.vin') }}</p>
                                <p class="font-medium font-mono text-sm break-all">{{ car.vin }}</p>
                            </div>
                        </div>

                        <div v-if="car.color" class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                            <Palette class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <p class="text-sm text-muted-foreground">{{ $t('cars.form.color') }}</p>
                                <p class="font-medium">{{ car.color }}</p>
                            </div>
                        </div>

                        <div class="pt-4 border-t">
                            <p class="text-xs text-muted-foreground">
                                {{ $t('cars.show.registeredOn') }} {{ formatDate(car.created_at) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Service History -->
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>{{ $t('cars.show.serviceHistory') }}</CardTitle>
                            <CardDescription>{{ $t('cars.show.serviceHistoryDescription') }}</CardDescription>
                        </div>
                        <Link :href="ticketsRoutes.create().url + `?car_id=${car.id}`">
                            <Button size="sm">
                                <Plus class="mr-2 h-4 w-4" />
                                {{ $t('tickets.create.title') }}
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="car.tickets?.length" class="space-y-3">
                            <Link
                                v-for="ticket in car.tickets"
                                :key="ticket.id"
                                :href="ticketsRoutes.show({ ticket: ticket.id }).url"
                                class="flex items-center justify-between p-4 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
                            >
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="p-2 rounded-lg" :class="{
                                        'bg-orange-100 dark:bg-orange-900/30': ticket.status === 'open',
                                        'bg-blue-100 dark:bg-blue-900/30': ticket.status === 'assigned' || ticket.status === 'in_progress',
                                        'bg-green-100 dark:bg-green-900/30': ticket.status === 'completed' || ticket.status === 'closed',
                                    }">
                                        <component
                                            :is="getStatusIcon(ticket.status)"
                                            class="h-5 w-5"
                                            :class="{
                                                'text-orange-600 dark:text-orange-400': ticket.status === 'open',
                                                'text-blue-600 dark:text-blue-400': ticket.status === 'assigned' || ticket.status === 'in_progress',
                                                'text-green-600 dark:text-green-400': ticket.status === 'completed' || ticket.status === 'closed',
                                            }"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium line-clamp-1">{{ ticket.description }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ formatDate(ticket.created_at) }}
                                            <span v-if="ticket.problems?.length" class="ml-2">
                                                • {{ ticket.problems.length }} {{ $t('tickets.problems') }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <Badge :variant="getStatusBadgeVariant(ticket.status)">
                                    {{ $t(`tickets.status.${ticket.status}`) }}
                                </Badge>
                            </Link>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                            <Wrench class="h-12 w-12 text-muted-foreground/50 mb-3" />
                            <h3 class="font-semibold mb-1">{{ $t('cars.show.noServiceHistory') }}</h3>
                            <p class="text-sm text-muted-foreground mb-4">{{ $t('cars.show.noServiceHistoryDescription') }}</p>
                            <Link :href="ticketsRoutes.create().url + `?car_id=${car.id}`">
                                <Button variant="outline" size="sm">
                                    <Plus class="mr-2 h-4 w-4" />
                                    {{ $t('tickets.create.title') }}
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ $t('cars.delete.title') }}</DialogTitle>
                    <DialogDescription>
                        {{ $t('cars.delete.description', { car: `${car.make} ${car.model}` }) }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        {{ $t('common.cancel') }}
                    </Button>
                    <Button variant="destructive" @click="deleteCar">
                        {{ $t('common.delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
