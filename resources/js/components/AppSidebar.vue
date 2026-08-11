<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { 
    BookOpen, 
    FolderGit2, 
    LayoutGrid, 
    Package, // Icône pour Produits
    Leaf,    // Icône pour Vanille
    Cherry,   // Icône pour Litchi (proche visuellement)
    Wind,
    ClipboardList,
    Layers,
    Truck,
    Box,
    Archive,
    ClipboardCheck,
    Sprout,
    ShieldCheck,
    Headset,
    UserCheck,
    BadgeCheck,
    Award,
    Users,
    ChartColumn,
    Building
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Litchi',
        icon: Cherry, // Icône de groupe (empilement)
        href: '#',
        items: [
            {
                title: 'Reception',
                href: '/reception',
                icon: Truck, // Icône de formulaire
            },
            {
                title: 'Soufrage',
                href: '/soufrage',
                icon: Wind, // Icône de vent/gaz pour le soufrage
            },
            {
                title: 'Triage',
                href: '/triage',
                icon: ClipboardList, // Icône de formulaire
            },
            {
                title: 'Paletisation',
                href: '/palettisation',
                icon: Layers, // Icône de vent/gaz pour le soufrage
            },
            {
                title: 'Expédition',
                href: '/expeditions',
                icon: Package, // Icône de vent/gaz pour le soufrage
            },
            {
                title: 'Statistiques',
                href: '/statistiques/litchi',
                icon: ChartColumn, // Icône de vent/gaz pour le soufrage
            },
        ],
    },
    {
        title: 'Vanille',
        icon: Leaf, // Icône de groupe (empilement)
        href: '#',
        items: [
            {
                title: 'Reception',
                href: '/fiche-reception-vanille',
                icon: Truck, // Icône de formulaire
            },
            {
                title: 'Triage',
                href: '/fiche-tracabilite-vanille',
                icon: ClipboardList, // Icône de formulaire
            },
            {
                title: 'Paletisation',
                href: '/fiche-paletisation-vanille',
                icon: Layers, // Icône de vent/gaz pour le soufrage
            },
            {
                title: 'Expédition',
                href: '/fiche-expedition-vanille',
                icon: Package, // Icône de vent/gaz pour le soufrage
            },
        ],
    },
    {
        title: 'Archives',
        icon: Archive, // Icône de groupe (empilement)
        href: '#',
        items: [
            {
                title: 'Vanille',
                href: '/archives/vanille',
                icon: Leaf, // Icône de formulaire
            },
            {
                title: 'Litchi',
                href: '/archives/litchi',
                icon: Cherry, // Icône de formulaire
            }
        ],
    },
    {
        title: 'Enqueteurs',
        href: '/enqueteurs',
        icon: ClipboardCheck, // Icône de formulaire
    },
    {
        title: 'Producteurs',
        href: '/producteurs',
        icon: Sprout, // Icône de formulaire
    },
    {
        title: 'Controleur RAQT',
        href: '/raqt',
        icon: ShieldCheck
    },
    {
        title: 'Opérateurs',
        href: '/operateurs',
        icon: Users
    }
    ,
    {
        title: 'Type de Certifications',
        href: '/type-certifications',
        icon: Award
    }
    ,
    {
        title: 'Sociétés',
        href: '/societes',
        icon: Building
    }
];
const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
