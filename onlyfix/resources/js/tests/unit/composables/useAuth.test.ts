import { describe, it, expect } from 'vitest';
import { getRoleName, useAuth } from '@/composables/useAuth';
import { setMockUser } from '../../setup';

describe('getRoleName', () => {
    it('string típusú szerepkört stringként adja vissza', () => {
        expect(getRoleName('admin')).toBe('admin');
    });

    it('objektum típusú szerepkörből a name mezőt adja vissza', () => {
        expect(getRoleName({ name: 'mechanic' })).toBe('mechanic');
    });
});

describe('useAuth', () => {
    it('isAdmin true ha a szerepkör admin', () => {
        setMockUser({
            id: 1,
            name: 'Admin',
            email: 'admin@test.com',
            roles: [{ name: 'admin' }],
            permissions: [],
        });
        const { isAdmin } = useAuth();
        expect(isAdmin.value).toBe(true);
    });

    it('isAdmin false ha a szerepkör user', () => {
        setMockUser({
            id: 2,
            name: 'User',
            email: 'user@test.com',
            roles: [{ name: 'user' }],
            permissions: [],
        });
        const { isAdmin } = useAuth();
        expect(isAdmin.value).toBe(false);
    });

    it('isAdmin false ha a szerepkör mechanic', () => {
        setMockUser({
            id: 3,
            name: 'Mechanic',
            email: 'mechanic@test.com',
            roles: [{ name: 'mechanic' }],
            permissions: [],
        });
        const { isAdmin } = useAuth();
        expect(isAdmin.value).toBe(false);
    });

    it('isMechanic true ha a szerepkör mechanic', () => {
        setMockUser({
            id: 3,
            name: 'Mechanic',
            email: 'mechanic@test.com',
            roles: [{ name: 'mechanic' }],
            permissions: [],
        });
        const { isMechanic } = useAuth();
        expect(isMechanic.value).toBe(true);
    });

    it('isMechanic false ha a szerepkör user', () => {
        setMockUser({
            id: 2,
            name: 'User',
            email: 'user@test.com',
            roles: [{ name: 'user' }],
            permissions: [],
        });
        const { isMechanic } = useAuth();
        expect(isMechanic.value).toBe(false);
    });

    it('isUser true ha a szerepkör user', () => {
        setMockUser({
            id: 2,
            name: 'User',
            email: 'user@test.com',
            roles: [{ name: 'user' }],
            permissions: [],
        });
        const { isUser } = useAuth();
        expect(isUser.value).toBe(true);
    });

    it('isAuthenticated true ha a felhasználó létezik', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [],
            permissions: [],
        });
        const { isAuthenticated } = useAuth();
        expect(isAuthenticated.value).toBe(true);
    });

    it('canViewAllTickets true mechanic esetén', () => {
        setMockUser({
            id: 3,
            name: 'Mechanic',
            email: 'mechanic@test.com',
            roles: [{ name: 'mechanic' }],
            permissions: [],
        });
        const { canViewAllTickets } = useAuth();
        expect(canViewAllTickets.value).toBe(true);
    });

    it('canViewAllTickets true admin esetén', () => {
        setMockUser({
            id: 1,
            name: 'Admin',
            email: 'admin@test.com',
            roles: [{ name: 'admin' }],
            permissions: [],
        });
        const { canViewAllTickets } = useAuth();
        expect(canViewAllTickets.value).toBe(true);
    });

    it('canViewAllTickets false user esetén', () => {
        setMockUser({
            id: 2,
            name: 'User',
            email: 'user@test.com',
            roles: [{ name: 'user' }],
            permissions: [],
        });
        const { canViewAllTickets } = useAuth();
        expect(canViewAllTickets.value).toBe(false);
    });

    it('canManageUsers true admin esetén', () => {
        setMockUser({
            id: 1,
            name: 'Admin',
            email: 'admin@test.com',
            roles: [{ name: 'admin' }],
            permissions: [],
        });
        const { canManageUsers } = useAuth();
        expect(canManageUsers.value).toBe(true);
    });

    it('canManageUsers false mechanic esetén', () => {
        setMockUser({
            id: 3,
            name: 'Mechanic',
            email: 'mechanic@test.com',
            roles: [{ name: 'mechanic' }],
            permissions: [],
        });
        const { canManageUsers } = useAuth();
        expect(canManageUsers.value).toBe(false);
    });

    it('canAcceptTickets true mechanic esetén', () => {
        setMockUser({
            id: 3,
            name: 'Mechanic',
            email: 'mechanic@test.com',
            roles: [{ name: 'mechanic' }],
            permissions: [],
        });
        const { canAcceptTickets } = useAuth();
        expect(canAcceptTickets.value).toBe(true);
    });

    it('canAcceptTickets false admin esetén', () => {
        setMockUser({
            id: 1,
            name: 'Admin',
            email: 'admin@test.com',
            roles: [{ name: 'admin' }],
            permissions: [],
        });
        const { canAcceptTickets } = useAuth();
        expect(canAcceptTickets.value).toBe(false);
    });

    it('hasPermission true ha van egyező jogosultság', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [],
            permissions: ['edit-tickets', 'view-reports'],
        });
        const { hasPermission } = useAuth();
        expect(hasPermission('edit-tickets')).toBe(true);
    });

    it('hasPermission false ha nincs egyező jogosultság', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [],
            permissions: ['view-reports'],
        });
        const { hasPermission } = useAuth();
        expect(hasPermission('edit-tickets')).toBe(false);
    });

    it('hasAnyPermission true ha legalább egy jogosultság egyezik', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [],
            permissions: ['view-reports'],
        });
        const { hasAnyPermission } = useAuth();
        expect(hasAnyPermission(['edit-tickets', 'view-reports'])).toBe(true);
    });

    it('hasAllPermissions true ha minden jogosultság megvan', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [],
            permissions: ['edit-tickets', 'view-reports'],
        });
        const { hasAllPermissions } = useAuth();
        expect(hasAllPermissions(['edit-tickets', 'view-reports'])).toBe(true);
    });

    it('hasAllPermissions false ha nem minden jogosultság van meg', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [],
            permissions: ['view-reports'],
        });
        const { hasAllPermissions } = useAuth();
        expect(hasAllPermissions(['edit-tickets', 'view-reports'])).toBe(false);
    });

    it('hasRole működik string szerepkörökkel', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: ['admin'],
            permissions: [],
        });
        const { hasRole } = useAuth();
        expect(hasRole('admin')).toBe(true);
    });

    it('hasAnyRole true ha legalább egy szerepkör egyezik', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [{ name: 'mechanic' }],
            permissions: [],
        });
        const { hasAnyRole } = useAuth();
        expect(hasAnyRole(['admin', 'mechanic'])).toBe(true);
    });

    it('hasAllRoles true ha minden szerepkör megvan', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [{ name: 'admin' }, { name: 'mechanic' }],
            permissions: [],
        });
        const { hasAllRoles } = useAuth();
        expect(hasAllRoles(['admin', 'mechanic'])).toBe(true);
    });

    it('üres roles tömb esetén isAdmin false', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            roles: [],
            permissions: [],
        });
        const { isAdmin } = useAuth();
        expect(isAdmin.value).toBe(false);
    });

    it('undefined roles esetén isAdmin false', () => {
        setMockUser({
            id: 1,
            name: 'User',
            email: 'user@test.com',
            permissions: [],
        });
        const { isAdmin } = useAuth();
        expect(isAdmin.value).toBe(false);
    });
});
