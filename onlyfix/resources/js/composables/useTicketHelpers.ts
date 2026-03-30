import { useI18n } from 'vue-i18n';
import { AlertCircle, Clock, Wrench, CheckCircle2 } from 'lucide-vue-next';
import type { Component } from 'vue';

interface Problem {
    name: string;
    description?: string;
    category?: string;
    [key: string]: unknown;
}

export function useTicketHelpers() {
    const { t } = useI18n();

    const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
            open: 'destructive',
            assigned: 'secondary',
            in_progress: 'default',
            completed: 'outline',
            closed: 'outline',
        };
        return variants[status] || 'secondary';
    };

    const getPriorityBadgeClass = (priority: string): string => {
        const classes: Record<string, string> = {
            urgent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
            medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            low: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        };
        return classes[priority] || 'bg-gray-100 text-gray-800';
    };

    const getStatusIcon = (status: string): Component => {
        const icons: Record<string, Component> = {
            open: AlertCircle,
            assigned: Clock,
            in_progress: Wrench,
            completed: CheckCircle2,
            closed: CheckCircle2,
        };
        return icons[status] || Clock;
    };

    const translateProblem = (problem: Problem) => {
        const translationKey = `problems.items.${problem.name}`;
        const translatedName = t(`${translationKey}.name`, problem.name);
        const translatedDescription = t(`${translationKey}.description`, problem.description || '');
        return {
            name: translatedName === `${translationKey}.name` ? problem.name : translatedName,
            description: translatedDescription === `${translationKey}.description` ? problem.description : translatedDescription,
        };
    };

    const getRoleBadgeVariant = (role: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
            admin: 'destructive',
            mechanic: 'default',
            user: 'secondary',
        };
        return variants[role] || 'outline';
    };

    const getStatusBgColorClass = (status: string): string => {
        if (status === 'open') return 'bg-orange-100 dark:bg-orange-900/30';
        if (status === 'assigned' || status === 'in_progress') return 'bg-blue-100 dark:bg-blue-900/30';
        if (status === 'completed' || status === 'closed') return 'bg-green-100 dark:bg-green-900/30';
        return 'bg-muted';
    };

    const getStatusIconColorClass = (status: string): string => {
        if (status === 'open') return 'text-orange-600 dark:text-orange-400';
        if (status === 'assigned' || status === 'in_progress') return 'text-blue-600 dark:text-blue-400';
        if (status === 'completed' || status === 'closed') return 'text-green-600 dark:text-green-400';
        return 'text-muted-foreground';
    };

    return {
        getStatusBadgeVariant,
        getPriorityBadgeClass,
        getStatusIcon,
        translateProblem,
        getRoleBadgeVariant,
        getStatusBgColorClass,
        getStatusIconColorClass,
    };
}
