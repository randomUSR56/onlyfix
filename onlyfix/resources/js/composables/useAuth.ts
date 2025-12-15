import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth.user);
    const isAuthenticated = computed(() => !!user.value);

    // Role checks
    const isAdmin = computed(
        () => user.value?.roles?.includes('admin') ?? false,
    );

    const isMechanic = computed(
        () => user.value?.roles?.includes('mechanic') ?? false,
    );

    const isUser = computed(() => user.value?.roles?.includes('user') ?? false);

    // Permission checks
    const hasPermission = (permission: string) => {
        return user.value?.permissions?.includes(permission) ?? false;
    };

    const hasAnyPermission = (permissions: string[]) => {
        return permissions.some((permission) => hasPermission(permission));
    };

    const hasAllPermissions = (permissions: string[]) => {
        return permissions.every((permission) => hasPermission(permission));
    };

    // Role checks with multiple roles
    const hasRole = (role: string) => {
        return user.value?.roles?.includes(role) ?? false;
    };

    const hasAnyRole = (roles: string[]) => {
        return roles.some((role) => hasRole(role));
    };

    const hasAllRoles = (roles: string[]) => {
        return roles.every((role) => hasRole(role));
    };

    // Check if user can view all tickets (mechanic or admin)
    const canViewAllTickets = computed(() => hasAnyRole(['mechanic', 'admin']));

    // Check if user can manage users (admin only)
    const canManageUsers = computed(() => hasRole('admin'));

    // Check if user can accept tickets (mechanic or admin)
    const canAcceptTickets = computed(() => hasAnyRole(['mechanic', 'admin']));

    return {
        user,
        isAuthenticated,
        isAdmin,
        isMechanic,
        isUser,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        canViewAllTickets,
        canManageUsers,
        canAcceptTickets,
    };
}
