<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import * as ticketsRoutes from '@/routes/tickets';
import * as carsRoutes from '@/routes/cars';
import { type BreadcrumbItem } from '@/types';
import type { Car, Problem } from '@/types/models';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { 
    Ticket as TicketIcon, ArrowLeft, LoaderCircle, Car as CarIcon, 
    AlertTriangle, Wrench, Plus, Info
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { t } = useI18n();
const { isAdmin } = useAuth();
const { props: pageProps } = usePage();

const props = defineProps<{
    cars: Car[];
    problems: Problem[];
    users?: User[];
    mechanics?: User[];
    preselectedCarId?: number;
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
        title: t('tickets.create.pageTitle'),
        href: ticketsRoutes.create().url,
    },
];

const form = useForm({
    user_id: isAdmin.value ? null : (pageProps.auth.user.id as number),
    car_id: props.preselectedCarId || null as number | null,
    mechanic_id: null as number | null,
    description: '',
    priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
    problem_ids: [] as number[],
    problem_notes: {} as Record<number, string>,
});

// Filter cars based on selected user if admin
const filteredCars = computed(() => {
    if (!isAdmin.value || !form.user_id) return props.cars;
    return props.cars.filter(car => car.user_id === form.user_id);
});

const priorities = [
    { value: 'low', label: t('tickets.priority.low'), class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
    { value: 'medium', label: t('tickets.priority.medium'), class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
    { value: 'high', label: t('tickets.priority.high'), class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' },
    { value: 'urgent', label: t('tickets.priority.urgent'), class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' },
];

// Group problems by category
const problemsByCategory = computed(() => {
    const grouped: Record<string, Problem[]> = {};
    props.problems.forEach(problem => {
        if (problem.is_active) {
            if (!grouped[problem.category]) {
                grouped[problem.category] = [];
            }
            grouped[problem.category].push(problem);
        }
    });
    return grouped;
});

// Translate problem name and description
const translateProblem = (problem: Problem) => {
    const translationKey = `problems.items.${problem.name}`;
    const translatedName = t(`${translationKey}.name`, problem.name);
    const translatedDescription = t(`${translationKey}.description`, problem.description || '');
    return {
        name: translatedName === `${translationKey}.name` ? problem.name : translatedName,
        description: translatedDescription === `${translationKey}.description` ? problem.description : translatedDescription,
    };
};

const toggleProblem = (problemId: number) => {
    const index = form.problem_ids.indexOf(problemId);
    if (index === -1) {
        form.problem_ids.push(problemId);
    } else {
        form.problem_ids.splice(index, 1);
        delete form.problem_notes[problemId];
    }
};

const isProblemSelected = (problemId: number) => form.problem_ids.includes(problemId);

const selectedCar = computed(() => 
    props.cars.find(car => car.id === form.car_id)
);

const submit = () => {
    // Convert problem_notes object to array matching problem_ids order
    const notesArray = form.problem_ids.map(id => form.problem_notes[id] || '');
    
    form.transform(data => ({
        ...data,
        problem_notes: notesArray,
    })).post(ticketsRoutes.store().url);
};

// Check for preselected car from URL
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const carId = urlParams.get('car_id');
    if (carId) {
        form.car_id = parseInt(carId);
    }
});
</script>

<template>
    <Head :title="$t('tickets.create.pageTitle')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="ticketsRoutes.index().url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ $t('tickets.create.title') }}</h1>
                    <p class="text-muted-foreground">{{ $t('tickets.create.subtitle') }}</p>
                </div>
            </div>

            <!-- No Cars Warning -->
            <Card v-if="!cars.length" class="border-orange-200 dark:border-orange-900 bg-orange-50 dark:bg-orange-950/30">
                <CardContent class="flex items-center gap-4 p-6">
                    <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900/50">
                        <AlertTriangle class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-orange-800 dark:text-orange-200">{{ $t('tickets.create.noCarsTitle') }}</h3>
                        <p class="text-sm text-orange-700 dark:text-orange-300">{{ $t('tickets.create.noCarsDescription') }}</p>
                    </div>
                    <Link :href="carsRoutes.create().url">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            {{ $t('cars.addCar') }}
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Form -->
            <form v-else @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Left Column - Main Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Admin Only: User Selection -->
                        <Card v-if="isAdmin && users?.length">
                            <CardHeader>
                                <CardTitle>{{ $t('admin.ticket.selectUser') }}</CardTitle>
                                <CardDescription>{{ $t('admin.ticket.selectUserDescription') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <select
                                    v-model="form.user_id"
                                    @change="form.car_id = null"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option :value="null">{{ $t('admin.ticket.selectUserPlaceholder') }}</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.user_id" class="mt-2" />
                            </CardContent>
                        </Card>

                        <!-- Admin Only: Mechanic Assignment -->
                        <Card v-if="isAdmin && mechanics?.length">
                            <CardHeader>
                                <CardTitle>{{ $t('admin.ticket.assignMechanic') }}</CardTitle>
                                <CardDescription>{{ $t('admin.ticket.assignMechanicDescription') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <select
                                    v-model="form.mechanic_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option :value="null">{{ $t('admin.ticket.assignMechanicPlaceholder') }}</option>
                                    <option v-for="mech in mechanics" :key="mech.id" :value="mech.id">
                                        {{ mech.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.mechanic_id" class="mt-2" />
                            </CardContent>
                        </Card>

                        <!-- Select Car -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-primary/10">
                                        <CarIcon class="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <CardTitle>{{ $t('tickets.create.selectCar') }}</CardTitle>
                                        <CardDescription>{{ $t('tickets.create.selectCarDescription') }}</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div
                                        v-for="car in filteredCars"
                                        :key="car.id"
                                        class="relative flex items-center gap-3 p-4 rounded-lg border-2 cursor-pointer transition-all"
                                        :class="form.car_id === car.id 
                                            ? 'border-primary bg-primary/5' 
                                            : 'border-border hover:border-primary/50'"
                                        @click="form.car_id = car.id"
                                    >
                                        <input
                                            type="radio"
                                            :value="car.id"
                                            v-model="form.car_id"
                                            class="sr-only"
                                        />
                                        <div class="p-2 rounded-lg bg-muted">
                                            <CarIcon class="h-5 w-5 text-muted-foreground" />
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ car.make }} {{ car.model }}</p>
                                            <p class="text-sm text-muted-foreground">{{ car.year }} • {{ car.license_plate }}</p>
                                        </div>
                                        <div
                                            v-if="form.car_id === car.id"
                                            class="absolute top-2 right-2 w-3 h-3 rounded-full bg-primary"
                                        />
                                    </div>
                                </div>
                                <InputError :message="form.errors.car_id" class="mt-2" />
                            </CardContent>
                        </Card>

                        <!-- Select Problems -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-accent/10">
                                        <Wrench class="h-5 w-5 text-accent" />
                                    </div>
                                    <div>
                                        <CardTitle>{{ $t('tickets.create.selectProblems') }}</CardTitle>
                                        <CardDescription>{{ $t('tickets.create.selectProblemsDescription') }}</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div v-for="(problems, category) in problemsByCategory" :key="category" class="space-y-3">
                                    <h4 class="font-medium text-sm text-muted-foreground uppercase tracking-wide">
                                        {{ $t(`problems.categories.${category}`) }}
                                    </h4>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <div
                                            v-for="problem in problems"
                                            :key="problem.id"
                                            class="space-y-2"
                                        >
                                            <div
                                                class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                                                :class="isProblemSelected(problem.id) 
                                                    ? 'border-primary bg-primary/5' 
                                                    : 'border-border hover:border-primary/50'"
                                                @click="toggleProblem(problem.id)"
                                            >
                                                <Checkbox
                                                    :checked="isProblemSelected(problem.id)"
                                                    @click.stop
                                                    @update:checked="toggleProblem(problem.id)"
                                                />
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium text-sm">{{ translateProblem(problem).name }}</p>
                                                    <p v-if="problem.description" class="text-xs text-muted-foreground line-clamp-2">
                                                        {{ translateProblem(problem).description }}
                                                    </p>
                                                </div>
                                            </div>
                                            <!-- Problem-specific notes -->
                                            <Input
                                                v-if="isProblemSelected(problem.id)"
                                                v-model="form.problem_notes[problem.id]"
                                                type="text"
                                                :placeholder="$t('tickets.create.problemNotePlaceholder')"
                                                class="text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <InputError :message="form.errors.problem_ids" />
                            </CardContent>
                        </Card>

                        <!-- Description -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                        <Info class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <CardTitle>{{ $t('tickets.create.descriptionTitle') }}</CardTitle>
                                        <CardDescription>{{ $t('tickets.create.descriptionSubtitle') }}</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <textarea
                                    v-model="form.description"
                                    :placeholder="$t('tickets.create.descriptionPlaceholder')"
                                    class="flex min-h-[150px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                    required
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Column - Priority & Summary -->
                    <div class="space-y-6">
                        <!-- Priority Selection -->
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ $t('tickets.create.priorityTitle') }}</CardTitle>
                                <CardDescription>{{ $t('tickets.create.priorityDescription') }}</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-2">
                                <div
                                    v-for="priority in priorities"
                                    :key="priority.value"
                                    class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                                    :class="form.priority === priority.value 
                                        ? 'border-primary bg-primary/5' 
                                        : 'border-border hover:border-primary/50'"
                                    @click="form.priority = priority.value as any"
                                >
                                    <input
                                        type="radio"
                                        :value="priority.value"
                                        v-model="form.priority"
                                        class="sr-only"
                                    />
                                    <span :class="['text-sm font-medium px-2 py-1 rounded', priority.class]">
                                        {{ priority.label }}
                                    </span>
                                </div>
                                <InputError :message="form.errors.priority" />
                            </CardContent>
                        </Card>

                        <!-- Summary -->
                        <Card>
                            <CardHeader>
                                <CardTitle>{{ $t('tickets.create.summaryTitle') }}</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <p class="text-sm text-muted-foreground">{{ $t('tickets.create.selectedCar') }}</p>
                                    <p v-if="selectedCar" class="font-medium">
                                        {{ selectedCar.make }} {{ selectedCar.model }} ({{ selectedCar.license_plate }})
                                    </p>
                                    <p v-else class="text-muted-foreground italic">{{ $t('tickets.create.noCarSelected') }}</p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm text-muted-foreground">{{ $t('tickets.create.selectedProblems') }}</p>
                                    <div v-if="form.problem_ids.length" class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="problemId in form.problem_ids"
                                            :key="problemId"
                                            variant="secondary"
                                        >
                                            {{ problems.find(p => p.id === problemId)?.name }}
                                        </Badge>
                                    </div>
                                    <p v-else class="text-muted-foreground italic">{{ $t('tickets.create.noProblemsSelected') }}</p>
                                </div>

                                <div class="pt-4 border-t">
                                    <Button
                                        type="submit"
                                        class="w-full shadow-lg shadow-primary/25"
                                        :disabled="form.processing || !form.car_id || !form.problem_ids.length"
                                    >
                                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                        <TicketIcon v-else class="mr-2 h-4 w-4" />
                                        {{ $t('tickets.create.submitButton') }}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
