<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import * as carsRoutes from '@/routes/cars';
import * as ticketsRoutes from '@/routes/tickets';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Car, Ticket, Settings, HelpCircle, Wrench, Inbox } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { t } = useI18n();
const page = usePage();
const { isMechanic, isAdmin } = useAuth();

// Check current route for active state
const currentUrl = computed(() => page.url);

// Navigation items for regular users
const userNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
        isActive: currentUrl.value === '/dashboard',
    },
    {
        title: t('nav.myTickets'),
        href: ticketsRoutes.index(),
        icon: Ticket,
        isActive: currentUrl.value.startsWith('/tickets'),
    },
    {
        title: t('nav.myCars'),
        href: carsRoutes.index(),
        icon: Car,
        isActive: currentUrl.value.startsWith('/cars'),
    },
]);

// Navigation items for mechanics
const mechanicNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
        isActive: currentUrl.value === '/dashboard',
    },
    {
        title: t('nav.allTickets'),
        href: ticketsRoutes.index(),
        icon: Inbox,
        isActive: currentUrl.value.startsWith('/tickets'),
    },
]);

// Use mechanic nav items if user is mechanic or admin
const mainNavItems = computed(() => 
    isMechanic.value || isAdmin.value ? mechanicNavItems.value : userNavItems.value
);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.help'),
        href: '#',
        icon: HelpCircle,
    },
    {
        title: t('nav.settings'),
        href: '/settings/profile',
        icon: Settings,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
