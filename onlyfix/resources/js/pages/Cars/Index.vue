<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import * as carsRoutes from '@/routes/cars';
import { type BreadcrumbItem } from '@/types';
import type { Car, PaginatedData } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useAuth } from '@/composables/useAuth';
import { Car as CarIcon, Plus, Search, Edit, Trash2, Eye, MoreHorizontal, Ticket } from 'lucide-vue-next';
import { ref, watch, computed, onUnmounted } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const { t } = useI18n();
const { isMechanic, isAdmin } = useAuth();

// Only regular users can create cars (admin manages but doesn't own cars, mechanics can't create)
const canCreateCar = computed(() => !isMechanic.value && !isAdmin.value);

const props = defineProps<{
    cars: PaginatedData<Car>;
    filters?: {
        search?: string;
    };
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
];

const searchQuery = ref(props.filters?.search || '');
const deleteDialogOpen = ref(false);
const carToDelete = ref<Car | null>(null);

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchQuery, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(carsRoutes.index().url, { search: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
});

onUnmounted(() => {
    if (searchTimeout) clearTimeout(searchTimeout);
});

const confirmDelete = (car: Car) => {
    carToDelete.value = car;
    deleteDialogOpen.value = true;
};

const deleteCar = () => {
    if (carToDelete.value) {
        router.delete(carsRoutes.destroy({ car: carToDelete.value.id }).url, {
            onSuccess: () => {
                deleteDialogOpen.value = false;
                carToDelete.value = null;
            },
        });
    }
};

const decodePaginationLabel = (label: string) => {
    return label.replace(/&laquo;/g, '\u00AB').replace(/&raquo;/g, '\u00BB').replace(/&amp;/g, '&');
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
    <Head :title="$t('cars.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ isAdmin || isMechanic ? $t('cars.allVehicles') : $t('cars.title') }}
                    </h1>
                    <p class="text-muted-foreground">{{ $t('cars.subtitle') }}</p>
                </div>
                <Link v-if="canCreateCar" :href="carsRoutes.create().url">
                    <Button class="shadow-lg shadow-primary/25">
                        <Plus class="mr-2 h-4 w-4" />
                        {{ $t('cars.addCar') }}
                    </Button>
                </Link>
            </div>

            <!-- Search -->
            <div class="flex items-center gap-4">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        :placeholder="$t('cars.searchPlaceholder')"
                        class="pl-10"
                    />
                </div>
            </div>

            <!-- Cars Grid -->
            <div v-if="cars.data.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="car in cars.data"
                    :key="car.id"
                    class="group hover:shadow-lg transition-all duration-200"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-primary/10">
                                    <CarIcon class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <CardTitle class="text-lg">{{ car.make }} {{ car.model }}</CardTitle>
                                    <CardDescription>{{ car.year }}</CardDescription>
                                </div>
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="h-8 w-8">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="carsRoutes.show({ car: car.id }).url" class="flex items-center">
                                            <Eye class="mr-2 h-4 w-4" />
                                            {{ $t('common.view') }}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child>
                                        <Link :href="carsRoutes.edit({ car: car.id }).url" class="flex items-center">
                                            <Edit class="mr-2 h-4 w-4" />
                                            {{ $t('common.edit') }}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete(car)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        {{ $t('common.delete') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">{{ $t('cars.licensePlate') }}</span>
                                <span class="font-mono font-medium">{{ car.license_plate }}</span>
                            </div>
                            <div v-if="car.color" class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">{{ $t('cars.color') }}</span>
                                <span>{{ car.color }}</span>
                            </div>
                            <div v-if="car.vin" class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">{{ $t('cars.vin') }}</span>
                                <span class="font-mono text-xs truncate max-w-[120px]">{{ car.vin }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">{{ $t('cars.registered') }}</span>
                                <span>{{ formatDate(car.created_at) }}</span>
                            </div>
                            <div class="pt-2 border-t">
                                <Link
                                    :href="carsRoutes.tickets({ car: car.id }).url"
                                    class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                                >
                                    <Ticket class="h-3 w-3" />
                                    {{ car.tickets_count ?? 0 }} {{ $t('cars.serviceHistory') }}
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else class="flex flex-col items-center justify-center py-12">
                <CarIcon class="h-16 w-16 text-muted-foreground/50 mb-4" />
                <h3 class="text-lg font-semibold mb-1">{{ $t('cars.empty.title') }}</h3>
                <p class="text-muted-foreground mb-4">{{ $t('cars.empty.description') }}</p>
                <Link v-if="canCreateCar" :href="carsRoutes.create().url">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        {{ $t('cars.addCar') }}
                    </Button>
                </Link>
            </Card>

            <!-- Pagination -->
            <div v-if="cars.last_page > 1" class="flex items-center justify-center gap-2">
                <Button
                    v-for="link in cars.links"
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

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ $t('cars.delete.title') }}</DialogTitle>
                    <DialogDescription>
                        {{ $t('cars.delete.description', { car: `${carToDelete?.make} ${carToDelete?.model}` }) }}
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
