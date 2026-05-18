<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import { Trash2, Plus } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    // { title: 'Fiche Traçabilité', href: '#' },
    // { title: 'Palettisation', href: '#' }
];

// 1. Structure de données personnalisée pour la Palettisation
interface Lot {
    code: string;
    nbCartons: number | null;
}

interface PaletisationRow {
    id: number;
    numPalette: string;
    typeCartonPoids: string; // '2.5' | '5'
    typeCartonCertif: string; // 'F' | 'G' | 'C'
    debut: string;
    fin: string;
    lot1: Lot;
    lot2: Lot;
    lot3: Lot;
}

// 2. État réactif
const agentName = ref('');
const ficheNumber = ref('');

const rows = reactive<PaletisationRow[]>([
    createNewRow()
]);

function createNewRow(): PaletisationRow {
    return {
        id: Date.now(),
        numPalette: '',
        typeCartonPoids: '5',
        typeCartonCertif: 'G',
        debut: new Date().toISOString().slice(0, 16),
        fin: '',
        lot1: { code: '', nbCartons: null },
        lot2: { code: '', nbCartons: null },
        lot3: { code: '', nbCartons: null }
    };
}

// 3. Actions
const addRow = () => {
    rows.push(createNewRow());
};

const removeRow = (index: number) => {
    if (rows.length > 1) {
        rows.splice(index, 1);
    }
};

// Calcul du total par ligne
const rowTotal = (row: PaletisationRow) => {
    return (row.lot1.nbCartons || 0) + (row.lot2.nbCartons || 0) + (row.lot3.nbCartons || 0);
};
</script>

