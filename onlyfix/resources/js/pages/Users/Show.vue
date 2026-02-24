<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Mail, Calendar, Shield, Edit, Trash2, Car, ClipboardList } from 'lucide-vue-next';

const { t } = useI18n();

const props = defineProps<{
    user: User & { 
        roles: string[], 
        cars?: any[], 
        tickets?: any[] 
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('users.pageTitle'),
        href: '/users',
    },
    {
        title: props.user.name,
        href: `/users/${props.user.id}`,
    },
];

const deleteUser = () => {
    if (confirm(t('users.delete.description', { name: props.user.name }))) {
        router.delete(`/users/${props.user.id}`);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const getRoleBadgeVariant = (role: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        admin: 'destructive',
        mechanic: 'default',
        user: 'secondary',
    };
    return variants[role] || 'outline';
};
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-5xl mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center text-primary text-2xl font-bold">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ user.name }}</h1>
                        <div class="flex gap-1 mt-1">
                            <Badge 
                                v-for="role in user.roles" 
                                :key="role" 
                                :variant="getRoleBadgeVariant(role)"
                            >
                                {{ $t(`users.roles.${role}`) }}
                            </Badge>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="`/users/${user.id}/edit`">
                        <Button variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            {{ $t('common.edit') }}
                        </Button>
                    </Link>
                    <Button v-if="$page.props.auth.user.id !== user.id" variant="destructive" @click="deleteUser">
                        <Trash2 class="mr-2 h-4 w-4" />
                        {{ $t('common.delete') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Info Column -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ $t('users.show.info') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3 text-sm">
                                <Mail class="h-4 w-4 text-muted-foreground" />
                                <span>{{ user.email }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <span>{{ $t('users.show.registeredAt') }}: {{ formatDate(user.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <Shield class="h-4 w-4 text-muted-foreground" />
                                <span>{{ $t('users.show.accountStatus') }}: {{ user.email_verified_at ? $t('users.show.verified') : $t('users.show.unverified') }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="user.roles.includes('mechanic')">
                        <CardHeader>
                            <CardTitle>{{ $t('users.show.professionalData') }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ $t('users.show.totalRepairs') }}:</span>
                                <span class="font-bold">{{ user.tickets?.length || 0 }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Activity Column -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Cars Section (for users) -->
                    <Card v-if="!user.roles.includes('mechanic')">
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>{{ $t('users.show.registeredCars') }}</CardTitle>
                                <CardDescription>{{ $t('users.show.registeredCarsDescription') }}</CardDescription>
                            </div>
                            <Badge variant="outline">{{ user.cars?.length || 0 }}</Badge>
                        </CardHeader>
                        <CardContent>
                            <div v-if="user.cars?.length" class="space-y-3">
                                <div v-for="car in user.cars" :key="car.id" class="flex items-center justify-between p-3 border rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <Car class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p class="font-medium text-sm">{{ car.make }} {{ car.model }}</p>
                                            <p class="text-xs text-muted-foreground">{{ car.license_plate }}</p>
                                        </div>
                                    </div>
                                    <Link :href="`/cars/${car.id}`">
                                        <Button variant="ghost" size="sm">{{ $t('users.show.details') }}</Button>
                                    </Link>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground italic text-center py-4">
                                {{ $t('users.show.noCars') }}.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Tickets Section -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>{{ $t('users.show.repairTickets') }}</CardTitle>
                                <CardDescription>
                                    {{ user.roles.includes('mechanic') ? $t('users.show.assignedTasks') : $t('users.show.ownRequests') }}
                                </CardDescription>
                            </div>
                            <Badge variant="outline">{{ user.tickets?.length || 0 }}</Badge>
                        </CardHeader>
                        <CardContent>
                            <div v-if="user.tickets?.length" class="space-y-3">
                                <div v-for="ticket in user.tickets" :key="ticket.id" class="flex items-center justify-between p-3 border rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <ClipboardList class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p class="font-medium text-sm">#{{ ticket.id }} - {{ $t(`tickets.status.${ticket.status}`) }}</p>
                                            <p class="text-xs text-muted-foreground">{{ formatDate(ticket.created_at) }}</p>
                                        </div>
                                    </div>
                                    <Link :href="`/tickets/${ticket.id}`">
                                        <Button variant="ghost" size="sm">{{ $t('common.view') }}</Button>
                                    </Link>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground italic text-center py-4">
                                {{ $t('users.show.noTickets') }}.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
