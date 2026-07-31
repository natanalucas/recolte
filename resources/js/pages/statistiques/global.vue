<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ChevronDown, User, Package, Truck, Ship, Calendar, Box, Layers } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [];

// Types
interface ProducteurStat {
    id: number;
    nom: string;
    prenom: string;
    total_quantite: number;
    dernier_retour: string | null;
    qualite_moyenne: number | null;
}

interface SoufrageDetail {
    box: string | number | null;
    debut: string | null;
    fin: string | null;
    concent: string | null;
    soufre: number | null;
    code_traca: string | null;
}

interface TriageDetail {
    tapis: number[] | null;
    debut: string | null;
    fin: string | null;
    qualite: number;
    nombre: number | null;
}

interface ExpeditionAssociee {
    immatriculation: string | null;
    bateau: string | null;
    debut_empotage: string | null;
    fin_empotage: string | null;
    depart_station: string | null;
    arrivee_port: string | null;
}

interface PaletteAvecExpedition {
    num_palette: string | number;
    date_debut: string | null;
    date_fin: string | null;
    nb_cartons: number;
    certification: string | null;
    expedition: ExpeditionAssociee | null;
}

interface ParcoursEtape {
    date_livraison: string | null;
    quantite_kg: number;
    parcelle: string | null;
    soufrage: SoufrageDetail | null;
    triages: TriageDetail[];
    palettes: PaletteAvecExpedition[];
}

interface ProducteurDetail {
    id: number;
    nom: string;
    prenom: string;
    parcours: ParcoursEtape[];
    totalProducteur: number;
}

const props = defineProps<{
    producteurs: ProducteurStat[];
    totalGeneral: number;
    producteurDetail: ProducteurDetail | null;
}>();

