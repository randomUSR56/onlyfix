import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

// Stub alert UI components
vi.mock('@/components/ui/alert', () => ({
    Alert: defineComponent({
        props: ['variant'],
        setup(props, { slots }) {
            return () => h('div', {
                class: 'alert',
                'data-variant': props.variant,
                role: 'alert',
            }, slots.default?.());
        },
    }),
    AlertTitle: defineComponent({
        setup(_, { slots }) {
            return () => h('h5', { class: 'alert-title' }, slots.default?.());
        },
    }),
    AlertDescription: defineComponent({
        setup(_, { slots }) {
            return () => h('div', { class: 'alert-description' }, slots.default?.());
        },
    }),
}));

vi.mock('lucide-vue-next', () => ({
    AlertCircle: defineComponent({
        setup() {
            return () => h('svg', { class: 'icon-alert-circle' });
        },
    }),
}));

import AlertError from '@/components/AlertError.vue';

describe('AlertError', () => {
    it('nem renderel semmit ha errors tömb üres', () => {
        const wrapper = mount(AlertError, {
            props: { errors: [] },
        });
        expect(wrapper.find('.alert').exists()).toBe(false);
    });

    it('megjeleníti a hibaüzeneteket', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Hiba 1', 'Hiba 2'] },
        });
        expect(wrapper.find('.alert').exists()).toBe(true);
        const items = wrapper.findAll('li');
        expect(items).toHaveLength(2);
        expect(items[0].text()).toBe('Hiba 1');
        expect(items[1].text()).toBe('Hiba 2');
    });

    it('deduplikálja az azonos hibaüzeneteket', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Hiba 1', 'Hiba 1', 'Hiba 2'] },
        });
        const items = wrapper.findAll('li');
        expect(items).toHaveLength(2);
    });

    it('egyedi title prop-ot jelenít meg ha megadják', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Hiba'], title: 'Egyedi cím' },
        });
        expect(wrapper.find('.alert-title').text()).toBe('Egyedi cím');
    });

    it('alapértelmezett címet használ ha title prop nincs megadva', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Hiba'] },
        });
        // A mock $t visszaadja a kulcsot
        expect(wrapper.find('.alert-title').text()).toBe('common.somethingWentWrong');
    });

    it('destructive variánssal renderel', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Hiba'] },
        });
        expect(wrapper.find('.alert').attributes('data-variant')).toBe('destructive');
    });
});
