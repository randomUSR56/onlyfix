import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

// Stub the ProfileController
vi.mock('@/actions/App/Http/Controllers/Settings/ProfileController', () => ({
    default: {
        destroy: {
            form: () => ({
                method: 'delete',
                action: '/profile',
            }),
        },
    },
}));

// Stub UI components
vi.mock('@/components/ui/dialog', () => ({
    Dialog: defineComponent({
        setup(_, { slots }) {
            return () => h('div', { class: 'dialog' }, slots.default?.());
        },
    }),
    DialogTrigger: defineComponent({
        props: ['asChild'],
        setup(_, { slots }) {
            return () => h('div', { class: 'dialog-trigger' }, slots.default?.());
        },
    }),
    DialogClose: defineComponent({
        props: ['asChild'],
        setup(_, { slots }) {
            return () => h('div', { class: 'dialog-close' }, slots.default?.());
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
        props: ['variant', 'type', 'disabled'],
        setup(props, { slots, attrs }) {
            return () => h('button', {
                class: `btn-${props.variant || 'default'}`,
                type: props.type,
                disabled: props.disabled,
                ...attrs,
            }, slots.default?.());
        },
    }),
}));

vi.mock('@/components/ui/input', () => ({
    Input: defineComponent({
        props: ['id', 'type', 'name', 'placeholder'],
        setup(props) {
            return () => h('input', {
                id: props.id,
                type: props.type,
                name: props.name,
                placeholder: props.placeholder,
            });
        },
    }),
}));

vi.mock('@/components/ui/label', () => ({
    Label: defineComponent({
        props: ['for'],
        setup(props, { slots }) {
            return () => h('label', { for: props.for }, slots.default?.());
        },
    }),
}));

vi.mock('@/components/HeadingSmall.vue', () => ({
    default: defineComponent({
        props: ['title', 'description'],
        setup(props) {
            return () => h('div', { class: 'heading-small' }, [
                h('h3', props.title),
                props.description ? h('p', props.description) : null,
            ]);
        },
    }),
}));

vi.mock('@/components/InputError.vue', () => ({
    default: defineComponent({
        props: ['message'],
        setup(props) {
            return () => props.message
                ? h('p', { class: 'input-error' }, props.message)
                : null;
        },
    }),
}));

import DeleteUser from '@/components/DeleteUser.vue';

describe('DeleteUser', () => {
    const mountComponent = () => mount(DeleteUser, {
        global: {
            mocks: {
                $t: (key: string) => key,
            },
        },
    });

    it('megjelenik a törlés gomb', () => {
        const wrapper = mountComponent();
        const btn = wrapper.find('[data-test="delete-user-button"]');
        expect(btn.exists()).toBe(true);
    });

    it('tartalmazza a figyelmeztetés szöveget', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('settings.deleteAccount.warningTitle');
        expect(wrapper.text()).toContain('settings.deleteAccount.warningText');
    });

    it('tartalmazza a heading-et', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('.heading-small').exists()).toBe(true);
    });

    it('a törlés gomb destructive variánsú', () => {
        const wrapper = mountComponent();
        const btn = wrapper.find('[data-test="delete-user-button"]');
        expect(btn.classes()).toContain('btn-destructive');
    });

    it('tartalmazza a megerősítő dialog elemet', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('.dialog-content').exists()).toBe(true);
    });

    it('tartalmazza a jelszó beviteli mezőt', () => {
        const wrapper = mountComponent();
        const input = wrapper.find('input[type="password"]');
        expect(input.exists()).toBe(true);
    });

    it('tartalmazza a mégse gombot', () => {
        const wrapper = mountComponent();
        const cancelBtn = wrapper.find('.dialog-close .btn-secondary');
        expect(cancelBtn.exists()).toBe(true);
        expect(cancelBtn.text()).toBe('common.cancel');
    });
});
