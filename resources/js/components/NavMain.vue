<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
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
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const currentUrl = computed(() => page.url);

const isItemActive = (href: string | undefined) => {
    if (!href) return false;
    return currentUrl.value === href || currentUrl.value.startsWith(href + '/');
};

const isParentActive = (item: NavItem) => {
    return item.items?.some(sub => isItemActive(sub.href)) || isItemActive(item.href);
};
</script>

<template>
  <SidebarMenu class="gap-1.5">
    <template v-for="item in items" :key="item.title">
      
      <Collapsible 
        v-if="item.items && item.items.length > 0" 
        as-child 
        class="group/collapsible"
        :default-open="isParentActive(item)"
      >
        <SidebarMenuItem>
          <CollapsibleTrigger as-child>
            <SidebarMenuButton 
              :class="[
                'w-full flex items-center px-3 py-2.5 transition-all duration-200 rounded-lg group',
                isParentActive(item) 
                  ? 'bg-[var(--brand-green)] text-white shadow-md shadow-[var(--brand-green)]/20' 
                  : 'text-slate-900 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800'
              ]"
            >
              <div class="flex items-center justify-center w-5 shrink-0 mr-3">
                <component 
                  :is="item.icon" 
                  v-if="item.icon" 
                  :class="[
                    'w-4 h-4 transition-colors', 
                    isParentActive(item) ? 'text-white' : 'text-slate-500 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white'
                  ]" 
                />
              </div>

              <span class="text-sm font-bold tracking-tight flex-1 text-left">
                {{ item.title }}
              </span>

              <ChevronRight 
                :class="[
                  'w-4 h-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90',
                  isParentActive(item) ? 'text-white/70' : 'text-slate-400'
                ]"
              />
            </SidebarMenuButton>
          </CollapsibleTrigger>

          <CollapsibleContent class="overflow-hidden">
            <SidebarMenuSub class="ml-[25px] mt-1 border-l border-slate-200 dark:border-slate-700 px-0 py-1 gap-1">
              <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                <Link 
                  :href="subItem.href" 
                  :class="[
                    'flex items-center w-full pl-5 pr-3 py-2 text-[13px] transition-all rounded-r-md relative group/sub',
                    isItemActive(subItem.href) 
                      ? 'bg-[var(--brand-green)] text-white font-bold shadow-sm' 
                      : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50'
                  ]"
                >
                  <div v-if="isItemActive(subItem.href)" class="absolute left-[-1px] top-0 bottom-0 w-[2px] bg-white/40" />
                  
                  <component 
                    :is="subItem.icon" 
                    v-if="subItem.icon" 
                    :class="[
                        'w-3.5 h-3.5 mr-2 shrink-0',
                        isItemActive(subItem.href) ? 'text-white' : 'text-slate-400 group-hover/sub:text-slate-900 dark:group-hover/sub:text-white'
                    ]" 
                  />
                  {{ subItem.title }}
                </Link>
              </SidebarMenuSubItem>
            </SidebarMenuSub>
          </CollapsibleContent>
        </SidebarMenuItem>
      </Collapsible>

      <SidebarMenuItem v-else>
        <SidebarMenuButton as-child>
          <Link 
            :href="item.href"
            :class="[
              'w-full flex items-center px-3 py-2.5 transition-all duration-200 rounded-lg group',
              isItemActive(item.href) 
                ? 'bg-[var(--brand-green)] text-white shadow-md shadow-[var(--brand-green)]/20' 
                : 'text-slate-900 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <div class="flex items-center justify-center w-5 shrink-0 mr-3">
              <component 
                :is="item.icon" 
                v-if="item.icon" 
                :class="[
                    'w-4 h-4', 
                    isItemActive(item.href) ? 'text-white' : 'text-slate-500 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white'
                ]" 
              />
            </div>
            
            <span class="text-sm font-bold tracking-tight flex-1">
              {{ item.title }}
            </span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>

    </template>
  </SidebarMenu>
</template>