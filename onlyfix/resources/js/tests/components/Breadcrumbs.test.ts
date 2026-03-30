import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

// Stub breadcrumb UI components
vi.mock('@/components/ui/breadcrumb', () => ({
    Breadcrumb: defineComponent({
        setup(_, { slots }) {
            return () => h('nav', { class: 'breadcrumb' }, slots.default?.());
        },
    }),
    BreadcrumbList: defineComponent({
        setup(_, { slots }) {
            return () => h('ol', { class: 'breadcrumb-list' }, slots.default?.());
        },
    }),
    BreadcrumbItem: defineComponent({
        setup(_, { slots }) {
            return () => h('li', { class: 'breadcrumb-item' }, slots.default?.());
        },
    }),
    BreadcrumbLink: defineComponent({
        props: ['asChild'],
        setup(_, { slots }) {
            return () => h('span', { class: 'breadcrumb-link' }, slots.default?.());
        },
    }),
    BreadcrumbPage: defineComponent({
        setup(_, { slots }) {
            return () => h('span', { class: 'breadcrumb-page' }, slots.default?.());
        },
    }),
    BreadcrumbSeparator: defineComponent({
        setup() {
            return () => h('span', { class: 'breadcrumb-separator' }, '/');
        },
    }),
}));

import Breadcrumbs from '@/components/Breadcrumbs.vue';

describe('Breadcrumbs', () => {
    it('rendereli az összes breadcrumb elemet', () => {
        const items = [
            { title: 'Home', href: '/' },
            { title: 'Settings', href: '/settings' },
            { title: 'Profile', href: '/settings/profile' },
        ];
        const wrapper = mount(Breadcrumbs, {
            props: { breadcrumbs: items },
        });
        const breadcrumbItems = wrapper.findAll('.breadcrumb-item');
        expect(breadcrumbItems).toHaveLength(3);
    });

    it('az utolsó elem BreadcrumbPage-ként jelenik meg (nem link)', () => {
        const items = [
            { title: 'Home', href: '/' },
            { title: 'Profile', href: '/profile' },
        ];
        const wrapper = mount(Breadcrumbs, {
            props: { breadcrumbs: items },
        });
        const pages = wrapper.findAll('.breadcrumb-page');
        expect(pages).toHaveLength(1);
        expect(pages[0].text()).toBe('Profile');
    });

    it('a közbenső elemek linkként jelennek meg', () => {
        const items = [
            { title: 'Home', href: '/' },
            { title: 'Settings', href: '/settings' },
            { title: 'Profile', href: '/profile' },
        ];
        const wrapper = mount(Breadcrumbs, {
            props: { breadcrumbs: items },
        });
        const links = wrapper.findAll('.breadcrumb-link');
        expect(links).toHaveLength(2);
    });

    it('elválasztókat renderel a közbülső elemek között', () => {
        const items = [
            { title: 'Home', href: '/' },
            { title: 'Settings', href: '/settings' },
            { title: 'Profile', href: '/profile' },
        ];
        const wrapper = mount(Breadcrumbs, {
            props: { breadcrumbs: items },
        });
        const separators = wrapper.findAll('.breadcrumb-separator');
        expect(separators).toHaveLength(2);
    });

    it('egy elemű tömb esetén csak BreadcrumbPage-t renderel', () => {
        const items = [{ title: 'Dashboard', href: '/dashboard' }];
        const wrapper = mount(Breadcrumbs, {
            props: { breadcrumbs: items },
        });
        expect(wrapper.findAll('.breadcrumb-page')).toHaveLength(1);
        expect(wrapper.findAll('.breadcrumb-link')).toHaveLength(0);
        expect(wrapper.findAll('.breadcrumb-separator')).toHaveLength(0);
    });

    it('üres tömb esetén nem renderel elemeket', () => {
        const wrapper = mount(Breadcrumbs, {
            props: { breadcrumbs: [] },
        });
        expect(wrapper.findAll('.breadcrumb-item')).toHaveLength(0);
    });
});
