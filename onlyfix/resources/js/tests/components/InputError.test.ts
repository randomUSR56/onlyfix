import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import InputError from '@/components/InputError.vue';

describe('InputError', () => {
    it('nem jeleníti meg ha message prop üres string', () => {
        const wrapper = mount(InputError, {
            props: { message: '' },
        });
        // v-show display:none-t állít üres message esetén
        const div = wrapper.find('div');
        expect(div.attributes('style')).toContain('display: none');
    });

    it('nem jeleníti meg ha message prop undefined', () => {
        const wrapper = mount(InputError, {
            props: { message: undefined },
        });
        const div = wrapper.find('div');
        expect(div.attributes('style')).toContain('display: none');
    });

    it('megjeleníti a hibaüzenetet ha message meg van adva', () => {
        const wrapper = mount(InputError, {
            props: { message: 'Ez egy hiba.' },
        });
        const div = wrapper.find('div');
        const style = div.attributes('style') || '';
        expect(style).not.toContain('display: none');
        expect(wrapper.text()).toBe('Ez egy hiba.');
    });

    it('a hibaüzenet szövege egyezik a message prop értékével', () => {
        const msg = 'A jelszó túl rövid.';
        const wrapper = mount(InputError, {
            props: { message: msg },
        });
        expect(wrapper.find('p').text()).toBe(msg);
    });

    it('piros szövegszínt alkalmaz', () => {
        const wrapper = mount(InputError, {
            props: { message: 'Hiba' },
        });
        expect(wrapper.find('p').classes()).toContain('text-red-600');
    });
});
