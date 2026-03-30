import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Normalize a role value to its string name.
 * Handles both string roles ('admin') and object roles ({ id: 1, name: 'admin' }).
 */
export function getRoleName(role: string | { name: string }): string {
    return typeof role === 'string' ? role : role.name;
}

/**
 * Check if a roles array contains a specific role name.
 * Works with both string[] and { name: string }[] arrays.
 */
function rolesInclude(roles: (string | { name: string })[] | undefined, roleName: string): boolean {
    return roles?.some(r => getRoleName(r) === roleName) ?? false;
}

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth.user);
    const isAuthenticated = computed(() => !!user.value);

    // Role checks
    const isAdmin = computed(
        () => rolesInclude(user.value?.roles, 'admin'),
    );

    const isMechanic = computed(
        () => rolesInclude(user.value?.roles, 'mechanic'),
    );

    const isUser = computed(() => rolesInclude(user.value?.roles, 'user'));

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
        return rolesInclude(user.value?.roles, role);
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

    // Check if user can accept tickets (mechanic only — admin manages, doesn't do mechanic work)
    const canAcceptTickets = computed(() => hasRole('mechanic'));

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
