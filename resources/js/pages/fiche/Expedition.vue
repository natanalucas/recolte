<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';

const breadcrumbs: BreadcrumbItem[] = [];

// État réactif pour l'en-tête
const agentName = ref('');
const ficheNumber = ref('');

// Données des 24 lignes de palettes
const palettes = reactive(Array.from({ length: 24 }, (_, i) => ({
    id: i + 1,
    numero: '',
    type: '5 kg',
    certif: 'G'
})));

// Infos transport mis à jour avec tous les nouveaux champs
const transport = reactive({
    conteneur: '',
    immatriculation: '',
    propreteConteneur: 'propre',
    propreteCamion: 'propre',
    debutEmpotage: '',
    finEmpotage: '',
    departStation: '',
    arriveePort: '',
    bateau: '',
    bon_livraison: '',
    observations: ''
});
</script>

<template>
    <Head title="Fiche de Traçabilité de l'Expédition" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-6 transition-colors bg-[var(--background)] text-[var(--text)] font-sans space-y-6">
            
            <HeaderFiche 
                title="Fiche de Traçabilité de l'Expédition" 
                v-model:agentName="agentName" 
                v-model:ficheNumber="ficheNumber" 
            />

            <div class="flex justify-between items-center py-2 mt-2 text-sm font-medium">
                <div class="flex gap-8">
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">G</span>
                        <span class="tracking-wide text-xs">Globalgap</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">F</span>
                        <span class="tracking-wide text-xs">Fairtrade</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[12px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]">C</span>
                        <span class="tracking-wide text-xs">Conventionnelle</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4 items-start">
                
                <div class="w-full md:w-[50%] xl:w-[35%] overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                    <table class="w-full text-left border-collapse table-fixed">
                        <thead>
                            <tr class="bg-[var(--brand-green)] text-white text-[12px] font-black uppercase">
                                <th class="px-2 py-3 border-r border-white/10 w-10 text-center">N°</th>
                                <th class="px-3 py-3 border-r border-white/10 text-center w-32">N° Palette</th>
                                <th class="px-2 py-3 border-r border-white/10 text-center w-20">Type de Carton</th>
                                <th class="px-2 py-3 text-center w-16">G/F/C</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--sidebar-border)]">
                            <tr v-for="p in palettes" :key="p.id" class="hover:bg-[var(--brand-green)]/5 transition-colors">
                                <td class="px-2 py-1.5 border-r border-[var(--sidebar-border)] text-center text-[12px] font-bold bg-[var(--card-alt)] text-slate-400 italic">
                                    {{ p.id }}
                                </td>
                                <td class="px-3 py-1.5 border-r border-[var(--sidebar-border)]">
                                    <input v-model="p.numero" type="text" maxlength="6" placeholder="0000" class="w-full bg-transparent outline-none text-[12px] font-mono font-bold text-center focus:text-[var(--brand-green)]">
                                </td>
                                <td class="px-1 py-1.5 border-r border-[var(--sidebar-border)]">
                                    <select v-model="p.type" class="w-full bg-transparent outline-none text-[12px] font-medium cursor-pointer text-center">
                                        <option value="5 kg">5kg</option>
                                        <option value="2.5 kg">2.5kg</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <select v-model="p.certif" class="bg-transparent text-[12px] font-black text-center outline-none cursor-pointer uppercase w-full">
                                        <option>G</option>
                                        <option>F</option>
                                        <option>C</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="w-full md:w-[50%] xl:w-[65%] space-y-6">
                    
                    <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[var(--brand-green)] text-white text-[12px] font-black uppercase">
                                    <th class="px-4 py-3 border-r border-white/10 w-1/2">Numéro Conteneur</th>
                                    <th class="px-4 py-3 w-1/2">N° Immatriculation du Camion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="p-3 border-r border-[var(--sidebar-border)]">
                                        <input v-model="transport.conteneur" type="text" class="w-full bg-transparent outline-none text-sm font-bold uppercase placeholder:opacity-30" placeholder="....">
                                    </td>
                                    <td class="p-3">
                                        <input v-model="transport.immatriculation" type="text" class="w-full bg-transparent outline-none text-sm font-bold uppercase placeholder:opacity-30" placeholder="....">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <br/>
                    <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                        <div class="bg-[var(--brand-green)] px-4 py-2 border-b border-[var(--brand-green)] flex items-center text-white">
                            <span class="text-[12px] font-black uppercase">Vérification :</span>
                        </div>
                        <table class="w-full text-left border-collapse">
                            <tbody class="divide-y divide-[var(--sidebar-border)] text-[12px]">
                                <tr v-for="item in [{l:'du Conteneur', k:'propreteConteneur'}, {l:'du Camion', k:'propreteCamion'}]" :key="item.k">
                                    <td class="px-4 py-4 font-bold text-[12px] uppercase w-1/3 border-r border-[var(--sidebar-border)] bg-[var(--card-alt)]">Propreté {{ item.l }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex gap-8">
                                            <label class="flex items-center gap-2 cursor-pointer group">
                                                <input type="radio" v-model="transport[item.k as keyof typeof transport]" value="propre" class="accent-[var(--brand-green)] w-4 h-4">
                                                <span class="font-bold group-hover:text-[var(--brand-green)]">PROPRE</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer group">
                                                <input type="radio" v-model="transport[item.k as keyof typeof transport]" value="sale" class="accent-red-500 w-4 h-4">
                                                <span class="font-bold">SALE</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <br/>
                    <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                        <table class="w-full border-collapse table-fixed">
                            <tbody>
                                <tr>
                                    <td class="w-1/3 bg-[var(--brand-green)] text-white p-0 m-0 border-r border-white/20">
                                        <div class="flex items-center justify-center text-center font-black uppercase text-[12px] px-4 py-10 h-full leading-tight">
                                            Chargement ou <br> Empotage
                                        </div>
                                    </td>
                                    <td class="w-2/3 p-0 m-0 align-top text-white">
                                        <div class="flex flex-col sm:flex-row h-full">
                                            <div class="flex-1 border-r border-[var(--sidebar-border)] flex flex-col">
                                                <div class="bg-[var(--brand-green)] border-b border-white/10 px-4 py-3 text-center text-[12px] font-black uppercase">
                                                    Début
                                                </div>
                                                <div class="p-4 flex-grow flex items-center bg-[var(--card)]" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                    <input 
                                                        v-model="transport.debutEmpotage" 
                                                        type="datetime-local" 
                                                        class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                                        @input="(e) => (e.target as HTMLInputElement).blur()"
                                                    >
                                                </div>
                                            </div>
                                            <div class="flex-1 flex flex-col">
                                                <div class="bg-[var(--brand-green)] border-b border-white/10 px-4 py-3 text-center text-[12px] font-black uppercase">Fin</div>
                                                <div class="p-4 flex-grow flex items-center bg-[var(--card)]" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                    <input v-model="transport.finEmpotage" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <br/>
                    <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                        <table class="w-full border-collapse table-fixed">
                            <tbody>
                                <tr>
                                    <td class="w-1/3 bg-[var(--brand-green)] text-white p-0 m-0 border-r border-white/20">
                                        <div class="flex items-center justify-center text-center font-black uppercase text-[11px] px-4 py-10 h-full leading-tight">
                                            Départ à la station <br> & Arrivé au port
                                        </div>
                                    </td>
                                    <td class="w-2/3 p-0 m-0 align-top text-white"> 
                                        <div class="flex flex-col sm:flex-row h-full">
                                            <div class="flex-1 border-r border-[var(--sidebar-border)] flex flex-col">
                                                <div class="bg-[var(--brand-green)] border-b border-white/10 px-4 py-3 text-center text-[11px] font-black uppercase">Départ à la station</div>
                                                <div class="p-4 flex-grow flex items-center bg-[var(--card)] cursor-pointer hover:bg-slate-50 transition-colors" @click="$event.currentTarget.querySelector('input').showPicker()"
                                                    >
                                                    <input v-model="transport.departStation" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                                </div>
                                            </div>
                                            <div class="flex-1 flex flex-col">
                                                <div class="bg-[var(--brand-green)] border-b border-white/10 px-4 py-3 text-center text-[11px] font-black uppercase">Arrivé au port</div>
                                                <div class="p-4 flex-grow flex items-center bg-[var(--card)] cursor-pointer hover:bg-slate-50 transition-colors" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                    <input v-model="transport.arriveePort" type="datetime-local" class="w-full bg-transparent outline-none text-[12px] font-bold text-center focus:text-[var(--brand-green)] text-black dark:text-white"
                                                    @input="(e) => (e.target as HTMLInputElement).blur()">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <br/>
                    <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[var(--brand-green)] text-white text-[12px] font-black uppercase">
                                    <th class="px-4 py-3 border-r border-white/10 w-1/2">Nom du bâteau / Transport</th>
                                    <th class="px-4 py-3 w-1/2">Bon de livraison (B.L)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="p-3 border-r border-[var(--sidebar-border)]">
                                        <input v-model="transport.bateau" type="text" class="w-full bg-transparent outline-none text-sm font-bold uppercase placeholder:opacity-30" placeholder="....">
                                    </td>
                                    <td class="p-3">
                                        <input v-model="transport.bon_livraison" type="text" class="w-full bg-transparent outline-none text-sm font-bold uppercase placeholder:opacity-30" placeholder="....">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <br/>
                    <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                        <div class="bg-[var(--brand-green)] px-4 py-2 border-b border-[var(--brand-green)] flex items-center text-white">
                            <span class="text-[12px] font-black uppercase tracking-wider">Observations</span>
                        </div>
                        <div class="p-0">
                            <textarea v-model="transport.observations" rows="4" placeholder="Notes complémentaires..." class="w-full p-4 bg-transparent outline-none text-[12px] font-medium placeholder:opacity-40 placeholder:italic resize-none focus:ring-1 focus:ring-[var(--brand-green)]/20 transition-all"></textarea>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: var(--sidebar-border);
  border-radius: 10px;
}
</style>