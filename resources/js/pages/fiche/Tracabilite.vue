<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import HeaderFiche from './HeaderFiche.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Fiche Traçabilité', href: '/fiche-tracabilite' },
];

// 1. Structure de données pour une ligne
interface TriageRow {
    id: number;
    code: string;
    poids: string; // '2.5' | '5'
    certif: string; // 'F' | 'G'
    debut: string;
    fin: string;
    tapis: number | null; // Index du tapis sélectionné (0-13)
    nombre: number | null;
    qualite: number;
}

// 2. État réactif
const agentName = ref('');
const ficheNumber = ref('');
const rows = reactive<TriageRow[]>([
    { id: Date.now(), code: '', poids: '5', certif: 'G', debut: '', fin: '', tapis: null, nombre: null, qualite: 1 }
]);

// 3. Actions
const addRow = () => {
    rows.push({
        id: Date.now(),
        code: '',
        poids: '5',
        certif: 'G',
        debut: new Date().toISOString().slice(0, 16),
        fin: '',
        tapis: null,
        nombre: null,
        qualite: 1
    });
};

const removeRow = (index: number) => {
    if (rows.length > 1) rows.splice(index, 1);
};
</script>

<template>
    <Head title="Fiche Traçabilité" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 transition-colors bg-[var(--background)] text-[var(--text)] font-sans">
            
            <HeaderFiche 
                title="Fiche de traçabilité du triage" 
                v-model:agentName="agentName" 
                v-model:ficheNumber="ficheNumber" 
            />
            <div class="flex justify-between items-center py-4 mt-2 text-sm font-medium">
                    <div class="flex gap-8">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">G</span>
                            <span class="tracking-wide">Globalgap</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">F</span>
                            <span class="tracking-wide">Fairtrade</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">C</span>
                            <span class="tracking-wide">Conventionnelle</span>
                        </div>
                    </div>

                    <div class="italic flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full"></span>
                        * Qualité de soufrage : <span class="font-bold">1 à 3</span>
                    </div>
                </div>
            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1400px]">
                        <thead>
                            <tr class="bg-[var(--card-alt)] border-b border-[var(--sidebar-border)] text-[11px] font-bold uppercase">
                                <th rowspan="2" class="px-4 py-3 border-r border-[var(--sidebar-border)] left-0 bg-[var(--card-alt)] z-10 w-32">Code de traçabilité</th>
                                
                                <th colspan="5" class="px-4 py-3 border-r border-[var(--sidebar-border)] text-center">Type de carton</th>
                                
                                <th rowspan="2" class="px-4 py-3 border-r border-[var(--sidebar-border)] text-center">Début de triage</th>
                                <th rowspan="2" class="px-4 py-3 border-r border-[var(--sidebar-border)] text-center">Fin de triage</th>
                                <th colspan="14" class="px-4 py-2 text-center border-b border-[var(--sidebar-border)] bg-black/5 dark:bg-white/5">N° du tapis utilisé</th>
                                <th rowspan="2" class="px-4 py-3 border-l border-[var(--sidebar-border)] text-center">Nombre de carton</th>
                                <th rowspan="2" class="px-4 py-3 text-center">Qualité de Soufrage</th>
                                <th rowspan="2" class="px-2"></th>
                            </tr>
                            <tr class="bg-[var(--card-alt)] border-b border-[var(--sidebar-border)] text-[9px] opacity-60">
                                <th colspan="5" class="border-r border-[var(--sidebar-border)]"></th>
                                <th v-for="n in 14" :key="n" class="px-1 py-2 text-center border-r border-[var(--sidebar-border)] w-10">T{{ n }}</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--sidebar-border)]">
                            <tr v-for="(row, index) in rows" :key="row.id" class="hover:bg-blue-500/5 transition-colors">
                                <td class="px-4 py-4 sticky left-0 focus:border-[var(--brand-green)] z-10 border-r border-[var(--sidebar-border)]">
                                    <input v-model="row.code" type="text" class="bg-transparent border-b border-[var(--sidebar-border)] focus:border-[var(--brand-green)] outline-none pb-1 text-[12px] w-full">
                                </td>

                                <td v-for="p in ['2.5', '5']" :key="p" class="px-2 py-4 border-r border-[var(--sidebar-border)]/20 text-center">
                                    <label class="flex items-center justify-center gap-1 cursor-pointer">
                                        <input type="radio" :name="'poids_' + row.id" :value="p" v-model="row.poids" class="radio-green">
                                        <span class="text-[12px] font-medium">{{ p }}kg</span>
                                    </label>
                                </td>

                                <td v-for="c in ['F', 'G', 'C']" :key="c" class="px-2 py-4 border-r border-[var(--sidebar-border)]/20 text-center">
                                    <label class="flex items-center justify-center gap-1 cursor-pointer">
                                        <input type="radio" :name="'certif_' + row.id" :value="c" v-model="row.certif" class="radio-green">
                                        <span class="text-[12px] font-bold">{{ c }}</span>
                                    </label>
                                </td>

                                <td class="px-2 py-2 border-r border-[var(--sidebar-border)]/50 text-center cursor-pointer hover:bg-[var(--brand-green)]/5" 
                                    @click="$event.currentTarget.querySelector('input').showPicker()">
                                    
                                    <input 
                                        type="datetime-local" 
                                        v-model="row.debut" 
                                        class="bg-transparent border-none text-[12px] p-0 w-full text-center focus:ring-0 pointer-events-none"
                                    >
                                </td>

                                <td class="px-2 py-2 border-r border-[var(--sidebar-border)]/50 text-center cursor-pointer hover:bg-[var(--brand-green)]/5" 
                                    @click="$event.currentTarget.querySelector('input').showPicker()">
                                    
                                    <input 
                                        type="datetime-local" 
                                        v-model="row.fin" 
                                        class="bg-transparent border-none text-[12px] p-0 w-full text-center focus:ring-0 pointer-events-none"
                                    >
                                </td>

                               

                                <td v-for="n in 14" :key="n" class="px-0 py-0 border-r border-[var(--sidebar-border)]/20 text-center">
                                    <label class="w-full h-full block py-4 cursor-pointer hover:bg-blue-500/10">
                                        <input type="radio" :name="'tapis_' + row.id" :value="n" v-model="row.tapis" class="radio-green">
                                    </label>
                                </td>

                                <td class="px-2 py-4 border-l border-[var(--sidebar-border)]">
                                    <input v-model="row.nombre" type="number" class="bg-transparent border-b border-[var(--sidebar-border)] focus:border-[var(--brand-green)] outline-none pb-1 text-[12px] w-full">
                                </td>

                                <td class="px-2 py-4">
                                    <select v-model="row.qualite" class="bg-transparent border-none text-[12px] w-full text-center focus:ring-0 p-0 cursor-pointer">
                                        <option v-for="q in [1, 2, 3]" :key="q" :value="q">{{ q }}</option>
                                    </select>
                                </td>

                                <td class="px-2 text-center">
                                    <button @click="removeRow(index)" class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <button @click="addRow" class="mt-4 px-4 py-2 bg-[var(--brand-green)] text-white rounded-lg text-sm font-bold">
                + Ajouter une ligne de triage
            </button>
        </div>
    </AppLayout>
</template>