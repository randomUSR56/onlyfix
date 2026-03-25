import { ref, watch, onUnmounted, type Ref, type WatchSource } from 'vue';
import { router } from '@inertiajs/vue3';

interface DebouncedFilterOptions {
    url: string;
    delay?: number;
    preserveState?: boolean;
    preserveScroll?: boolean;
}

export function useDebouncedFilter(
    filters: Record<string, Ref<string>>,
    options: DebouncedFilterOptions,
) {
    const { url, delay = 300, preserveState = true, preserveScroll = true } = options;

    let timer: ReturnType<typeof setTimeout> | null = null;

    const applyFilters = () => {
        const params: Record<string, string | undefined> = {};
        for (const [key, value] of Object.entries(filters)) {
            params[key] = value.value || undefined;
        }
        router.get(url, params, {
            preserveState,
            preserveScroll,
            replace: true,
        });
    };

    const sources: WatchSource[] = Object.values(filters);

    watch(sources, () => {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => {
            applyFilters();
        }, delay);
    });

    onUnmounted(() => {
        if (timer) clearTimeout(timer);
    });

    const clearFilters = () => {
        for (const value of Object.values(filters)) {
            value.value = '';
        }
        applyFilters();
    };

    return {
        applyFilters,
        clearFilters,
    };
}
