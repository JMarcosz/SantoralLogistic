/* eslint-disable @typescript-eslint/no-explicit-any */
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Toaster } from '@/components/ui/sonner';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, type PropsWithChildren } from 'react';
import { toast } from 'sonner';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { props } = usePage();
    useEffect(() => {
        const successMessage = (props.flash as any)?.success;
        if (successMessage) {
            // Sonner's API is simpler
            toast.success('Success!', {
                description: successMessage,
            });
        }

        const errorMessage = (props.flash as any)?.error;
        if (errorMessage) {
            toast.error('Error!', {
                description: errorMessage,
            });
        }
    }, [props.flash]);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <Toaster position="top-right" richColors closeButton />
        </AppShell>
    );
}
