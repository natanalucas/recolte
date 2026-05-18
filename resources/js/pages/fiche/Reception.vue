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
interface TriageRow {
    id: number;
    numero_parcelle: string;
    voiture: string;
    commune: string;
    caissette: number | null;
    debut: string;
    fin: string;
    reception: string;
}

const agentName = ref('');
const ficheNumber = ref('');

// 2. État réactif
const rows = reactive<TriageRow[]>([
    { 
        id: Date.now(), numero_parcelle: '', voiture: '', commune: '', 
        caissette: null, reception: '',
        debut: new Date().toISOString().slice(0, 16), 
        fin: ''
    }
]);

// 3. Actions
const addRow = () => {
    rows.push({
        id: Date.now(),
        numero_parcelle: '', voiture: '', commune: '',
        caissette: null, reception: '',
        debut: new Date().toISOString().slice(0, 16),
        fin: ''
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
                title="Fiche traçabilité de la réception" 
                v-model:agentName="agentName" 
                v-model:ficheNumber="ficheNumber" 
            />

            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1400px]">
                        <thead>
                            <tr class="bg-[var(--brand-green)] text-white border-b border-[var(--sidebar-border)] text-[11px] font-black uppercase">
                                <th rowspan="2" class="px-2 w-10 sticky left-0"></th>
                                <th class="px-4 py-3 border-r border-white/10 sticky left-0 bg-[var(--brand-green)] z-20 w-32 text-[12px] text-center">Numéro parcelle</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Qtté Caissette Livrée</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">N° Immatriculation Voiture</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Commune et District</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Collecte</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Départ au champ</th>
                                <th class="px-4 py-4 border-r border-[var(--sidebar-border)] text-center">Retour à la station</th>
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
                                    <input v-model="row.numero_parcelle" type="text" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.caissette" type="number" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.voiture" type="text" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50">
                                    <input v-model="row.commune" type="number" class="input-line">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50 cursor-pointer" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <input v-model="row.debut" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50 cursor-pointer" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <input v-model="row.fin" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                </td>
                                <td class="px-2 py-4 border-r border-[var(--sidebar-border)]/50 cursor-pointer" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <input v-model="row.reception" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <button @click="addRow" class="mt-4 h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all">
                <Plus class="w-4 h-4" /> Ajouter une ligne de reception
            </button>
        </div>
    </AppLayout>
</template>