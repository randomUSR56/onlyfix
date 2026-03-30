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
import * as helpRoutes from '@/routes/help';
import * as profileRoutes from '@/routes/profile';
import * as ticketsRoutes from '@/routes/tickets';
import * as usersRoutes from '@/routes/users';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Car, Ticket, Settings, HelpCircle, Inbox, Users as UsersIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { t } = useI18n();
const { isMechanic, isAdmin } = useAuth();

// Navigation items for regular users
const userNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('nav.myTickets'),
        href: ticketsRoutes.index(),
        icon: Ticket,
    },
    {
        title: t('nav.myCars'),
        href: carsRoutes.index(),
        icon: Car,
    },
]);

// Navigation items for mechanics
const mechanicNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('nav.allTickets'),
        href: ticketsRoutes.index(),
        icon: Inbox,
    },
]);

// Navigation items for admins
const adminNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('nav.allTickets'),
        href: ticketsRoutes.index(),
        icon: Inbox,
    },
    {
        title: t('users.pageTitle'),
        href: usersRoutes.index(),
        icon: UsersIcon,
    },
]);

// Use appropriate nav items based on role
const mainNavItems = computed(() => {
    if (isAdmin.value) return adminNavItems.value;
    if (isMechanic.value) return mechanicNavItems.value;
    return userNavItems.value;
});

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.help'),
        href: helpRoutes.index(),
        icon: HelpCircle,
    },
    {
        title: t('nav.settings'),
        href: profileRoutes.edit().url,
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
