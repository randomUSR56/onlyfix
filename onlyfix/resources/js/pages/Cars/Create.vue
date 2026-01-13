<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import * as carsRoutes from '@/routes/cars';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Car as CarIcon, Calendar, Hash, Palette, FileText, ArrowLeft, LoaderCircle } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const { t } = useI18n();

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
        title: t('cars.create.pageTitle'),
        href: carsRoutes.create().url,
    },
];

const form = useForm({
    make: '',
    model: '',
    year: new Date().getFullYear(),
    license_plate: '',
    vin: '',
    color: '',
});

const submit = () => {
    form.post(carsRoutes.store().url);
};

// Common car makes for suggestions
const carMakes = [
    'Toyota', 'Honda', 'Ford', 'Chevrolet', 'BMW', 'Mercedes-Benz', 'Audi', 'Volkswagen',
    'Nissan', 'Hyundai', 'Kia', 'Mazda', 'Subaru', 'Lexus', 'Jeep', 'Tesla', 'Volvo',
    'Porsche', 'Fiat', 'Peugeot', 'Renault', 'Opel', 'Skoda', 'Seat', 'Suzuki'
];

// Generate year options (last 50 years)
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 50 }, (_, i) => currentYear - i);
</script>

<template>
    <Head :title="$t('cars.create.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="carsRoutes.index().url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ $t('cars.create.title') }}</h1>
                    <p class="text-muted-foreground">{{ $t('cars.create.subtitle') }}</p>
                </div>
            </div>

            <!-- Form Card -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-primary/10">
                            <CarIcon class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <CardTitle>{{ $t('cars.create.formTitle') }}</CardTitle>
                            <CardDescription>{{ $t('cars.create.formDescription') }}</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Make & Model Row -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="make">{{ $t('cars.form.make') }} *</Label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <CarIcon class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <Input
                                        id="make"
                                        v-model="form.make"
                                        type="text"
                                        :placeholder="$t('cars.form.makePlaceholder')"
                                        class="pl-10"
                                        list="car-makes"
                                        required
                                    />
                                    <datalist id="car-makes">
                                        <option v-for="make in carMakes" :key="make" :value="make" />
                                    </datalist>
                                </div>
                                <InputError :message="form.errors.make" />
                            </div>

                            <div class="space-y-2">
                                <Label for="model">{{ $t('cars.form.model') }} *</Label>
                                <Input
                                    id="model"
                                    v-model="form.model"
                                    type="text"
                                    :placeholder="$t('cars.form.modelPlaceholder')"
                                    required
                                />
                                <InputError :message="form.errors.model" />
                            </div>
                        </div>

                        <!-- Year & License Plate Row -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="year">{{ $t('cars.form.year') }} *</Label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <select
                                        id="year"
                                        v-model="form.year"
                                        class="flex h-9 w-full rounded-md border border-input bg-transparent pl-10 pr-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        required
                                    >
                                        <option v-for="year in yearOptions" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>
                                <InputError :message="form.errors.year" />
                            </div>

                            <div class="space-y-2">
                                <Label for="license_plate">{{ $t('cars.form.licensePlate') }} *</Label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <Hash class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <Input
                                        id="license_plate"
                                        v-model="form.license_plate"
                                        type="text"
                                        :placeholder="$t('cars.form.licensePlatePlaceholder')"
                                        class="pl-10 uppercase"
                                        required
                                    />
                                </div>
                                <InputError :message="form.errors.license_plate" />
                            </div>
                        </div>

                        <!-- VIN & Color Row -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="vin">{{ $t('cars.form.vin') }}</Label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <FileText class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <Input
                                        id="vin"
                                        v-model="form.vin"
                                        type="text"
                                        :placeholder="$t('cars.form.vinPlaceholder')"
                                        class="pl-10 uppercase font-mono"
                                        maxlength="17"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">{{ $t('cars.form.vinHint') }}</p>
                                <InputError :message="form.errors.vin" />
                            </div>

                            <div class="space-y-2">
                                <Label for="color">{{ $t('cars.form.color') }}</Label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <Palette class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <Input
                                        id="color"
                                        v-model="form.color"
                                        type="text"
                                        :placeholder="$t('cars.form.colorPlaceholder')"
                                        class="pl-10"
                                    />
                                </div>
                                <InputError :message="form.errors.color" />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link :href="carsRoutes.index().url">
                                <Button type="button" variant="outline">
                                    {{ $t('common.cancel') }}
                                </Button>
                            </Link>
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="shadow-lg shadow-primary/25"
                            >
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                {{ $t('cars.create.submitButton') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