<template>
    <Head title="Palettisation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 transition-colors bg-[var(--background)] text-[var(--text)] font-sans">
            
            <HeaderFiche 
                title="Fiche de traçabilité du paletisation" 
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
                        <span class="text-[12px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">F</span>
                        <span class="tracking-wide">Fairtrade</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">C</span>
                        <span class="tracking-wide">Conventionnelle</span>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1500px]">
                        <thead>
                            <tr class="bg-[var(--brand-green)] text-white text-[11px] font-black uppercase">
                                <th rowspan="2" class="px-2 w-10"></th>
                                <th rowspan="2" class="px-4 py-3 border-r border-white/10 sticky left-0 bg-[var(--brand-green)] z-20 w-32 text-[12px] text-center">
                                    N° Palette
                                </th>
                                <th colspan="5" class="px-4 py-3 border-r border-white/10 text-center text-[12px]">Type de carton</th>
                                <th rowspan="2" class="px-4 py-3 border-r border-white/10 text-center w-32 text-[12px]">Début</th>
                                <th rowspan="2" class="px-4 py-3 border-r border-white/10 text-center w-32 text-[12px]">Fin</th>
                                <th colspan="2" class="px-4 py-3 border-l border-white/10 text-center text-[12px]">1er Lot</th>
                                <th colspan="2" class="px-4 py-3 border-l border-white/10 text-center text-[12px]">2ème Lot</th>
                                <th colspan="2" class="px-4 py-3 border-l border-white/10 text-center text-[12px]">3ème Lot</th>
                                <th rowspan="2" class="px-2 py-3 border-l border-white/10 text-center w-20 text-[12px] bg-black/10">
                                    Total de Carton
                                </th>
                            </tr>

                            <tr class="bg-[var(--brand-green)]/10 border-b border-[var(--brand-green)]/20 text-[10px] font-black uppercase">
                                <th colspan="2" class="px-2 text-center border-r border-[var(--brand-green)]/20 text-[var(--brand-orange)]">Poids</th>
                                <th colspan="3" class="px-2 text-center border-r border-[var(--brand-green)]/20 text-[var(--brand-orange)]">Certif.</th>
                                
                                <th class="px-4 py-2 border-l border-[var(--brand-green)]/20 text-center w-44 text-[var(--brand-orange)]">Code Traça</th>
                                <th class="px-4 py-2 border-l border-[var(--brand-green)]/20 text-center w-24 text-[var(--brand-orange)]">Nb. Cartons</th>
                                
                                <th class="px-4 py-2 border-l border-[var(--brand-green)]/20 text-center w-44 text-[var(--brand-orange)]">Code Traça</th>
                                <th class="px-4 py-2 border-l border-[var(--brand-green)]/20 text-center w-24 text-[var(--brand-orange)]">Nb. Cartons</th>
                                
                                <th class="px-4 py-2 border-l border-[var(--brand-green)]/20 text-center w-44 text-[var(--brand-orange)]">Code Traça</th>
                                <th class="px-4 py-2 border-l border-[var(--brand-green)]/20 text-center w-24 text-[var(--brand-orange)]">Nb. Cartons</th>
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
                                    <input v-model="row.numPalette" type="text" class="bg-transparent border-b border-[var(--sidebar-border)] focus:border-[var(--brand-green)] outline-none pb-1 text-[12px] w-full font-bold uppercase">
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

                                <td class="px-2 py-2 border-r border-[var(--sidebar-border)]/50 text-center"
                                @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <input 
                                        type="datetime-local" 
                                        v-model="row.debut" 
                                        class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                        @input="(e) => (e.target as HTMLInputElement).blur()"
                                    >
                                </td>
                                <td class="px-2 py-2 border-r border-[var(--sidebar-border)]/50 text-center"
                                @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <input 
                                        type="datetime-local" 
                                        v-model="row.debut" 
                                        class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                        @input="(e) => (e.target as HTMLInputElement).blur()"
                                    >
                                </td>

                                <td class="px-3 py-2 border-l border-[var(--sidebar-border)]">
                                    <input v-model="row.lot1.code" type="text" placeholder="CODE..." class="bg-transparent border-b border-[var(--sidebar-border)]/20 text-[12px] w-full uppercase font-mono focus:border-[var(--brand-green)] outline-none text-center">
                                </td>
                                <td class="px-3 py-2 border-l border-[var(--sidebar-border)]/30 bg-black/5">
                                    <input v-model="row.lot1.nbCartons" type="number" placeholder="0" class="bg-transparent border-b border-[var(--sidebar-border)]/20 text-[12px] w-full uppercase font-mono focus:border-[var(--brand-green)] outline-none text-center">
                                </td>

                                <td class="px-3 py-2 border-l border-[var(--sidebar-border)]">
                                    <input v-model="row.lot2.code" type="text" placeholder="CODE..." class="bg-transparent border-b border-[var(--sidebar-border)]/20 text-[12px] w-full uppercase font-mono focus:border-[var(--brand-green)] outline-none text-center">
                                </td>
                                <td class="px-3 py-2 border-l border-[var(--sidebar-border)]/30 bg-black/5">
                                    <input v-model="row.lot2.nbCartons" type="number" placeholder="0" class="bg-transparent border-b border-[var(--sidebar-border)]/20 text-[12px] w-full uppercase font-mono focus:border-[var(--brand-green)] outline-none text-center">
                                </td>

                                <td class="px-3 py-2 border-l border-[var(--sidebar-border)]">
                                    <input v-model="row.lot3.code" type="text" placeholder="CODE..." class="bg-transparent border-b border-[var(--sidebar-border)]/20 text-[12px] w-full uppercase font-mono focus:border-[var(--brand-green)] outline-none text-center">
                                </td>
                                <td class="px-3 py-2 border-l border-[var(--sidebar-border)]/30 bg-black/5">
                                    <input v-model="row.lot3.nbCartons" type="number" placeholder="0" class="bg-transparent border-b border-[var(--sidebar-border)]/20 text-[12px] w-full uppercase font-mono focus:border-[var(--brand-green)] outline-none text-center">
                                </td>

                                <td class="px-2 py-4 border-l border-[var(--sidebar-border)] text-center font-black text-[12px]">
                                    {{ rowTotal(row) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <button @click="addRow" class="mt-4 h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all">
                <Plus class="w-4 h-4" /> Ajouter une palette
            </button>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Supprimer les flèches des inputs number */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>