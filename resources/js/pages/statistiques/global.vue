<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    User, Layers, Ship, Sprout, MapPin, PackageX, TriangleAlert,
    CheckCircle2, ArrowLeft, Search, ArrowUpDown, ChevronRight,
    Building2, Phone, MapPinned,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [];

/* ---------- Types ---------- */
interface Societe {
    id: number;
    nom: string;
    adresse: string | null;
    phone: string | null;
    logo_url: string | null;
    nb_producteurs?: number;
    nb_fiches?: number;
}

interface ProducteurStat {
    id: number;
    nom: string;
    prenom: string;
    total_kg: number;
    nb_caissettes: number;
    mur_kg: number;
    dernier_retour: string | null;
    nb_fiches: number;
    fiches_non_palettisees: number;
    palettes_expediees: number;
    palettes_non_expediees: number;
}

interface GlobalStat {
    total_kg: number;
    mur_kg: number;
    nb_caissettes: number;
    nb_producteurs: number;
    nb_fiches: number;
    fiches_non_palettisees: number;
    palettes_expediees: number;
    palettes_non_expediees: number;
    calibre_min: number;
}

interface LotLigne {
    num_palette: string | number | null;
    type_carton: string | null;
    lot_number: string | number | null;
    nb_cartons: number;
    certifications: string[];
    bateau: string | null;
    expedie: boolean;
}

interface Reception {
    id: number;
    fiche_number: string | null;
    retour_station: string | null;
    quantite_kg: number;
    caissettes: number;
    poids_caissette: number;
    qualite: string | null;
    calibre: number | null;
    mur_calibre: boolean;
    palettise: boolean;
    lots: LotLigne[];
}

interface ParcelleStat {
    id: number;
    num: string | number;
    total_kg: number;
    mur_kg: number;
    nb_caissettes: number;
    fiches_non_palettisees: number;
    receptions: Reception[];
}

interface ProducteurDetail {
    id: number;
    nom: string;
    prenom: string;
    total_kg: number;
    mur_kg: number;
    nb_caissettes: number;
    nb_fiches: number;
    fiches_non_palettisees: number;
    palettes: number;
    palettes_expediees: number;
    palettes_non_expediees: number;
    parcelles: ParcelleStat[];
}

const props = defineProps<{
    isAdmin: boolean;
    societes: Societe[];
    societe: Societe | null;
    producteurs: ProducteurStat[];
    global: GlobalStat | null;
    producteurDetail: ProducteurDetail | null;
}>();

/* ---------- Formatage ---------- */
const fmtDate = (d: string | null, withTime = false) => {
    if (!d) return null;
    const opts: Intl.DateTimeFormatOptions = withTime
        ? { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }
        : { day: '2-digit', month: '2-digit', year: 'numeric' };
    return new Date(d).toLocaleString('fr-FR', opts);
};

