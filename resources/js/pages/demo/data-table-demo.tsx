'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, MoreHorizontal } from 'lucide-react';

// Sample data type
export type User = {
    id: string;
    name: string;
    email: string;
    status: 'active' | 'inactive' | 'pending';
    role: string;
};

// Column definitions
export const columns: ColumnDef<User>[] = [
    {
        accessorKey: 'name',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                >
                    Name
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
    },
    {
        accessorKey: 'email',
        header: ({ column }) => {
            return (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                >
                    Email
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            return (
                <Badge
                    variant={
                        status === 'active'
                            ? 'default'
                            : status === 'inactive'
                              ? 'secondary'
                              : 'outline'
                    }
                >
                    {status}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'role',
        header: 'Role',
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const user = row.original;

            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                        <DropdownMenuItem
                            onClick={() =>
                                navigator.clipboard.writeText(user.id)
                            }
                        >
                            Copy user ID
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem>View details</DropdownMenuItem>
                        <DropdownMenuItem>Edit user</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];

// Sample data
const data: User[] = [
    {
        id: '1',
        name: 'John Doe',
        email: 'john@example.com',
        status: 'active',
        role: 'Admin',
    },
    {
        id: '2',
        name: 'Jane Smith',
        email: 'jane@example.com',
        status: 'active',
        role: 'Manager',
    },
    {
        id: '3',
        name: 'Bob Johnson',
        email: 'bob@example.com',
        status: 'inactive',
        role: 'User',
    },
    {
        id: '4',
        name: 'Alice Williams',
        email: 'alice@example.com',
        status: 'pending',
        role: 'User',
    },
    {
        id: '5',
        name: 'Charlie Brown',
        email: 'charlie@example.com',
        status: 'active',
        role: 'Manager',
    },
    {
        id: '6',
        name: 'Diana Prince',
        email: 'diana@example.com',
        status: 'active',
        role: 'Admin',
    },
    {
        id: '7',
        name: 'Ethan Hunt',
        email: 'ethan@example.com',
        status: 'inactive',
        role: 'User',
    },
    {
        id: '8',
        name: 'Fiona Green',
        email: 'fiona@example.com',
        status: 'pending',
        role: 'User',
    },
    {
        id: '9',
        name: 'George Wilson',
        email: 'george@example.com',
        status: 'active',
        role: 'Manager',
    },
    {
        id: '10',
        name: 'Hannah Davis',
        email: 'hannah@example.com',
        status: 'active',
        role: 'User',
    },
    {
        id: '11',
        name: 'Ian Taylor',
        email: 'ian@example.com',
        status: 'inactive',
        role: 'User',
    },
    {
        id: '12',
        name: 'Jessica Moore',
        email: 'jessica@example.com',
        status: 'active',
        role: 'Admin',
    },
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Demo',
        href: '/demo/data-table',
    },
    {
        title: 'DataTable',
        href: '/demo/data-table',
    },
];

export default function DataTableDemo() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="DataTable Demo" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        DataTable Component Demo
                    </h1>
                    <p className="text-muted-foreground">
                        Reusable table component with sorting, filtering, and
                        pagination.
                    </p>
                </div>

                <DataTable
                    columns={columns}
                    data={data}
                    searchKey="email"
                    searchPlaceholder="Filter by email..."
                />
            </div>
        </AppLayout>
    );
}
