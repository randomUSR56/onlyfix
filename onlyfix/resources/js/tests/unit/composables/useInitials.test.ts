import { describe, it, expect } from 'vitest';
import { getInitials, useInitials } from '@/composables/useInitials';

describe('getInitials', () => {
    it('két szóból a kezdőbetűket adja vissza nagybetűvel', () => {
        expect(getInitials('John Doe')).toBe('JD');
    });

    it('egy szóból az első betűt adja vissza nagybetűvel', () => {
        expect(getInitials('Single')).toBe('S');
    });

    it('három szóból az első és utolsó szó kezdőbetűjét adja vissza', () => {
        expect(getInitials('John Middle Doe')).toBe('JD');
    });

    it('üres string esetén üres stringet ad vissza', () => {
        expect(getInitials('')).toBe('');
    });

    it('undefined esetén üres stringet ad vissza', () => {
        expect(getInitials(undefined)).toBe('');
    });

    it('kisbetűs nevet is nagybetűvel ad vissza', () => {
        expect(getInitials('jane doe')).toBe('JD');
    });

    it('felesleges szóközöket kezel', () => {
        expect(getInitials('  John Doe  ')).toBe('JD');
    });

    it('magyar ékezetes neveket is kezel', () => {
        expect(getInitials('Ádám Éva')).toBe('ÁÉ');
    });
});

describe('useInitials', () => {
    it('getInitials függvényt ad vissza', () => {
        const { getInitials: fn } = useInitials();
        expect(typeof fn).toBe('function');
        expect(fn('Test User')).toBe('TU');
    });
});
