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
    Sprout
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
                href: '/fiche-reception',
                icon: Truck, // Icône de formulaire
            },
            {
                title: 'Soufrage',
                href: '/fiche-soufrage',
                icon: Wind, // Icône de vent/gaz pour le soufrage
            },
            {
                title: 'Traçabilité',
                href: '/fiche-tracabilite',
                icon: ClipboardList, // Icône de formulaire
            },
            {
                title: 'Paletisation',
                href: '/fiche-paletisation',
                icon: Layers, // Icône de vent/gaz pour le soufrage
            },
            {
                title: 'Expédition',
                href: '/fiche-expedition',
                icon: Package, // Icône de vent/gaz pour le soufrage
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
                href: '/fiche-reception',
                icon: Truck, // Icône de formulaire
            },
            {
                title: 'Traçabilité',
                href: '/fiche-tracabilite',
                icon: ClipboardList, // Icône de formulaire
            },
            {
                title: 'Paletisation',
                href: '/fiche-paletisation',
                icon: Layers, // Icône de vent/gaz pour le soufrage
            },
            {
                title: 'Expédition',
                href: '/fiche-expedition',
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
                href: '/archive-vanille',
                icon: Leaf, // Icône de formulaire
            },
            {
                title: 'Litchi',
                href: '/archive-litchi',
                icon: Cherry, // Icône de formulaire
            }
        ],
    },
    {
        title: 'Enqueteurs',
        href: '/agent-tracabilite',
        icon: ClipboardCheck, // Icône de formulaire
    },
    {
        title: 'Producteurs',
        href: '/producteurs',
        icon: Sprout, // Icône de formulaire
    }
];
const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
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
