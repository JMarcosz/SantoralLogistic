import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { resolveUrl } from '@/lib/utils';
import { type NavGroup, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';

/**
 * Check if user has permission to view a menu item
 */
function useHasPermission() {
    const { auth } = usePage<SharedData>().props;

    return (permission?: string): boolean => {
        if (!permission) return true; // No permission required

        const user = auth.user;
        if (!user) return false;

        // Super admin sees everything
        if (user.role_names?.includes('super_admin')) {
            return true;
        }

        // Check if user has the specific permission
        return user.permission_names?.includes(permission) ?? false;
    };
}

/**
 * Filter menu items based on user permissions
 */
function filterItemsByPermission(
    items: NavItem[],
    hasPermission: (perm?: string) => boolean,
): NavItem[] {
    return items
        .filter((item) => hasPermission(item.permission))
        .map((item) => ({
            ...item,
            items: item.items
                ? filterItemsByPermission(item.items, hasPermission)
                : undefined,
        }));
}

let scrollPositionCache = 0;

export function NavMain({ groups = [] }: { groups: NavGroup[] }) {
    const page = usePage();
    const hasPermission = useHasPermission();
    
 const scrollRef = useRef<HTMLDivElement>(null);

    // Restaurar posición del scroll al montar
  // Restaurar posición del scroll al montar
    useEffect(() => {
        if (scrollRef.current && scrollPositionCache > 0) {
            scrollRef.current.scrollTop = scrollPositionCache;
        }
    }, []);

    // Guardar posición del scroll cuando cambia
    const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
        scrollPositionCache = (e.target as HTMLDivElement).scrollTop;
    };


    // Filter groups and items based on permissions
    const filteredGroups = groups
        .filter((group) => hasPermission(group.permission))
        .map((group) => ({
            ...group,
            items: filterItemsByPermission(group.items, hasPermission),
        }))
        .filter((group) => group.items.length > 0); // Remove empty groups

    return (
        <div
            ref={scrollRef}
            onScroll={handleScroll}
            className="overflow-y-auto"
        >
            {filteredGroups.map((group) => (
                <SidebarGroup key={group.title} className="px-3 py-2">
                    <SidebarGroupLabel className="mb-2 px-2 text-xs font-bold tracking-wider text-muted-foreground/80 uppercase">
                        {group.title}
                    </SidebarGroupLabel>
                    <SidebarMenu className="space-y-1">
                        {group.items.map((item) => {
                            const hasSubitems =
                                item.items && item.items.length > 0;
                            const isActive = item.href
                                ? page.url === resolveUrl(item.href)
                                : false;

                            if (hasSubitems) {
                                return (
                                    <Collapsible
                                        key={item.title}
                                        asChild
                                        defaultOpen={isActive}
                                    >
                                        <SidebarMenuItem>
                                            <CollapsibleTrigger asChild>
                                                <SidebarMenuButton
                                                    tooltip={{
                                                        children: item.title,
                                                    }}
                                                    className="group relative rounded-lg transition-all hover:bg-primary/10 hover:shadow-sm data-[state=open]:bg-primary/5"
                                                >
                                                    {item.icon && (
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-md bg-primary/10 text-primary transition-all group-hover:scale-110 group-hover:bg-primary/20">
                                                            <item.icon className="h-4 w-4" />
                                                        </div>
                                                    )}
                                                    <span className="font-medium">
                                                        {item.title}
                                                    </span>
                                                    <ChevronRight className="ml-auto h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                                </SidebarMenuButton>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent>
                                                <SidebarMenuSub className="ml-4 border-l-2 border-primary/20 pl-4">
                                                    {item.items!.map(
                                                        (subItem) => {
                                                            const subIsActive =
                                                                subItem.href
                                                                    ? page.url ===
                                                                          resolveUrl(
                                                                              subItem.href,
                                                                          )
                                                                    : false;

                                                            return (
                                                                <SidebarMenuSubItem
                                                                    key={
                                                                        subItem.title
                                                                    }
                                                                >
                                                                    <SidebarMenuSubButton
                                                                        asChild
                                                                        isActive={
                                                                            subIsActive
                                                                        }
                                                                        className="rounded-md transition-all hover:bg-primary/10"
                                                                    >
                                                                        <Link
                                                                            href={
                                                                                subItem.href!
                                                                            }
                                                                            prefetch
                                                                        >
                                                                            <span className="font-medium">
                                                                                {
                                                                                    subItem.title
                                                                                }
                                                                            </span>
                                                                        </Link>
                                                                    </SidebarMenuSubButton>
                                                                </SidebarMenuSubItem>
                                                            );
                                                        },
                                                    )}
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuItem>
                                    </Collapsible>
                                );
                            }

                            return (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isActive}
                                        tooltip={{ children: item.title }}
                                        className="group relative rounded-lg transition-all hover:bg-primary/10 hover:shadow-sm data-[active=true]:bg-gradient-to-r data-[active=true]:from-primary/20 data-[active=true]:to-primary/10 data-[active=true]:font-semibold data-[active=true]:shadow-md"
                                    >
                                        <Link href={item.href!} prefetch>
                                            {item.icon && (
                                                <div className="flex h-8 w-8 items-center justify-center rounded-md bg-primary/10 text-primary transition-all group-hover:scale-110 group-hover:bg-primary/20 group-data-[active=true]:bg-primary group-data-[active=true]:text-primary-foreground group-data-[active=true]:shadow-md group-data-[active=true]:shadow-primary/30">
                                                    <item.icon className="h-4 w-4" />
                                                </div>
                                            )}
                                            <span className="font-medium">
                                                {item.title}
                                            </span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            );
                        })}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </div>
    );
}
