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
import { LayoutGrid, Car, Ticket, Settings, HelpCircle } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();
const page = usePage();

// Check current route for active state
const currentUrl = computed(() => page.url);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
        isActive: currentUrl.value === '/dashboard',
    },
    {
        title: t('nav.myCars'),
        href: carsRoutes.index(),
        icon: Car,
        isActive: currentUrl.value.startsWith('/cars'),
    },
    {
        title: t('nav.myTickets'),
        href: ticketsRoutes.index(),
        icon: Ticket,
        isActive: currentUrl.value.startsWith('/tickets'),
    },
]);

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
