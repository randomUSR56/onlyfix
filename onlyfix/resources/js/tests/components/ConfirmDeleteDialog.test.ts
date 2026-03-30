import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

// Stub all UI components used by ConfirmDeleteDialog
vi.mock('@/components/ui/dialog', () => ({
    Dialog: defineComponent({
        props: ['open'],
        emits: ['update:open'],
        setup(props, { slots, emit }) {
            return () => props.open ? h('div', { class: 'dialog', 'data-testid': 'dialog' }, [
                slots.default?.(),
            ]) : null;
        },
    }),
    DialogContent: defineComponent({
        setup(_, { slots }) {
            return () => h('div', { class: 'dialog-content' }, slots.default?.());
        },
    }),
    DialogHeader: defineComponent({
        setup(_, { slots }) {
            return () => h('div', { class: 'dialog-header' }, slots.default?.());
        },
    }),
    DialogTitle: defineComponent({
        setup(_, { slots }) {
            return () => h('h2', { class: 'dialog-title' }, slots.default?.());
        },
    }),
    DialogDescription: defineComponent({
        setup(_, { slots }) {
            return () => h('p', { class: 'dialog-description' }, slots.default?.());
        },
    }),
    DialogFooter: defineComponent({
        setup(_, { slots }) {
            return () => h('div', { class: 'dialog-footer' }, slots.default?.());
        },
    }),
}));

vi.mock('@/components/ui/button', () => ({
    Button: defineComponent({
        props: ['variant', 'disabled'],
        setup(props, { slots, attrs }) {
            return () => h('button', {
                class: `btn-${props.variant || 'default'}`,
                disabled: props.disabled,
                ...attrs,
            }, slots.default?.());
        },
    }),
}));

import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';

describe('ConfirmDeleteDialog', () => {
    it('nem renderel semmit ha open prop false', () => {
        const wrapper = mount(ConfirmDeleteDialog, {
            props: {
                open: false,
                title: 'Törlés',
                description: 'Biztos törlöd?',
            },
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        expect(wrapper.find('[data-testid="dialog"]').exists()).toBe(false);
    });

    it('megjelenik ha open prop true', () => {
        const wrapper = mount(ConfirmDeleteDialog, {
            props: {
                open: true,
                title: 'Törlés',
                description: 'Biztos törlöd?',
            },
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        expect(wrapper.find('[data-testid="dialog"]').exists()).toBe(true);
    });

    it('a title és description prop megjelenik', () => {
        const wrapper = mount(ConfirmDeleteDialog, {
            props: {
                open: true,
                title: 'Fiók törlése',
                description: 'Ez a művelet nem vonható vissza.',
            },
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        expect(wrapper.find('.dialog-title').text()).toBe('Fiók törlése');
        expect(wrapper.find('.dialog-description').text()).toBe('Ez a művelet nem vonható vissza.');
    });

    it('confirm eseményt bocsát ki a törlés gomb kattintásakor', async () => {
        const wrapper = mount(ConfirmDeleteDialog, {
            props: {
                open: true,
                title: 'Törlés',
                description: 'Biztos?',
            },
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        const destructiveBtn = wrapper.find('.btn-destructive');
        await destructiveBtn.trigger('click');
        expect(wrapper.emitted('confirm')).toBeTruthy();
    });

    it('cancel (update:open false) eseményt bocsát ki a mégse gomb kattintásakor', async () => {
        const wrapper = mount(ConfirmDeleteDialog, {
            props: {
                open: true,
                title: 'Törlés',
                description: 'Biztos?',
            },
            global: {
                mocks: { $t: (key: string) => key },
            },
        });
        const outlineBtn = wrapper.find('.btn-outline');
        await outlineBtn.trigger('click');
        expect(wrapper.emitted('update:open')).toBeTruthy();
        expect(wrapper.emitted('update:open')![0]).toEqual([false]);
    });
});
