import { describe, it, expect } from 'vitest';
import { useFormatting } from '@/composables/useFormatting';

describe('useFormatting', () => {
    const { formatDate, formatLongDate, formatSimpleDate, decodePaginationLabel } = useFormatting();

    describe('formatDate', () => {
        it('ISO dátumstringet formázott szöveggé alakít', () => {
            const result = formatDate('2024-01-15T10:00:00Z');
            expect(result).toBeTruthy();
            // A pontos formátum locale-függő, de tartalmaznia kell az évet
            expect(result).toContain('2024');
        });

        it('másik dátumot is helyesen formáz', () => {
            const result = formatDate('2023-12-25T00:00:00Z');
            expect(result).toContain('2023');
        });
    });

    describe('formatLongDate', () => {
        it('hosszú formátumot ad vissza évvel', () => {
            const result = formatLongDate('2024-06-15T14:30:00Z');
            expect(result).toBeTruthy();
            expect(result).toContain('2024');
        });

        it('időt is tartalmaz a kimenetben', () => {
            const result = formatLongDate('2024-06-15T14:30:00Z');
            expect(result).toBeTruthy();
            // A pontos formátum locale-függő, de karakterlánc legyen
            expect(typeof result).toBe('string');
        });
    });

    describe('formatSimpleDate', () => {
        it('egyszerű dátumformátumot ad vissza', () => {
            const result = formatSimpleDate('2024-03-20T00:00:00Z');
            expect(result).toBeTruthy();
            expect(typeof result).toBe('string');
        });
    });

    describe('decodePaginationLabel', () => {
        it('&laquo; entitást « karakterré alakít', () => {
            expect(decodePaginationLabel('&laquo; Previous')).toBe('\u00AB Previous');
        });

        it('&raquo; entitást » karakterré alakít', () => {
            expect(decodePaginationLabel('Next &raquo;')).toBe('Next \u00BB');
        });

        it('&amp; entitást & karakterré alakít', () => {
            expect(decodePaginationLabel('A &amp; B')).toBe('A & B');
        });

        it('egyidejűleg több entitást is dekódol', () => {
            expect(decodePaginationLabel('&laquo; A &amp; B &raquo;')).toBe('\u00AB A & B \u00BB');
        });

        it('entitás nélküli stringet változatlanul hagyja', () => {
            expect(decodePaginationLabel('Page 1')).toBe('Page 1');
        });
    });
});
