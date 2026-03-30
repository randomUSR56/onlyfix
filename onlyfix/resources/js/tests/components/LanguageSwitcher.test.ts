import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

// Use vi.hoisted to solve the hoisting problem
const { mockLocale, mockSetLocale } = vi.hoisted(() => {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    const { ref } = require('vue');
    const mockLocale = ref('hu');
    const mockSetLocale = vi.fn((locale: string) => {
        mockLocale.value = locale;
    });
    return { mockLocale, mockSetLocale };
});

vi.mock('@/i18n', () => ({
    i18n: {
        global: {
            locale: mockLocale,
            t: (key: string) => key,
        },
        install: vi.fn(),
    },
    availableLocales: [
        { code: 'en', name: 'English', nativeName: 'English' },
        { code: 'hu', name: 'Hungarian', nativeName: 'Magyar' },
    ],
    setLocale: mockSetLocale,
    getCurrentLocale: vi.fn(() => mockLocale.value),
    default: {
        global: {
            locale: mockLocale,
            t: (key: string) => key,
        },
        install: vi.fn(),
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: defineComponent({
        props: ['variant', 'size'],
        setup(props, { slots, attrs }) {
            return () => h('button', {
                class: `btn-${props.variant || 'default'}`,
                ...attrs,
            }, slots.default?.());
        },
    }),
}));

vi.mock('@/components/ui/dropdown-menu', () => ({
    DropdownMenu: defineComponent({
        setup(_, { slots }) {
            return () => h('div', { class: 'dropdown-menu' }, slots.default?.());
        },
    }),
    DropdownMenuTrigger: defineComponent({
        props: ['asChild'],
        setup(_, { slots }) {
            return () => h('div', { class: 'dropdown-trigger' }, slots.default?.());
        },
    }),
    DropdownMenuContent: defineComponent({
        props: ['align'],
        setup(_, { slots }) {
            return () => h('div', { class: 'dropdown-content' }, slots.default?.());
        },
    }),
    DropdownMenuItem: defineComponent({
        setup(_, { slots, attrs }) {
            return () => h('div', {
                class: `dropdown-item ${attrs.class || ''}`,
                onClick: attrs.onClick,
            }, slots.default?.());
        },
    }),
}));

vi.mock('lucide-vue-next', () => ({
    Globe: defineComponent({
        setup() {
            return () => h('svg', { class: 'icon-globe' });
        },
    }),
}));

import LanguageSwitcher from '@/components/LanguageSwitcher.vue';

describe('LanguageSwitcher', () => {
    it('rendereli a nyelvi opciókat', () => {
        const wrapper = mount(LanguageSwitcher, {
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        const items = wrapper.findAll('.dropdown-item');
        expect(items.length).toBe(2);
    });

    it('megjeleníti a nativeName-eket', () => {
        const wrapper = mount(LanguageSwitcher, {
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        expect(wrapper.text()).toContain('English');
        expect(wrapper.text()).toContain('Magyar');
    });

    it('kattintásra hívja a setLocale-t en értékkel', async () => {
        mockLocale.value = 'hu';
        mockSetLocale.mockClear();
        const wrapper = mount(LanguageSwitcher, {
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        const items = wrapper.findAll('.dropdown-item');
        await items[0].trigger('click');
        expect(mockSetLocale).toHaveBeenCalledWith('en');
    });

    it('kattintásra hívja a setLocale-t hu értékkel', async () => {
        mockLocale.value = 'en';
        mockSetLocale.mockClear();
        const wrapper = mount(LanguageSwitcher, {
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        const items = wrapper.findAll('.dropdown-item');
        await items[1].trigger('click');
        expect(mockSetLocale).toHaveBeenCalledWith('hu');
    });

    it('az aktív locale kiemelt stílusú', () => {
        mockLocale.value = 'hu';
        const wrapper = mount(LanguageSwitcher, {
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        const items = wrapper.findAll('.dropdown-item');
        expect(items[1].classes()).toContain('bg-accent');
        expect(items[0].classes()).not.toContain('bg-accent');
    });
});
