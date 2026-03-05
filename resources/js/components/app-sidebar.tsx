import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavGroup } from '@/types';
import { Link } from '@inertiajs/react';
import {
    /*     BarChart3,
     */ Blocks,
    BookOpen,
    Building2,
    Calculator,
    /*     Calendar,
     */ ClipboardList,
    DollarSign,
    FileSpreadsheet,
    FileText,
    Folder,
    Globe,
    /*     History,
     */ LayoutGrid,
    MapPin,
    Package,
    /*     Settings,
     */ Settings2,
    Shield,
    Ship,
    ShoppingCart,
    Truck,
    UserCircle,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

const navGroups: NavGroup[] = [
    {
        title: 'General',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        title: 'CRM',
        items: [
            {
                title: 'Clientes',
                href: '/crm/customers',
                icon: Building2,
                permission: 'customers.view_any',
            },
            // {
            //     title: 'Contactos',
            //     href: '/crm/contacts',
            //     icon: Users,
            //     permission: 'contacts.view_any',
            // },
        ],
    },
    {
        title: 'Operaciones',
        items: [
            {
                title: 'Cotizaciones',
                href: '/quotes',
                icon: FileText,
                permission: 'quotes.view_any',
            },
            {
                title: 'Órdenes de Pedido',
                href: '/sales-orders',
                icon: ShoppingCart,
            },
            {
                title: 'Órdenes de Envío',
                href: '/shipping-orders',
                icon: Ship,
                permission: 'shipping_orders.view_any',
            },
            {
                title: 'Recogidas',
                href: '/pickup-orders',
                icon: Truck,
                permission: 'pickup_orders.view_any',
            },
            {
                title: 'Entregas',
                href: '/delivery-orders',
                icon: Package,
                permission: 'delivery_orders.view_any',
            },
            {
                title: 'Choferes',
                href: '/settings/drivers',
                icon: UserCircle,
                permission: 'drivers.view_any',
            },
        ],
    },
    {
        title: 'Almacén',
        items: [
            {
                title: 'Dashboard',
                href: '/warehouse/dashboard',
                icon: LayoutGrid,
                permission: 'warehouses.view_any',
            },
            {
                title: 'Recepciones (Receipts)',
                href: '/warehouse-receipts',
                icon: Package,
                permission: 'warehouse_receipts.view_any',
            },
            {
                title: 'Pedidos (Orders)',
                href: '/warehouse-orders',
                icon: ShoppingCart,
                permission: 'warehouse_orders.view_any',
            },
            {
                title: 'Inventario',
                href: '/inventory',
                icon: Blocks,
                permission: 'inventory.view_any',
            },
            {
                title: 'Conteos Cíclicos',
                href: '/cycle-counts',
                icon: ClipboardList,
                permission: 'cycle_counts.view_any',
            },
        ],
    },
    /* {
        title: 'Reportes',
        items: [
            {
                title: 'Kardex Operativo',
                href: '/warehouse/reports/movements',
                icon: FileText,
                permission: 'warehouse_movements.view_any',
            },
            {
                title: 'Reporte Inventario',
                href: '/warehouse/reports/inventory',
                icon: FileSpreadsheet,
                permission: 'warehouse_inventory.view_any',
            },
            {
                title: 'Reporte Cuentas por Cobrar',
                href: '/billing/accounts-receivable',
                icon: FileSpreadsheet,
                permission: 'billing.ar.view',
            },

            {
                title: 'Exportar DGII',
                href: '/billing/dgii/exports',
                icon: FileDown,

                //OJO: El permiso aun no está definido en el backend...
                permission: 'billing.exports.view_any',
            },
        ],
    }, */
    {
        title: 'Facturación',
        items: [
            {
                title: 'Pre-Facturas',
                href: '/pre-invoices',
                icon: FileSpreadsheet,
                permission: 'pre_invoices.view_any',
            },
            {
                title: 'Facturas',
                href: '/invoices',
                icon: FileSpreadsheet,
                permission: 'invoices.view_any',
            },
        ],
    },
    {
        title: 'Cobros',
        items: [
            {
                title: 'Pagos',
                href: '/payments',
                icon: FileSpreadsheet,

                //OJO: El permiso aun no está definido en el backend...
                permission: 'payments.view_any',
            },
        ],
    },
    /* {
        title: 'Contabilidad',
        items: [
            {
                title: 'Dashboard Contable',
                href: '/accounting',
                icon: Calculator,
                permission: 'accounting.view',
            },
            {
                title: 'Plan de Cuentas',
                href: '/accounting/accounts',
                icon: BookOpen,
                permission: 'accounting.view',
            },
            {
                title: 'Períodos Contables',
                href: '/accounting/periods',
                icon: Calendar,
                permission: 'accounting.view',
            },
            {
                title: 'Asientos Contables',
                href: '/accounting/journal-entries',
                icon: FileText,
                permission: 'accounting.view',
            },
            {
                title: 'Libro Mayor',
                href: '/accounting/ledger',
                icon: FileSpreadsheet,
                permission: 'accounting.view',
            },
            {
                title: 'Configuración',
                href: '/accounting/settings',
                icon: Settings,
                permission: 'accounting.view',
            },
            {
                title: 'Reportes Financieros',
                href: '/accounting/reports',
                icon: BarChart3,
                permission: 'accounting.view',
            },
            {
                title: 'Conciliación Bancaria',
                href: '/accounting/bank-reconciliation',
                icon: Building2,
                permission: 'accounting.view',
            },
            {
                title: 'Auditoría',
                href: '/accounting/audit-logs',
                icon: History,
                permission: 'accounting.view',
            },
        ],
    }, */
    {
        title: 'Administración',
        items: [
            {
                title: 'Usuarios',
                href: '/users',
                icon: Users,
                permission: 'users.view_any',
            },
            {
                title: 'Roles y Permisos',
                href: '/roles',
                icon: Shield,
                permission: 'roles.view_any',
            },
            {
                title: 'Secuencias Fiscales',
                href: '/admin/fiscal-sequences',
                icon: FileText,
                permission: 'fiscal_sequences.manage',
            },
        ],
    },
    {
        title: 'Configuración',
        items: [
            // Empresa & Global
            {
                title: 'Empresa',
                href: '/settings/company',
                icon: Building2,
                permission: 'company_settings.view_any',
            },
            {
                title: 'Monedas',
                href: '/settings/currencies',
                icon: DollarSign,
                permission: 'currencies.view_any',
            },
            {
                title: 'Divisiones',
                href: '/divisions',
                icon: Settings2,
                permission: 'divisions.view_any',
            },
            {
                title: 'Proyectos',
                href: '/projects',
                icon: Folder,
                permission: 'projects.view_any',
            },
            // Infraestructura
            {
                title: 'Almacenes',
                href: '/settings/warehouses',
                icon: Building2,
                permission: 'warehouses.view_any',
            },
            {
                title: 'Ubicaciones',
                href: '/settings/locations',
                icon: MapPin,
                permission: 'locations.view_any',
            },
            // Logística
            {
                title: 'Puertos',
                href: '/settings/ports',
                icon: Globe,
                permission: 'ports.view_any',
            },
            {
                title: 'Transportistas (Carriers)',
                href: '/carriers',
                icon: Ship,
                permission: 'carriers.view_any',
            },
            {
                title: 'Modos de Transporte',
                href: '/settings/transport-modes',
                icon: Truck,
                permission: 'transport_modes.view_any',
            },
            // Catálogos
            {
                title: 'Servicios y Productos',
                href: '/settings/products-services',
                icon: Package,
                permission: 'products_services.view_any',
            },
            {
                title: 'Tipos de Servicios',
                href: '/settings/service-types',
                icon: Blocks,
                permission: 'service_types.view_any',
            },
            {
                title: 'Tipos de Paquetes',
                href: '/settings/package-types',
                icon: Package,
                permission: 'package_types.view_any',
            },
            {
                title: 'Tarifas',
                href: '/settings/rates',
                icon: Calculator,
                permission: 'rates.view_any',
            },
            {
                title: 'Terminos & Cond.',
                href: '/settings/terms',
                icon: BookOpen,
                permission: 'terms.view_any',
            },
        ],
    },
];

const footerNavItems: NavGroup['items'] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/react-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#react',
    //     icon: BookOpen,
    // },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain groups={navGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
            </SidebarFooter>
        </Sidebar>
    );
}
