import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useAppearance } from '@/hooks/use-appearance';
import {
    type BreadcrumbItem as BreadcrumbItemType,
    type SharedData,
} from '@/types';
import { usePage } from '@inertiajs/react';
import { ChevronDown, Monitor, Moon, Sun } from 'lucide-react';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage<SharedData>().props;
    const { appearance, updateAppearance } = useAppearance();

    const getCurrentThemeIcon = () => {
        switch (appearance) {
            case 'dark':
                return <Moon className="h-4 w-4" />;
            case 'light':
                return <Sun className="h-4 w-4" />;
            default:
                return <Monitor className="h-4 w-4" />;
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <header className="flex h-20 shrink-0 items-center justify-between gap-6 border-b bg-background px-6 shadow-sm transition-all md:px-8">
            {/* Left Side - Trigger + Breadcrumbs */}
            <div className="flex items-center gap-4">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            {/* Right Side - Theme + User Menu */}
            <div className="flex items-center gap-4">
                {/* Theme Selector - Simple Ghost Button */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-10 w-10 rounded-full hover:bg-muted"
                        >
                            {getCurrentThemeIcon()}
                            <span className="sr-only">Toggle theme</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-40">
                        <DropdownMenuItem
                            onClick={() => updateAppearance('light')}
                        >
                            <Sun className="mr-2 h-4 w-4" />
                            Light
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => updateAppearance('dark')}
                        >
                            <Moon className="mr-2 h-4 w-4" />
                            Dark
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => updateAppearance('system')}
                        >
                            <Monitor className="mr-2 h-4 w-4" />
                            System
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                {/* User Menu - Premium Card Style */}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="group flex items-center gap-3 rounded-xl bg-gradient-to-br from-primary/10 to-primary/5 px-4 py-2.5 shadow-md transition-all hover:from-primary/15 hover:to-primary/10 hover:shadow-lg">
                            <Avatar className="h-9 w-9 rounded-lg border-2 border-primary/30 shadow-sm">
                                <AvatarFallback className="rounded-lg bg-gradient-to-br from-primary to-primary/80 text-sm font-bold text-primary-foreground">
                                    {getInitials(auth.user.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="hidden text-left sm:block">
                                <p className="text-sm leading-none font-semibold text-foreground">
                                    {auth.user.name}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {auth.user.email}
                                </p>
                            </div>
                            <ChevronDown className="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-y-0.5" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-64 rounded-lg"
                        align="end"
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
