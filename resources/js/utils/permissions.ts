import { usePage } from '@inertiajs/react';

interface AuthProps {
    user: {
        id: number;
        name: string;
        email: string;
        [key: string]: unknown;
    } | null;
    can: string[];
}

/**
 * Check if the current user has a specific permission.
 * 
 * @param permission - Permission name to check (e.g., 'fiscal_sequences.manage')
 * @returns boolean - true if user has the permission, false otherwise
 * 
 * @example
 * ```tsx
 * import { can } from '@/utils/permissions';
 * 
 * function MyComponent() {
 *     return (
 *         <>
 *             {can('fiscal_sequences.manage') && (
 *                 <Button>Nuevo Rango</Button>
 *             )}
 *         </>
 *     );
 * }
 * ```
 */
export function can(permission: string): boolean {
    const { auth } = usePage<{ auth: AuthProps }>().props;
    return auth?.can?.includes(permission) ?? false;
}

/**
 * Check if the current user has ANY of the specified permissions.
 * 
 * @param permissions - Array of permission names
 * @returns boolean - true if user has at least one permission
 * 
 * @example
 * ```tsx
 * if (canAny(['invoices.view', 'invoices.create'])) {
 *     // Show invoice section
 * }
 * ```
 */
export function canAny(permissions: string[]): boolean {
    const { auth } = usePage<{ auth: AuthProps }>().props;
    return permissions.some(permission => auth?.can?.includes(permission) ?? false);
}

/**
 * Check if the current user has ALL of the specified permissions.
 * 
 * @param permissions - Array of permission names
 * @returns boolean - true if user has all permissions
 * 
 * @example
 * ```tsx
 * if (canAll(['invoices.view', 'invoices.print'])) {
 *     // Show print button
 * }
 * ```
 */
export function canAll(permissions: string[]): boolean {
    const { auth } = usePage<{ auth: AuthProps }>().props;
    return permissions.every(permission => auth?.can?.includes(permission) ?? false);
}

/**
 * Get all permissions for the current user.
 * 
 * @returns string[] - Array of permission names
 */
export function getPermissions(): string[] {
    const { auth } = usePage<{ auth: AuthProps }>().props;
    return auth?.can ?? [];
}
