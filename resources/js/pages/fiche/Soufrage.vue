<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import { Trash2, Plus } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    // { title: 'Fiche Traçabilité', href: '/fiche-tracabilite' },
    // { title: 'Registre de Soufrage', href: '#' }
];

// 1. Structure de données simplifiée (Traçabilité = un seul champ texte)
interface SoufrageRow {
    id: number;
    cycle: string;
    box: string;
    concent: string;
    parcelle: string;
    code: string; // L'input texte unique
    caissette: number | null;
    soufre: number | null;
    debut: string;
    fin: string;
    operateur: string;
}

const agentName = ref('');
const ficheNumber = ref('');

// 2. État réactif
const rows = reactive<SoufrageRow[]>([
    { 
        id: Date.now(), cycle: '', box: '', concent: '', parcelle: '', 
        code: '', caissette: null, soufre: null, 
        debut: new Date().toISOString().slice(0, 16), 
        fin: '', operateur: '' 
    }
]);

// 3. Actions
const addRow = () => {
    rows.push({
        id: Date.now(),
        cycle: '', box: '', concent: '', parcelle: '',
        code: '', caissette: null, soufre: null,
        debut: new Date().toISOString().slice(0, 16),
        fin: '', operateur: ''
    });
};

const removeRow = (index: number) => {
    if (rows.length > 1) {
        rows.splice(index, 1);
    }
};
</script>

<template>
    <Head title="Registre de Soufrage" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 transition-colors bg-[var(--background)] text-[var(--text)] font-sans">
            
            <HeaderFiche 
                title="Registre de Soufrage" 
                v-model:agentName="agentName" 
                v-model:ficheNumber="ficheNumber" 
            />

            <div class="py-4 text-sm font-medium space-y-4">
                <div class="flex gap-4">
                    <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm flex-1">
                        <div class="space-y-3">
                            <div class="flex items-baseline gap-3">
                                <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Produit traité :</span>
                                <p class="text-[12px] font-medium border-b border-[var(--sidebar-border)]/30 pb-1 flex-1">Litchi, variété Kwai-Mee</p>
                            </div>
                            <div class="flex items-baseline gap-3">
                                <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Phytosanitaire :</span>
                                <p class="text-[12px] font-medium border-b border-[var(--sidebar-border)]/30 pb-1 flex-1">SOUFRE FLEUR à <span class="font-bold text-orange-500">99,9%</span></p>
                            </div>
                            <div class="flex items-baseline gap-3">
                                <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Motif :</span>
                                <p class="text-[12px] font-medium italic flex-1">Conservation du litchi frais</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm flex-1">
                        <div class="space-y-3">
                            <div class="flex items-baseline gap-3">
                                <span class="text-[12px] uppercase tracking-wider font-bold w-33 shrink-0">Lieu de traitement :</span>

                                <input type="text" class="input-line">
                            </div>
                            <div class="flex items-baseline gap-3">
                                <span class="text-[12px] uppercase tracking-wider font-bold w-33 shrink-0">Type de traitement :</span>
                                <p class="text-[12px] font-medium flex-1">À l'anhydride sulfureux par fumigation</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm flex items-baseline gap-3">
                        <span class="text-[12px] uppercase tracking-wider font-bold shrink-0">Contrôle RAQT :</span>
                        <input type="text" placeholder="..." class="input-line" />
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1400px]">
                        <thead>
                            <tr class="text-white bg-[var(--brand-green)] border-b border-[var(--sidebar-border)] text-[11px] font-black uppercase">
                                <th rowspan="2" class="px-2 w-10 sticky left-0"></th>
                                <th class="px-4 py-3 border-r border-white/10 sticky left-0 bg-[var(--brand-green)] z-20 w-32 text-[12px] text-center">Cycle</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">N° Box</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Concent.</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Parcelle</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center w-64">Code Traçabilité</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Qté Caissette</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Qté Soufre (g)</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Début</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Fin</th>
                                <th class="px-4 py-4">Opérateur</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--sidebar-border)]">
                            <tr v-for="(row, index) in rows" :key="row.id" class="hover:bg-[var(--brand-green)]/5 transition-colors tr-row">
                                <td class="px-2 text-center">
                                    <button @click="removeRow(index)" 
                                        class="text-red-400 hover:text-red-600 transition-all p-1">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                                <td class="px-4 py-4 sticky left-0 bg-[var(--card)] z-10 border-r border-[var(--sidebar-border)]">
                                    <input v-model="row.cycle" type="text" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.box" type="number" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.concent" type="text" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.parcelle" type="number" class="input-line">
                                </td>
                                <td class="px-4 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <select v-model="row.code" class="input-line">
                                        <option value="gasy_fruits">Station de conditionnement GASY FRUITS</option>
                                    </select>
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.caissette" type="number" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.soufre" type="number" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50 cursor-pointer" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <input v-model="row.debut" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50 cursor-pointer" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <input v-model="row.fin" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                </td>
                                <td class="px-4 py-4">

                                    <select v-model="row.operateur" class="input-line">
                                        <option value="gasy_fruits">Nom producteur</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <button @click="addRow" class="mt-4 h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all">
                <Plus class="w-4 h-4" /> Ajouter une ligne de soufrage
            </button>
        </div>
    </AppLayout>
</template>