import { vi } from 'vitest';
import { ref } from 'vue';

// ---- vue-i18n mock ----
vi.mock('vue-i18n', () => ({
    useI18n: () => ({
        t: (key: string, fallback?: string) => fallback ?? key,
        locale: ref('hu'),
    }),
    createI18n: () => ({
        global: {
            locale: ref('hu'),
            t: (key: string, fallback?: string) => fallback ?? key,
        },
        install: vi.fn(),
    }),
}));

// ---- Inertia usePage mock ----
const mockPageProps = ref({
    auth: {
        user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            roles: [{ name: 'user' }],
            permissions: [],
        },
    },
    sidebarOpen: true,
});

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: mockPageProps.value }),
    Link: {
        name: 'InertiaLink',
        template: '<a :href="href"><slot /></a>',
        props: ['href', 'method', 'as'],
    },
    router: {
        visit: vi.fn(),
        post: vi.fn(),
        delete: vi.fn(),
        flushAll: vi.fn(),
    },
    useForm: vi.fn((data: Record<string, unknown>) => ({
        ...data,
        processing: false,
        errors: {},
        submit: vi.fn(),
        delete: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
    })),
    Form: {
        name: 'InertiaForm',
        template: '<form><slot v-bind="{ errors: {}, processing: false, reset: () => {}, clearErrors: () => {} }" /></form>',
    },
}));

// ---- window.matchMedia mock ----
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});

// ---- @/i18n mock ----
vi.mock('@/i18n', () => ({
    i18n: {
        global: {
            locale: ref('hu'),
            t: (key: string, fallback?: string) => fallback ?? key,
        },
        install: vi.fn(),
    },
    availableLocales: [
        { code: 'en', name: 'English', nativeName: 'English' },
        { code: 'hu', name: 'Hungarian', nativeName: 'Magyar' },
    ],
    setLocale: vi.fn(),
    getCurrentLocale: vi.fn(() => 'hu'),
    getInitialLocale: vi.fn(() => 'hu'),
    setStoredLocale: vi.fn(),
    default: {
        global: {
            locale: ref('hu'),
            t: (key: string, fallback?: string) => fallback ?? key,
        },
        install: vi.fn(),
    },
}));

// ---- Helper to set mock page props in tests ----
export function setMockPageProps(props: Record<string, unknown>) {
    mockPageProps.value = props as typeof mockPageProps.value;
}

export function setMockUser(user: Record<string, unknown>) {
    mockPageProps.value = {
        ...mockPageProps.value,
        auth: { user: user as typeof mockPageProps.value.auth.user },
    };
}