// Fonctions de formatage
const fmtDate = (d: string | null) => {
    if (!d) return '—';
    return new Date(d).toLocaleString('fr-FR', { 
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const fmtNumber = (n: number) => {
    return n.toFixed(2) + ' kg';
};

// Gestion du lien vers le détail d'un producteur
const goToProducteur = (id: number) => {
    router.get(route('statistiques.index', { slug: id }), {}, { preserveScroll: true });
};

const selectedProducteurName = computed(() => {
    if (!props.producteurDetail) return null;
    return `${props.producteurDetail.prenom} ${props.producteurDetail.nom}`;
});
</script>

<template>
    <Head title="Statistiques" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 bg-[var(--background)] text-[var(--text)] font-sans">

            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-black uppercase tracking-widest flex items-center gap-3">
                    <Layers class="w-6 h-6 text-[var(--brand-green)]" />
                    Statistiques de traçabilité
                </h1>
                <div v-if="selectedProducteurName" class="text-sm font-bold bg-[var(--brand-green)]/10 px-4 py-2 rounded-full border border-[var(--brand-green)]/20">
                    Producteur sélectionné : {{ selectedProducteurName }}
                </div>
            </div>

            <!-- Section 1 : Liste des producteurs -->
            <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-[var(--brand-green)] flex items-center justify-between">
                    <h2 class="text-[13px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                        <User class="w-4 h-4" /> Producteurs – Indicateurs
                    </h2>
                    <span class="text-white/70 text-[11px] font-bold uppercase tracking-widest">
                        Total général : {{ fmtNumber(totalGeneral) }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[var(--brand-green)]/10 text-[10px] font-black uppercase border-b border-[var(--sidebar-border)]">
                                <th class="px-4 py-3">Producteur</th>
                                <th class="px-4 py-3 text-center">Dernière livraison</th>
                                <th class="px-4 py-3 text-center">Quantité totale (kg)</th>
                                <th class="px-4 py-3 text-center">Qualité moyenne</th>
                                <th class="px-4 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--sidebar-border)]">
                            <tr v-for="prod in producteurs" :key="prod.id" class="hover:bg-[var(--brand-green)]/5 transition-colors text-[12px]">
                                <td class="px-4 py-3 font-bold">
                                    {{ prod.prenom }} {{ prod.nom }}
                                </td>
                                <td class="px-4 py-3 text-center text-[11px]">
                                    {{ fmtDate(prod.dernier_retour) }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-bold">
                                    {{ fmtNumber(prod.total_quantite) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="prod.qualite_moyenne !== null" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[var(--brand-orange)]/10 text-[var(--brand-orange)]">
                                        {{ prod.qualite_moyenne.toFixed(1) }}/3
                                    </span>
                                    <span v-else class="opacity-30">—</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        @click="goToProducteur(prod.id)"
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-[var(--sidebar-border)] text-[10px] font-black uppercase hover:border-[var(--brand-green)] hover:text-[var(--brand-green)] transition-all"
                                    >
                                        <ChevronDown class="w-3 h-3" /> Détails
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="producteurs.length === 0">
                                <td colspan="5" class="py-8 text-center text-[12px] font-bold opacity-30 uppercase tracking-widest">
                                    Aucun producteur trouvé
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 2 : Détails du producteur sélectionné -->
            <div v-if="producteurDetail" class="space-y-8">
                <hr class="border-[var(--sidebar-border)]" />

                <!-- En-tête producteur -->
                <div class="flex items-center gap-4 bg-[var(--brand-green)]/5 p-4 rounded-xl border border-[var(--brand-green)]/20">
                    <div class="p-3 bg-[var(--brand-green)] rounded-full text-white">
                        <User class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black uppercase tracking-widest">
                            {{ producteurDetail.prenom }} {{ producteurDetail.nom }}
                        </h2>
                        <p class="text-sm opacity-60">
                            Total livré : {{ fmtNumber(producteurDetail.totalProducteur) }}
                        </p>
                    </div>
                    <button
                        @click="router.get(route('statistiques.index'), {}, { preserveScroll: true })"
                        class="ml-auto text-[11px] font-black uppercase px-4 py-2 border border-[var(--sidebar-border)] rounded-xl hover:bg-[var(--sidebar-border)]/20 transition"
                    >
                        Retour à la liste
                    </button>
                </div>

                <!-- Parcours complet -->
                <div>
                    <h3 class="text-[13px] font-black uppercase tracking-widest flex items-center gap-2 mb-4">
                        <Layers class="w-4 h-4 text-[var(--brand-green)]" />
                        Parcours complet (Réception → Soufrage → Triage → Palette → Expédition)
                    </h3>

                    <div v-for="(etape, index) in producteurDetail.parcours" :key="index" class="mb-6 bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl shadow-sm overflow-hidden">
                        <!-- En-tête du lot -->
                        <div class="px-6 py-3 bg-[var(--brand-green)]/10 border-b border-[var(--sidebar-border)] flex flex-wrap items-center justify-between">
                            <div class="flex items-center gap-4">
                                <span class="text-[11px] font-black uppercase tracking-widest">Lot #{{ index+1 }}</span>
                                <span class="text-[12px] font-bold">{{ fmtDate(etape.date_livraison) }}</span>
                                <span class="text-[12px] font-mono bg-[var(--brand-green)]/20 px-2 py-0.5 rounded-full">{{ etape.quantite_kg.toFixed(2) }} kg</span>
                                <span class="text-[11px] opacity-60">Parcelle {{ etape.parcelle ?? '—' }}</span>
                            </div>
                            <div class="flex gap-2 text-[10px] font-black uppercase">
                                <span v-if="etape.soufrage" class="px-2 py-0.5 bg-[var(--brand-orange)]/10 text-[var(--brand-orange)] rounded-full">Soufré</span>
                                <span v-if="etape.triages.length" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-full">Trié</span>
                                <span v-if="etape.palettes.length" class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">Palettisé</span>
                                <span v-if="etape.palettes.some(p => p.expedition)" class="px-2 py-0.5 bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-full">Expédié</span>
                            </div>
                        </div>

                        <!-- Corps : étapes verticales -->
                        <div class="p-4 space-y-4">
                            <!-- 1. Réception -->
                            <div class="border-l-4 border-[var(--brand-green)] pl-3">
                                <div class="text-[11px] font-black uppercase tracking-widest text-[var(--brand-green)]">📦 Réception</div>
                                <div class="text-[12px] grid grid-cols-1 sm:grid-cols-3 gap-1 mt-1">
                                    <div><span class="opacity-60">Date :</span> {{ fmtDate(etape.date_livraison) }}</div>
                                    <div><span class="opacity-60">Quantité :</span> {{ etape.quantite_kg.toFixed(2) }} kg</div>
                                    <div><span class="opacity-60">Parcelle :</span> {{ etape.parcelle ?? '—' }}</div>
                                </div>
                            </div>

                            <!-- 2. Soufrage -->
                            <div v-if="etape.soufrage" class="border-l-4 border-[var(--brand-orange)] pl-3">
                                <div class="text-[11px] font-black uppercase tracking-widest text-[var(--brand-orange)]">🧪 Soufrage</div>
                                <div class="text-[12px] grid grid-cols-1 sm:grid-cols-3 gap-1 mt-1">
                                    <div><span class="opacity-60">Box :</span> #{{ etape.soufrage.box ?? '?' }}</div>
                                    <div><span class="opacity-60">Début :</span> {{ etape.soufrage.debut ?? '—' }}</div>
                                    <div><span class="opacity-60">Fin :</span> {{ etape.soufrage.fin ?? '—' }}</div>
                                    <div><span class="opacity-60">Concentration :</span> {{ etape.soufrage.concent ?? '—' }}</div>
                                    <div><span class="opacity-60">Soufre :</span> {{ etape.soufrage.soufre ?? '—' }} g</div>
                                    <div><span class="opacity-60">Code traçabilité :</span> {{ etape.soufrage.code_traca ?? '—' }}</div>
                                </div>
                            </div>
                            <div v-else class="text-[12px] opacity-40 italic pl-3">Pas de soufrage associé</div>

                            <!-- 3. Triages -->
                            <div v-if="etape.triages.length" class="border-l-4 border-emerald-500 pl-3">
                                <div class="text-[11px] font-black uppercase tracking-widest text-emerald-600">🔍 Triage</div>
                                <div v-for="(t, idx) in etape.triages" :key="idx" class="text-[12px] mt-2 border-t border-[var(--sidebar-border)]/30 pt-2 first:border-0 first:pt-0">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                                        <div><span class="opacity-60">Tapis :</span> 
                                            <span v-if="t.tapis && t.tapis.length" class="inline-flex gap-1">
                                                <span v-for="num in t.tapis.slice().sort((a,b)=>a-b)" :key="num" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-[var(--brand-green)]/10 text-[var(--brand-green)]">T{{ num }}</span>
                                            </span>
                                            <span v-else>—</span>
                                        </div>
                                        <div><span class="opacity-60">Début :</span> {{ t.debut ?? '—' }}</div>
                                        <div><span class="opacity-60">Fin :</span> {{ t.fin ?? '—' }}</div>
                                        <div><span class="opacity-60">Qualité :</span> <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-[var(--brand-orange)]/10 text-[var(--brand-orange)]">{{ t.qualite }}/3</span></div>
                                        <div v-if="t.nombre"><span class="opacity-60">Cartons :</span> {{ t.nombre }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-[12px] opacity-40 italic pl-3">Pas de triage</div>

                            <!-- 4. Palettes (avec expéditions) -->
                            <div v-if="etape.palettes.length" class="border-l-4 border-blue-500 pl-3">
                                <div class="text-[11px] font-black uppercase tracking-widest text-blue-600">📦 Palettes & Expéditions</div>
                                <div v-for="(p, idx) in etape.palettes" :key="idx" class="text-[12px] mt-2 border-t border-[var(--sidebar-border)]/30 pt-2 first:border-0 first:pt-0">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                                        <div><span class="opacity-60">Palette :</span> #{{ p.num_palette }}</div>
                                        <div><span class="opacity-60">Début :</span> {{ p.date_debut ?? '—' }}</div>
                                        <div><span class="opacity-60">Fin :</span> {{ p.date_fin ?? '—' }}</div>
                                        <div><span class="opacity-60">Cartons :</span> {{ p.nb_cartons }}</div>
                                        <div v-if="p.certification"><span class="opacity-60">Certification :</span> <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ p.certification }}</span></div>
                                    </div>
                                    <!-- Expédition associée -->
                                    <div v-if="p.expedition" class="mt-1 ml-4 pl-3 border-l-2 border-purple-300">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-purple-600">🚢 Expédition</div>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                                            <div><span class="opacity-60">Camion :</span> {{ p.expedition.immatriculation ?? '—' }}</div>
                                            <div><span class="opacity-60">Bateau :</span> {{ p.expedition.bateau ?? '—' }}</div>
                                            <div><span class="opacity-60">Empotage :</span> {{ p.expedition.debut_empotage ?? '—' }} → {{ p.expedition.fin_empotage ?? '—' }}</div>
                                            <div><span class="opacity-60">Départ station :</span> {{ p.expedition.depart_station ?? '—' }}</div>
                                            <div><span class="opacity-60">Arrivée port :</span> {{ p.expedition.arrivee_port ?? '—' }}</div>
                                        </div>
                                    </div>
                                    <div v-else class="mt-1 text-[11px] opacity-40 italic">Pas d'expédition pour cette palette</div>
                                </div>
                            </div>
                            <div v-else class="text-[12px] opacity-40 italic pl-3">Pas de palette</div>
                        </div>
                    </div>

                    <div v-if="producteurDetail.parcours.length === 0" class="text-center py-8 opacity-40">
                        Aucune donnée de parcours pour ce producteur.
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>