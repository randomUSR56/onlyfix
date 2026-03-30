import { describe, it, expect } from 'vitest';
import { useTicketHelpers } from '@/composables/useTicketHelpers';

describe('useTicketHelpers', () => {
    const {
        getStatusBadgeVariant,
        getPriorityBadgeClass,
        getStatusIcon,
        translateProblem,
        getRoleBadgeVariant,
        getStatusBgColorClass,
        getStatusIconColorClass,
    } = useTicketHelpers();

    describe('getStatusBadgeVariant', () => {
        it('open státuszhoz destructive variánst ad', () => {
            expect(getStatusBadgeVariant('open')).toBe('destructive');
        });

        it('assigned státuszhoz secondary variánst ad', () => {
            expect(getStatusBadgeVariant('assigned')).toBe('secondary');
        });

        it('in_progress státuszhoz default variánst ad', () => {
            expect(getStatusBadgeVariant('in_progress')).toBe('default');
        });

        it('completed státuszhoz outline variánst ad', () => {
            expect(getStatusBadgeVariant('completed')).toBe('outline');
        });

        it('closed státuszhoz outline variánst ad', () => {
            expect(getStatusBadgeVariant('closed')).toBe('outline');
        });

        it('ismeretlen státuszhoz secondary fallback-et ad', () => {
            expect(getStatusBadgeVariant('unknown')).toBe('secondary');
        });
    });

    describe('getPriorityBadgeClass', () => {
        it('urgent prioritáshoz piros osztályt ad', () => {
            expect(getPriorityBadgeClass('urgent')).toContain('bg-red-100');
            expect(getPriorityBadgeClass('urgent')).toContain('text-red-800');
        });

        it('high prioritáshoz narancs osztályt ad', () => {
            expect(getPriorityBadgeClass('high')).toContain('bg-orange-100');
            expect(getPriorityBadgeClass('high')).toContain('text-orange-800');
        });

        it('medium prioritáshoz sárga osztályt ad', () => {
            expect(getPriorityBadgeClass('medium')).toContain('bg-yellow-100');
            expect(getPriorityBadgeClass('medium')).toContain('text-yellow-800');
        });

        it('low prioritáshoz zöld osztályt ad', () => {
            expect(getPriorityBadgeClass('low')).toContain('bg-green-100');
            expect(getPriorityBadgeClass('low')).toContain('text-green-800');
        });

        it('ismeretlen prioritáshoz szürke fallback-et ad', () => {
            expect(getPriorityBadgeClass('unknown')).toContain('bg-gray-100');
        });
    });

    describe('getStatusIcon', () => {
        it('minden ismert státuszhoz komponenst ad vissza', () => {
            const statuses = ['open', 'assigned', 'in_progress', 'completed', 'closed'];
            for (const status of statuses) {
                const icon = getStatusIcon(status);
                expect(icon).toBeDefined();
            }
        });

        it('ismeretlen státuszhoz is ad vissza ikont (Clock fallback)', () => {
            const icon = getStatusIcon('unknown');
            expect(icon).toBeDefined();
        });
    });

    describe('translateProblem', () => {
        it('fallback-el a problem.name-re ha nincs fordítás', () => {
            const problem = { name: 'engine_misfire', description: 'Engine misfires' };
            const result = translateProblem(problem);
            expect(result.name).toBe('engine_misfire');
            expect(result.description).toBe('Engine misfires');
        });

        it('üres description esetén üres stringet ad fallback-ként', () => {
            const problem = { name: 'brake_issue' };
            const result = translateProblem(problem);
            expect(result.name).toBe('brake_issue');
            expect(result.description).toBe('');
        });
    });

    describe('getRoleBadgeVariant', () => {
        it('admin szerepkörhöz destructive variánst ad', () => {
            expect(getRoleBadgeVariant('admin')).toBe('destructive');
        });

        it('mechanic szerepkörhöz default variánst ad', () => {
            expect(getRoleBadgeVariant('mechanic')).toBe('default');
        });

        it('user szerepkörhöz secondary variánst ad', () => {
            expect(getRoleBadgeVariant('user')).toBe('secondary');
        });

        it('ismeretlen szerepkörhöz outline fallback-et ad', () => {
            expect(getRoleBadgeVariant('unknown')).toBe('outline');
        });
    });

    describe('getStatusBgColorClass', () => {
        it('open státuszhoz narancs hátteret ad', () => {
            expect(getStatusBgColorClass('open')).toContain('bg-orange-100');
        });

        it('assigned státuszhoz kék hátteret ad', () => {
            expect(getStatusBgColorClass('assigned')).toContain('bg-blue-100');
        });

        it('in_progress státuszhoz kék hátteret ad', () => {
            expect(getStatusBgColorClass('in_progress')).toContain('bg-blue-100');
        });

        it('completed státuszhoz zöld hátteret ad', () => {
            expect(getStatusBgColorClass('completed')).toContain('bg-green-100');
        });

        it('closed státuszhoz zöld hátteret ad', () => {
            expect(getStatusBgColorClass('closed')).toContain('bg-green-100');
        });

        it('ismeretlen státuszhoz muted fallback-et ad', () => {
            expect(getStatusBgColorClass('unknown')).toBe('bg-muted');
        });
    });

    describe('getStatusIconColorClass', () => {
        it('open státuszhoz narancs szöveget ad', () => {
            expect(getStatusIconColorClass('open')).toContain('text-orange-600');
        });

        it('assigned státuszhoz kék szöveget ad', () => {
            expect(getStatusIconColorClass('assigned')).toContain('text-blue-600');
        });

        it('in_progress státuszhoz kék szöveget ad', () => {
            expect(getStatusIconColorClass('in_progress')).toContain('text-blue-600');
        });

        it('completed státuszhoz zöld szöveget ad', () => {
            expect(getStatusIconColorClass('completed')).toContain('text-green-600');
        });

        it('ismeretlen státuszhoz muted-foreground fallback-et ad', () => {
            expect(getStatusIconColorClass('unknown')).toBe('text-muted-foreground');
        });
    });
});
