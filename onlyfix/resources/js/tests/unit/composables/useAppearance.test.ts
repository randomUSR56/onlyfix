import { describe, it, expect, vi, beforeEach } from 'vitest';
import { updateTheme } from '@/composables/useAppearance';

describe('useAppearance', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.classList.remove('dark');
    });

    describe('updateTheme', () => {
        it('dark értékkel hozzáadja a dark osztályt', () => {
            updateTheme('dark');
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });

        it('light értékkel eltávolítja a dark osztályt', () => {
            document.documentElement.classList.add('dark');
            updateTheme('light');
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });

        it('system értékkel a matchMedia eredménye alapján dönt', () => {
            // A setup.ts-ben a matchMedia mock matches: false értéket ad (light)
            updateTheme('system');
            expect(document.documentElement.classList.contains('dark')).toBe(false);
        });

        it('system értékkel dark-ot állít ha a matchMedia matches: true', () => {
            vi.spyOn(window, 'matchMedia').mockImplementation((query: string) => ({
                matches: true,
                media: query,
                onchange: null,
                addListener: vi.fn(),
                removeListener: vi.fn(),
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }));
            updateTheme('system');
            expect(document.documentElement.classList.contains('dark')).toBe(true);
        });
    });

    describe('localStorage integráció', () => {
        it('appearance értéket el lehet menteni és visszaolvasni', () => {
            localStorage.setItem('appearance', 'dark');
            expect(localStorage.getItem('appearance')).toBe('dark');
        });

        it('light értéket is elmenti', () => {
            localStorage.setItem('appearance', 'light');
            expect(localStorage.getItem('appearance')).toBe('light');
        });

        it('system értéket is elmenti', () => {
            localStorage.setItem('appearance', 'system');
            expect(localStorage.getItem('appearance')).toBe('system');
        });
    });
});
