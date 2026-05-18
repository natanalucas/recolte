<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

// Gestion des années
const currentYear = new Date().getFullYear();
const selectedYear = ref(currentYear);
const years = [2026, 2025, 2024, 2023];

// Définition des catégories avec leurs icônes respectives
const categories = [
    { title: 'Réception', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { title: 'Soufrage', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { title: 'Triage', icon: 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z' },
    { title: 'Palettisation', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { title: 'Expédition', icon: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' },
];

const breadcrumbs = [
    { title: 'Archives', href: '#' },
    { title: 'Vanille', href: '#' }
];
</script>

<template>
    <Head :title="`Archives ${selectedYear}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 space-y-8 max-w-7xl mx-auto">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[var(--sidebar-border)] pb-6">
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight">
                        Archives <span class="text-[var(--brand-green)]">Vanille</span>
                    </h1>
                </div>

                <div class="relative inline-block">
                    <div class="flex items-center gap-2 bg-[var(--card)] border border-[var(--sidebar-border)] rounded-lg px-4 py-2 shadow-sm">
                        <svg class="w-4 h-4 text-[var(--brand-green)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <select v-model="selectedYear" class=" text-[16px] bg-transparent outline-none font-bold text-sm cursor-pointer pr-4">
                            <option v-for="year in years" :key="year" :value="year">
                                {{ year }} {{ year === currentYear ? '(En cours)' : '' }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
    <div 
        v-for="cat in categories" 
        :key="cat.title"
        class="group bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-[var(--brand-green)]/40 transition-all flex flex-col"
    >
        <div class="p-6 flex flex-col items-center text-center space-y-4 flex-grow">
            <div class="p-4 rounded-2xl bg-[var(--brand-green)]/10 text-[var(--brand-green)] group-hover:scale-110 transition-transform duration-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="cat.icon"></path>
                </svg>
            </div>
            
            <div>
                <h3 class="text-[14px] font-black uppercase tracking-widest text-slate-700 dark:text-slate-200">
                    {{ cat.title }}
                </h3>
<p class="text-[12px] text-[var(--brand-green)] dark:text-[var(--brand-orange)] mt-1 font-medium italic">
    Archives {{ selectedYear }}
</p>
            </div>
        </div>

        <div class="p-4 bg-slate-50/50 dark:bg-black/10 border-t border-[var(--sidebar-border)]">
            <button class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-xl bg-white dark:bg-slate-800 border border-[var(--brand-orange)]/30 text-[var(--brand-orange)] hover:bg-[var(--brand-orange)] hover:text-white transition-all shadow-sm group/btn">
                <svg class="w-5 h-5 text-[var(--brand-orange)] group-hover/btn:text-white group-hover/btn:scale-110 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <span class="text-[11px] font-black uppercase tracking-wide">Voir le résumé PDF</span>
            </button>
        </div>
    </div>
</div>

        </div>
    </AppLayout>
</template>

<style scoped>
select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0 center;
    background-size: 1em;
}
</style>