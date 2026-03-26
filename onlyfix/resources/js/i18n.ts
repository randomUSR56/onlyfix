import { createI18n } from 'vue-i18n';
import type { I18n } from 'vue-i18n';

// Import locale messages
import en from './locales/en.json';
import hu from './locales/hu.json';

export type SupportedLocale = 'en' | 'hu';

const STORAGE_KEY = 'onlyfix-locale';

/**
 * Get the stored locale from localStorage
 */
function getStoredLocale(): SupportedLocale | null {
    if (typeof window === 'undefined') return null;
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'en' || stored === 'hu') {
        return stored;
    }
    return null;
}

/**
 * Detect the browser's preferred language
 */
function detectBrowserLocale(): SupportedLocale {
    if (typeof window === 'undefined') return 'en';
    
    const browserLang = navigator.language || (navigator as any).userLanguage;
    
    // Check if browser language starts with 'hu'
    if (browserLang?.toLowerCase().startsWith('hu')) {
        return 'hu';
    }
    
    return 'en';
}

/**
 * Get the initial locale based on stored preference or browser detection
 */
export function getInitialLocale(): SupportedLocale {
    // First, check if user has a stored preference
    const stored = getStoredLocale();
    if (stored) return stored;
    
    // Otherwise, detect from browser
    return detectBrowserLocale();
}

/**
 * Save locale preference to localStorage
 */
export function setStoredLocale(locale: SupportedLocale): void {
    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, locale);
    }
}

// Type definitions for messages
export type MessageSchema = typeof en;

// Create i18n instance
// eslint-disable-next-line @typescript-eslint/no-empty-object-type
export const i18n: I18n<{ en: MessageSchema; hu: MessageSchema }, {}, {}, SupportedLocale, false> = createI18n({
    legacy: false,
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    messages: {
        en,
        hu,
    },
});

/**
 * Switch the current locale
 */
export function setLocale(locale: SupportedLocale): void {
    (i18n.global.locale as any).value = locale;
    setStoredLocale(locale);
    
    // Update HTML lang attribute
    if (typeof document !== 'undefined') {
        document.documentElement.lang = locale;
    }
}

/**
 * Get the current locale
 */
export function getCurrentLocale(): SupportedLocale {
    return (i18n.global.locale as any).value;
}

/**
 * Available locales with their display names
 */
export const availableLocales: { code: SupportedLocale; name: string; nativeName: string }[] = [
    { code: 'en', name: 'English', nativeName: 'English' },
    { code: 'hu', name: 'Hungarian', nativeName: 'Magyar' },
];

export default i18n;