const fmtKg = (n: number) => {
    const decimals = Math.abs(n) >= 100 ? 0 : 1;
    return n.toLocaleString('fr-FR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + ' kg';
};

const fmtCalibre = (n: number | null) =>
    n === null ? '—' : n.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + ' mm';

const pct = (part: number, total: number) => (total > 0 ? Math.round((part / total) * 100) : 0);
const nomComplet = (p: { prenom: string; nom: string }) => `${p.prenom} ${p.nom}`;
const calibreMin = computed(() => props.global?.calibre_min ?? 0);

/* ---------- Palette sémantique ---------- */
const chipAlert = (n: number) =>
    n > 0
        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';

const qualiteClass = (r: Reception) =>
    r.mur_calibre
        ? 'bg-[var(--brand-green)]/15 text-[var(--brand-green)]'
        : 'bg-[var(--brand-orange)]/10 text-[var(--brand-orange)]';

/* ---------- Navigation par query params ---------- */
const societesUrl = () => route('statistiques.index');

const societeUrl = (id: number) =>
    route('statistiques.index', { societe: id });

const currentSocieteUrl = () =>
    props.isAdmin && props.societe
        ? route('statistiques.index', { societe: props.societe.id })
        : route('statistiques.index');

const producteurUrl = (id: number) => {
    const params: Record<string, any> = { producteur: id };
    if (props.isAdmin && props.societe) params.societe = props.societe.id;
    return route('statistiques.index', params);
};

const goToSociete = (id: number) =>
    router.get(societeUrl(id), {}, { preserveScroll: true });

const goToProducteur = (id: number) =>
    router.get(producteurUrl(id), {}, { preserveScroll: true });

/* ---------- Recherche + tri ---------- */
const search = ref('');
const sortKey = ref<keyof ProducteurStat>('total_kg');
const sortDir = ref<'asc' | 'desc'>('desc');
const onlyAlerts = ref(false);

const toggleSort = (key: keyof ProducteurStat) => {
    if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    else { sortKey.value = key; sortDir.value = 'desc'; }
};

const filteredSorted = computed(() => {
    const q = search.value.trim().toLowerCase();
    let list = [...props.producteurs];
    if (q) list = list.filter(p => nomComplet(p).toLowerCase().includes(q));
    if (onlyAlerts.value) list = list.filter(p => p.fiches_non_palettisees > 0 || p.palettes_non_expediees > 0);

    const dir = sortDir.value === 'asc' ? 1 : -1;
    const key = sortKey.value;
    list.sort((a, b) => {
        const va = a[key], vb = b[key];
        if (va === null) return 1;
        if (vb === null) return -1;
        if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir;
        return String(va).localeCompare(String(vb)) * dir;
    });
    return list;
});

const totals = computed(() => {
    const list = filteredSorted.value;
    return {
        total_kg: list.reduce((s, p) => s + p.total_kg, 0),
        mur_kg: list.reduce((s, p) => s + p.mur_kg, 0),
        nb_caissettes: list.reduce((s, p) => s + p.nb_caissettes, 0),
        fiches_non_palettisees: list.reduce((s, p) => s + p.fiches_non_palettisees, 0),
        nb_fiches: list.reduce((s, p) => s + p.nb_fiches, 0),
        palettes_expediees: list.reduce((s, p) => s + p.palettes_expediees, 0),
        palettes_non_expediees: list.reduce((s, p) => s + p.palettes_non_expediees, 0),
    };
});

const hasActiveFilters = computed(() => search.value.trim() !== '' || onlyAlerts.value);
const resetFilters = () => { search.value = ''; onlyAlerts.value = false; };

/* ---------- Scroll auto vers détail producteur ---------- */
const detailRef = ref<HTMLElement | null>(null);
watch(
    () => props.producteurDetail?.id,
    async (id) => {
        if (id) {
            await nextTick();
            detailRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    },
);

/* ---------- KPI fusionné ---------- */
const murPct = computed(() => (props.global ? pct(props.global.mur_kg, props.global.total_kg) : 0));
const nonMurPct = computed(() => 100 - murPct.value);
</script>

<template>
    <Head :title="societe ? `Statistiques · ${societe.nom}` : 'Statistiques'" />

    <AppLayout :breadcrumbs="breadcrumbs">

        <!-- ============================================================ -->
        <!-- VUE 1 — LISTE DES SOCIÉTÉS (admin, aucune société sélectionnée) -->
        <!-- ============================================================ -->
        <div
            v-if="!societe"
            class="p-6 space-y-6 bg-[var(--background)] text-[var(--text)] font-sans"
        >
            <h1 class="text-xl font-black tracking-wide flex items-center gap-3">
                <Building2 class="w-6 h-6 text-[var(--brand-green)]" />
                Statistiques par société
            </h1>

            <div
                v-if="societes.length"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <Link
                    v-for="s in societes"
                    :key="s.id"
                    :href="societeUrl(s.id)"
                    class="group bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl p-5 hover:border-[var(--brand-green)] hover:shadow-md transition-all flex flex-col gap-3"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-[var(--brand-green)]/10 flex items-center justify-center shrink-0 overflow-hidden"
                        >
                            <img
                                v-if="s.logo_url"
                                :src="s.logo_url"
                                :alt="`Logo ${s.nom}`"
                                class="w-full h-full object-cover"
                            />
                            <Building2 v-else class="w-6 h-6 text-[var(--brand-green)]" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="font-black text-[15px] truncate group-hover:text-[var(--brand-green)] transition-colors">
                                {{ s.nom }}
                            </h2>
                            <p class="text-[11px] opacity-60 truncate flex items-center gap-1 mt-0.5">
                                <MapPinned class="w-3 h-3 shrink-0" />
                                {{ s.adresse ?? 'Adresse non renseignée' }}
                            </p>
                            <p v-if="s.phone" class="text-[11px] opacity-60 flex items-center gap-1 mt-0.5">
                                <Phone class="w-3 h-3 shrink-0" />
                                {{ s.phone }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-auto pt-3 border-t border-[var(--sidebar-border)]">
                        <div>
                            <div class="text-[10px] font-bold opacity-60 uppercase">Producteurs</div>
                            <div class="font-mono font-bold text-[14px]">{{ s.nb_producteurs ?? 0 }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold opacity-60 uppercase">Fiches</div>
                            <div class="font-mono font-bold text-[14px]">{{ s.nb_fiches ?? 0 }}</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end text-[11px] font-bold text-[var(--brand-green)] opacity-0 group-hover:opacity-100 transition-opacity">
                        Voir les statistiques <ChevronRight class="w-3.5 h-3.5 ml-0.5" />
                    </div>
                </Link>
            </div>

            <div v-else class="text-center py-16 opacity-50">
                <Building2 class="w-8 h-8 mx-auto mb-3" />
                <div class="text-[13px]">Aucune société enregistrée.</div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- VUE 2 — STATS D'UNE SOCIÉTÉ                                  -->
        <!-- ============================================================ -->
        <div
            v-else
            class="p-6 space-y-6 bg-[var(--background)] text-[var(--text)] font-sans"
        >
            <!-- Fil d'ariane admin -->
            <Link
                v-if="isAdmin"
                :href="societesUrl()"
                class="inline-flex items-center gap-1.5 text-[11px] font-bold opacity-70 hover:opacity-100 hover:text-[var(--brand-green)] transition-colors"
            >
                <ArrowLeft class="w-3.5 h-3.5" />
                Toutes les sociétés
            </Link>

            <header class="flex items-center gap-3 flex-wrap">
                <div class="w-10 h-10 rounded-xl bg-[var(--brand-green)]/10 flex items-center justify-center overflow-hidden shrink-0">
                    <img v-if="societe.logo_url" :src="societe.logo_url" :alt="`Logo ${societe.nom}`" class="w-full h-full object-cover" />
                    <Layers v-else class="w-5 h-5 text-[var(--brand-green)]" />
                </div>
                <div>
                    <div class="text-[11px] font-bold opacity-60 uppercase tracking-wide">Statistiques des livraisons</div>
                    <h1 class="text-lg font-black tracking-wide">{{ societe.nom }}</h1>
                </div>
            </header>

            <template v-if="global">

                <!-- ============ Indicateurs globaux ============ -->
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2 bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-4 border-l-4 border-l-[var(--brand-green)]">
                            <div class="flex items-baseline justify-between gap-4 flex-wrap">
                                <div>
                                    <div class="text-[11px] font-bold opacity-60">Réception totale</div>
                                    <div class="text-2xl font-black font-mono mt-1">{{ fmtKg(global.total_kg) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] font-bold opacity-60">dont mûr ≥ {{ calibreMin }} mm</div>
                                    <div class="text-lg font-black font-mono text-[var(--brand-green)]">
                                        {{ fmtKg(global.mur_kg) }}
                                        <span class="text-[12px] font-normal opacity-70">({{ murPct }} %)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 h-2 rounded-full bg-[var(--sidebar-border)] overflow-hidden flex">
                                <div class="h-full bg-[var(--brand-green)]" :style="{ width: murPct + '%' }" />
                                <div class="h-full bg-[var(--brand-orange)]/60" :style="{ width: nonMurPct + '%' }" />
                            </div>

                            <div class="mt-2 text-[11px] opacity-70 flex flex-wrap gap-x-4 gap-y-1">
                                <span>{{ global.nb_fiches }} fiches</span>
                                <span>·</span>
                                <span>{{ global.nb_caissettes.toLocaleString('fr-FR') }} caissettes</span>
                                <span>·</span>
                                <span>{{ global.nb_producteurs }} producteurs</span>
                            </div>
                        </div>

                        <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-4 border-l-4 border-l-purple-500">
                            <div class="text-[11px] font-bold opacity-60">Palettes expédiées</div>
                            <div class="text-2xl font-black font-mono mt-1">{{ global.palettes_expediees }}</div>
                            <div class="text-[11px] opacity-60 mt-1">
                                sur {{ global.palettes_expediees + global.palettes_non_expediees }} palettes
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div
                            class="rounded-xl p-4 border flex items-center gap-4"
                            :class="global.fiches_non_palettisees > 0
                                ? 'bg-amber-50 border-amber-300 dark:bg-amber-900/10 dark:border-amber-700/50'
                                : 'bg-[var(--card)] border-[var(--sidebar-border)]'"
                        >
                            <component
                                :is="global.fiches_non_palettisees > 0 ? PackageX : CheckCircle2"
                                class="w-8 h-8 shrink-0"
                                :class="global.fiches_non_palettisees > 0 ? 'text-amber-600' : 'text-emerald-600'"
                            />
                            <div>
                                <div class="text-[11px] font-bold opacity-70">Fiches de réception non palettisées</div>
                                <div class="text-2xl font-black font-mono">
                                    {{ global.fiches_non_palettisees }}
                                    <span class="text-[12px] font-normal opacity-60">sur {{ global.nb_fiches }} fiches</span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-xl p-4 border flex items-center gap-4"
                            :class="global.palettes_non_expediees > 0
                                ? 'bg-amber-50 border-amber-300 dark:bg-amber-900/10 dark:border-amber-700/50'
                                : 'bg-[var(--card)] border-[var(--sidebar-border)]'"
                        >
                            <component
                                :is="global.palettes_non_expediees > 0 ? TriangleAlert : CheckCircle2"
                                class="w-8 h-8 shrink-0"
                                :class="global.palettes_non_expediees > 0 ? 'text-amber-600' : 'text-emerald-600'"
                            />
                            <div>
                                <div class="text-[11px] font-bold opacity-70">Palettes non expédiées</div>
                                <div class="text-2xl font-black font-mono">
                                    {{ global.palettes_non_expediees }}
                                    <span class="text-[12px] font-normal opacity-60">en attente de départ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ Liste des producteurs ============ -->
                <div
                    v-show="!producteurDetail"
                    class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl shadow-sm overflow-hidden"
                >
                    <div class="px-6 py-4 bg-[var(--brand-green)] flex flex-wrap items-center gap-3 text-white">
                        <User class="w-4 h-4" />
                        <h2 class="text-[13px] font-black tracking-wide">Producteurs qui ont livré</h2>
                        <span class="text-[11px] opacity-80">
                            ({{ filteredSorted.length }} / {{ producteurs.length }})
                        </span>
                    </div>

                    <div class="px-4 py-3 border-b border-[var(--sidebar-border)] flex flex-wrap items-center gap-3 bg-[var(--background)]/50">
                        <div class="relative flex-1 min-w-[200px] max-w-sm">
                            <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 opacity-40" />
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Rechercher un producteur…"
                                class="w-full pl-9 pr-3 py-1.5 rounded-lg text-[12px] bg-[var(--card)] border border-[var(--sidebar-border)] focus:border-[var(--brand-green)] focus:outline-none focus:ring-1 focus:ring-[var(--brand-green)]/30"
                            />
                        </div>

                        <label class="flex items-center gap-2 text-[12px] cursor-pointer select-none">
                            <input
                                v-model="onlyAlerts"
                                type="checkbox"
                                class="rounded border-[var(--sidebar-border)] text-[var(--brand-green)] focus:ring-[var(--brand-green)]/30"
                            />
                            <span :class="onlyAlerts ? 'font-bold text-amber-600' : 'opacity-70'">Uniquement à traiter</span>
                        </label>

                        <button
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="text-[11px] font-bold px-2 py-1 rounded-md border border-[var(--sidebar-border)] hover:border-[var(--brand-green)] hover:text-[var(--brand-green)] transition-colors"
                        >
                            Réinitialiser
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <caption class="sr-only">
                                Liste des producteurs avec volumes livrés, mûr, fiches non palettisées et palettes
                            </caption>
                            <thead>
                                <tr class="bg-[var(--brand-green)]/10 text-[11px] font-bold border-b border-[var(--sidebar-border)]">
                                    <th scope="col" class="px-4 py-3">
                                        <button @click="toggleSort('nom')" class="flex items-center gap-1 hover:text-[var(--brand-green)]">
                                            Producteur <ArrowUpDown class="w-3 h-3 opacity-60" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3">
                                        <button @click="toggleSort('dernier_retour')" class="flex items-center gap-1 hover:text-[var(--brand-green)]">
                                            Dernier retour <ArrowUpDown class="w-3 h-3 opacity-60" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right">
                                        <button @click="toggleSort('total_kg')" class="ml-auto flex items-center gap-1 hover:text-[var(--brand-green)]">
                                            Quantité <ArrowUpDown class="w-3 h-3 opacity-60" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right">
                                        <button @click="toggleSort('nb_caissettes')" class="ml-auto flex items-center gap-1 hover:text-[var(--brand-green)]">
                                            Caissettes <ArrowUpDown class="w-3 h-3 opacity-60" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right">
                                        <button @click="toggleSort('mur_kg')" class="ml-auto flex items-center gap-1 hover:text-[var(--brand-green)]">
                                            Mûr ≥ {{ calibreMin }} mm <ArrowUpDown class="w-3 h-3 opacity-60" />
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center border-l border-[var(--sidebar-border)]">
                                        Fiches non palettisées
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center" colspan="2">Palettes</th>
                                    <th scope="col" class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--sidebar-border)]">
                                <tr
                                    v-for="p in filteredSorted"
                                    :key="p.id"
                                    class="text-[12px] cursor-pointer hover:bg-[var(--brand-green)]/5 transition-colors"
                                    :class="{ 'bg-[var(--brand-green)]/10 ring-1 ring-inset ring-[var(--brand-green)]/30': producteurDetail?.id === p.id }"
                                    @click="goToProducteur(p.id)"
                                >
                                    <td class="px-4 py-3 font-bold">{{ nomComplet(p) }}</td>
                                    <td class="px-4 py-3 text-[11px]">
                                        <span v-if="fmtDate(p.dernier_retour)">{{ fmtDate(p.dernier_retour) }}</span>
                                        <span v-else class="italic opacity-40">jamais</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold">{{ fmtKg(p.total_kg) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ p.nb_caissettes.toLocaleString('fr-FR') }}</td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        {{ fmtKg(p.mur_kg) }}
                                        <span class="text-[11px] opacity-70">({{ pct(p.mur_kg, p.total_kg) }} %)</span>
                                    </td>
                                    <td class="px-4 py-3 text-center border-l border-[var(--sidebar-border)]">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold" :class="chipAlert(p.fiches_non_palettisees)">
                                            {{ p.fiches_non_palettisees }} / {{ p.nb_fiches }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-emerald-700 dark:text-emerald-400">
                                        {{ p.palettes_expediees }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                            :class="p.palettes_non_expediees > 0
                                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                                : 'opacity-40'"
                                        >
                                            {{ p.palettes_non_expediees }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <ChevronRight class="w-4 h-4 inline opacity-40" />
                                    </td>
                                </tr>

                                <tr v-if="filteredSorted.length === 0">
                                    <td colspan="9" class="py-10 text-center">
                                        <div v-if="hasActiveFilters" class="space-y-2">
                                            <Search class="w-6 h-6 mx-auto opacity-30" />
                                            <div class="text-[12px] opacity-60">Aucun producteur ne correspond aux filtres.</div>
                                            <button @click="resetFilters" class="text-[11px] font-bold underline hover:text-[var(--brand-green)]">
                                                Réinitialiser les filtres
                                            </button>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <Sprout class="w-6 h-6 mx-auto opacity-30" />
                                            <div class="text-[12px] opacity-60">Aucune livraison enregistrée pour cette société.</div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>

                            <tfoot v-if="filteredSorted.length > 0" class="border-t-2 border-[var(--sidebar-border)] bg-[var(--brand-green)]/5 text-[12px] font-bold">
                                <tr>
                                    <td class="px-4 py-3">Total ({{ filteredSorted.length }})</td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3 text-right font-mono">{{ fmtKg(totals.total_kg) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ totals.nb_caissettes.toLocaleString('fr-FR') }}</td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        {{ fmtKg(totals.mur_kg) }}
                                        <span class="text-[11px] font-normal opacity-70">({{ pct(totals.mur_kg, totals.total_kg) }} %)</span>
                                    </td>
                                    <td class="px-4 py-3 text-center border-l border-[var(--sidebar-border)] font-mono">
                                        {{ totals.fiches_non_palettisees }} / {{ totals.nb_fiches }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-emerald-700 dark:text-emerald-400">
                                        {{ totals.palettes_expediees }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono">{{ totals.palettes_non_expediees }}</td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- ============ Détail d'un producteur ============ -->
                <div v-if="producteurDetail" ref="detailRef" class="space-y-5">
                    <div class="bg-[var(--brand-green)]/5 p-4 rounded-xl border border-[var(--brand-green)]/20 space-y-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="p-3 bg-[var(--brand-green)] rounded-full text-white">
                                <User class="w-6 h-6" />
                            </div>
                            <h2 class="text-lg font-black">{{ nomComplet(producteurDetail) }}</h2>

                            <Link
                                :href="currentSocieteUrl()"
                                preserve-scroll
                                class="ml-auto inline-flex items-center gap-1.5 text-[11px] font-bold px-4 py-2 border border-[var(--sidebar-border)] rounded-xl hover:bg-[var(--sidebar-border)]/20 transition-colors"
                            >
                                <ArrowLeft class="w-3.5 h-3.5" />
                                Retour à la liste
                            </Link>
                        </div>

                        <dl class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 text-[12px]">
                            <div>
                                <dt class="text-[11px] font-bold opacity-60">Quantité livrée</dt>
                                <dd class="font-mono font-bold">{{ fmtKg(producteurDetail.total_kg) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold opacity-60">Caissettes</dt>
                                <dd class="font-mono font-bold">{{ producteurDetail.nb_caissettes.toLocaleString('fr-FR') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold opacity-60">Mûr ≥ {{ calibreMin }} mm</dt>
                                <dd class="font-mono font-bold text-[var(--brand-green)]">{{ fmtKg(producteurDetail.mur_kg) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold opacity-60">Fiches non palettisées</dt>
                                <dd>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold" :class="chipAlert(producteurDetail.fiches_non_palettisees)">
                                        {{ producteurDetail.fiches_non_palettisees }} / {{ producteurDetail.nb_fiches }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold opacity-60">Palettes expédiées</dt>
                                <dd class="font-mono font-bold">{{ producteurDetail.palettes_expediees }} / {{ producteurDetail.palettes }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-bold opacity-60">Palettes non expédiées</dt>
                                <dd>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                        :class="producteurDetail.palettes_non_expediees > 0
                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'"
                                    >
                                        {{ producteurDetail.palettes_non_expediees }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <section
                        v-for="parcelle in producteurDetail.parcelles"
                        :key="parcelle.id"
                        class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl shadow-sm overflow-hidden"
                    >
                        <header class="px-5 py-3 bg-[var(--brand-green)]/10 border-b border-[var(--sidebar-border)] flex flex-wrap items-center gap-x-6 gap-y-2">
                            <h3 class="font-black text-[14px] flex items-center gap-2">
                                <MapPin class="w-4 h-4 text-[var(--brand-green)]" />
                                Parcelle n° {{ parcelle.num }}
                            </h3>
                            <div class="flex flex-wrap gap-x-5 gap-y-1 text-[12px]">
                                <span><span class="opacity-60">Livré :</span> <b class="font-mono">{{ fmtKg(parcelle.total_kg) }}</b></span>
                                <span><span class="opacity-60">Caissettes :</span> <b class="font-mono">{{ parcelle.nb_caissettes.toLocaleString('fr-FR') }}</b></span>
                                <span>
                                    <span class="opacity-60">Mûr ≥ {{ calibreMin }} mm :</span>
                                    <b class="font-mono">{{ fmtKg(parcelle.mur_kg) }}</b>
                                    <span class="opacity-60"> ({{ pct(parcelle.mur_kg, parcelle.total_kg) }} %)</span>
                                </span>
                            </div>
                            <span class="ml-auto px-2 py-0.5 rounded-full text-[11px] font-bold" :class="chipAlert(parcelle.fiches_non_palettisees)">
                                {{ parcelle.fiches_non_palettisees }} fiche(s) non palettisée(s)
                            </span>
                        </header>

                        <ul class="divide-y divide-[var(--sidebar-border)]">
                            <li v-for="r in parcelle.receptions" :key="r.id" class="px-5 py-4 space-y-3">
                                <dl class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-x-4 gap-y-2 text-[12px]">
                                    <div>
                                        <dt class="text-[11px] font-bold opacity-60">Retour station</dt>
                                        <dd class="font-bold">{{ fmtDate(r.retour_station, true) ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-bold opacity-60">Quantité</dt>
                                        <dd class="font-mono font-bold">{{ fmtKg(r.quantite_kg) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-bold opacity-60">Qualité de livraison</dt>
                                        <dd>
                                            <span class="px-2 py-0.5 rounded-full text-[12px] font-bold" :class="qualiteClass(r)">
                                                {{ r.qualite ?? 'Non renseignée' }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-bold opacity-60">Calibre</dt>
                                        <dd class="font-mono">{{ fmtCalibre(r.calibre) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-bold opacity-60">Caissettes</dt>
                                        <dd class="font-mono">{{ r.caissettes.toLocaleString('fr-FR') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-bold opacity-60">Fiche</dt>
                                        <dd class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono text-[11px] opacity-70">{{ r.fiche_number ?? '—' }}</span>
                                            <span
                                                v-if="!r.palettise"
                                                class="px-1.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                                            >
                                                Non palettisée
                                            </span>
                                        </dd>
                                    </div>
                                </dl>

                                <div v-if="r.lots.length" class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                    <div
                                        v-for="(l, i) in r.lots"
                                        :key="i"
                                        class="rounded-xl border p-3 text-[12px] space-y-2"
                                        :class="l.expedie
                                            ? 'border-purple-300 bg-purple-50/60 dark:bg-purple-900/10 dark:border-purple-700/50'
                                            : 'border-[var(--sidebar-border)] bg-[var(--background)]'"
                                    >
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="font-black">Palette n° {{ l.num_palette ?? '?' }}</span>
                                            <span v-if="l.type_carton" class="text-[11px] opacity-60">Carton : {{ l.type_carton }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[11px] font-bold opacity-60">Lot</span>
                                            <div>Lot {{ l.lot_number }} <span class="opacity-60">· {{ l.nb_cartons }} cartons</span></div>
                                        </div>
                                        <div>
                                            <span class="text-[11px] font-bold opacity-60">Certification</span>
                                            <div v-if="l.certifications.length" class="flex flex-wrap gap-1 mt-0.5">
                                                <span
                                                    v-for="c in l.certifications"
                                                    :key="c"
                                                    class="px-1.5 py-0.5 rounded-full text-[11px] font-bold border border-[var(--sidebar-border)] bg-[var(--background)]"
                                                >
                                                    {{ c }}
                                                </span>
                                            </div>
                                            <div v-else class="opacity-40 text-[11px]">Aucune</div>
                                        </div>
                                        <div>
                                            <span class="text-[11px] font-bold opacity-60">Bateau</span>
                                            <div v-if="l.expedie" class="flex items-center gap-1 font-bold text-purple-700 dark:text-purple-400">
                                                <Ship class="w-3.5 h-3.5" />
                                                {{ l.bateau ?? 'Nom non renseigné' }}
                                            </div>
                                            <div v-else class="text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                                Palette non expédiée
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <div v-if="!producteurDetail.parcelles.length" class="text-center py-10 opacity-50">
                        <Sprout class="w-6 h-6 mx-auto mb-2" />
                        <div class="text-[12px]">Aucune livraison pour ce producteur.</div>
                    </div>
                </div>
            </template>

            <!-- Fallback : société sans données -->
            <div v-else class="text-center py-16 opacity-50">
                <Building2 class="w-8 h-8 mx-auto mb-3" />
                <div class="text-[13px]">Aucune donnée pour cette société.</div>
            </div>
        </div>
    </AppLayout>
</template>