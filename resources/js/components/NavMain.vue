<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next';
import { Link, usePage } from '@inertiajs/vue3'; // Import de usePage
import { computed } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

// Récupération de l'URL actuelle via Inertia
const page = usePage();
const currentUrl = computed(() => page.url);

// Fonction pour vérifier si un lien est actif
const isItemActive = (href: string) => {
    return currentUrl.value.startsWith(href);
};
</script>

<template>
  <SidebarMenu>
    <template v-for="item in items" :key="item.title">
      
      <Collapsible 
        v-if="item.items && item.items.length > 0" 
        as-child 
        class="group/collapsible"
        :default-open="item.items.some(sub => isItemActive(sub.href))"
      >
        <SidebarMenuItem>
          <CollapsibleTrigger as-child>
            <SidebarMenuButton :is-active="item.items.some(sub => isItemActive(sub.href))">
              <component :is="item.icon" v-if="item.icon" 
                :class="{ 'text-[var(--brand-green)]': item.items.some(sub => isItemActive(sub.href)) }"
              />
              <span :class="{ 'font-bold text-[var(--brand-green)]': item.items.some(sub => isItemActive(sub.href)) }">
                {{ item.title }}
              </span>
              <ChevronRight class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
            </SidebarMenuButton>
          </CollapsibleTrigger>

            <CollapsibleContent>
            <SidebarMenuSub>
                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                <SidebarMenuSubButton as-child :is-active="isItemActive(subItem.href)">
                    <Link :href="subItem.href" class="flex items-center gap-3 w-full">
                    <component 
                        :is="subItem.icon" 
                        v-if="subItem.icon" 
                        class="w-4 h-4 shrink-0 transition-colors" 
                        :class="isItemActive(subItem.href) ? '!text-[var(--brand-green)]' : 'text-muted-foreground'"
                    />
                    <span 
                        class="transition-colors"
                        :class="isItemActive(subItem.href) ? 'font-bold !text-[var(--brand-green)]' : ''"
                    >
                        {{ subItem.title }}
                    </span>
                    </Link>
                </SidebarMenuSubButton>
                </SidebarMenuSubItem>
            </SidebarMenuSub>
            </CollapsibleContent>
        </SidebarMenuItem>
      </Collapsible>

      <SidebarMenuItem v-else>
        <SidebarMenuButton as-child :is-active="isItemActive(item.href)">
          <Link :href="item.href">
            <component 
                :is="item.icon" 
                v-if="item.icon" 
                :class="{ 'text-[var(--brand-green)]': isItemActive(item.href) }"
            />
            <span :class="{ 'font-bold text-[var(--brand-green)]': isItemActive(item.href) }">
                {{ item.title }}
            </span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>

    </template>
  </SidebarMenu>
</template>