<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import {
    Trash2, Plus, Save, CheckCircle2, ChevronDown,
    ChevronUp, Pencil, X, AlertTriangle, Weight
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [];

// ─── Types ────────────────────────────────────────────────────────────────────

interface Parcelle { id: number; num: string; }

interface ReceptionLigne {
    _key:           number;
    open:           boolean;      // accordéon ouvert/fermé
    parcelle_id:    number | null;
    voiture:        string;
    commune:        string;
    caissette:      number | null;
    collecte:       string;
    depart_champ:   string;
    retour_station: string;
}

interface FicheReception {
    id:                  number;
    enqueteur_id:        number | null;
    fiche_number:        string | null;
    poids_par_caissette: number | null;
    lignes: {
        id:              number;
        parcelle_id:     number | null;
        parcelle:        { id: number; num: string } | null;
        voiture:         string | null;
        commune:         string | null;
        caissette:       number | null;
        collecte:        string | null;
        depart_champ:    string | null;
        retour_station:  string | null;
    }[];
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    parcelles:           Parcelle[];
    fiches: {
        data: FicheReception[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    poids_par_caissette: number;
    enqueteurs: { id: number; nom: string; prenom: string; poste: string }[];
}>();

// ─── Poids global (hors modal) ────────────────────────────────────────────────

const enqueteurId         = ref<number | null>(null);
const ficheNumber         = ref('');
const poidsGlobal         = ref<number | null>(props.poids_par_caissette || null);
const poidsSaving         = ref(false);
const poidsSuccess        = ref(false);

const savePoids = () => {
    poidsSaving.value  = true;
    poidsSuccess.value = false;
    router.post(route('settings.poids'), {
        poids_par_caissette: poidsGlobal.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { poidsSaving.value = false; poidsSuccess.value = true; },
        onError:   () => { poidsSaving.value = false; },
    });
};

// ─── Modal ────────────────────────────────────────────────────────────────────

const showModal      = ref(false);
const editingFicheId = ref<number | null>(null);
const modalEnqueteurId = ref<number | null>(null); // <-- Remplacé ici
const modalFicheNum  = ref('');
const modalSaving    = ref(false);
const modalError     = ref<string | null>(null);
const modalSuccess   = ref(false);

// ─── Lignes (accordéon) ───────────────────────────────────────────────────────

const makeRow = (): ReceptionLigne => ({
    _key:           Date.now() + Math.random(),
    open:           true,
    parcelle_id:    null,
    voiture:        '',
    commune:        '',
    caissette:      null,
    collecte:       new Date().toISOString().slice(0, 16),
    depart_champ:   '',
    retour_station: '',
});

const modalRows = reactive<ReceptionLigne[]>([makeRow()]);

const addModalRow    = () => modalRows.push(makeRow());
const removeModalRow = (i: number) => { if (modalRows.length > 1) modalRows.splice(i, 1); };
const toggleRow      = (row: ReceptionLigne) => { row.open = !row.open; };

// Résumé d'une ligne pour l'affichage condensé
const rowSummary = (row: ReceptionLigne) => {
    const num = props.parcelles.find(p => p.id === row.parcelle_id)?.num ?? '—';
    const kg  = row.caissette && poidsGlobal.value
        ? (row.caissette * poidsGlobal.value).toFixed(2) + ' kg'
        : null;
    return { num, kg };
};

// Total de la fiche en cours
const totalCaissettes = computed(() =>
    modalRows.reduce((s, r) => s + (r.caissette ?? 0), 0)
);
const totalKg = computed(() =>
    poidsGlobal.value
        ? (totalCaissettes.value * poidsGlobal.value).toFixed(2)
        : null
);

// Calcul kg pour une ligne
const ligneKg = (caissette: number | null): string | null =>
    caissette && poidsGlobal.value
        ? (caissette * poidsGlobal.value).toFixed(2)
        : null;

// ─── Ouverture modal ──────────────────────────────────────────────────────────

const openAddModal = () => {
    editingFicheId.value = null;
    modalEnqueteurId.value  = enqueteurId.value; // <-- Remplacé ici
    modalFicheNum.value  = ficheNumber.value;
    modalError.value     = null;
    modalSuccess.value   = false;
    modalRows.splice(0, modalRows.length, makeRow());
    showModal.value      = true;
};

const openEditModal = (fiche: FicheReception) => {
    editingFicheId.value = fiche.id;
    modalEnqueteurId.value = fiche.enqueteur_id ?? null; 
    modalFicheNum.value  = fiche.fiche_number ?? '';
    modalError.value     = null;
    modalSuccess.value   = false;

    const lignes = fiche.lignes.map(l => ({
        _key:           Date.now() + Math.random(),
        open:           false,        // fermées par défaut en mode édition
        parcelle_id:    l.parcelle_id,
        voiture:        l.voiture        ?? '',
        commune:        l.commune        ?? '',
        caissette:      l.caissette,
        collecte:       l.collecte?.slice(0, 16)       ?? '',
        depart_champ:   l.depart_champ?.slice(0, 16)   ?? '',
        retour_station: l.retour_station?.slice(0, 16) ?? '',
    }));
    modalRows.splice(0, modalRows.length, ...lignes);
    showModal.value = true;
};

const closeModal = () => {
    showModal.value      = false;
    editingFicheId.value = null;
    modalError.value     = null;
};

// ─── Soumission ───────────────────────────────────────────────────────────────

const submitModal = () => {
    modalSaving.value  = true;
    modalError.value   = null;
    modalSuccess.value = false;

    const payload = {
        enqueteur_id:        modalEnqueteurId.value, 
        fiche_number:        modalFicheNum.value,
        poids_par_caissette: poidsGlobal.value,
        lignes: modalRows.map(({ _key, open, ...r }) => r),
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modalSaving.value  = false;
            modalSuccess.value = true;
            setTimeout(closeModal, 700);
        },
        onError: (e: Record<string, string>) => {
            modalError.value  = Object.values(e)[0] ?? 'Erreur';
            modalSaving.value = false;
        },
    };

    editingFicheId.value !== null
        ? router.put(route('reception.update', editingFicheId.value), payload, options)
        : router.post(route('reception.store'), payload, options);
};

// ─── Suppression ─────────────────────────────────────────────────────────────

const deleteTarget    = ref<FicheReception | null>(null);
const showDeleteModal = ref(false);
const deleting        = ref(false);

const confirmDelete = (f: FicheReception) => { deleteTarget.value = f; showDeleteModal.value = true; };
const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('reception.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; deleteTarget.value = null; deleting.value = false; },
        onError:   () => { deleting.value = false; },
    });
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

const fmtDate = (d: string | null) => {
    if (!d) return '—';
    return new Date(d).toLocaleString('fr-FR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit' 
    });
};

const parcelleNum = (ligne: FicheReception['lignes'][0]) =>
    ligne.parcelle?.num
    ?? props.parcelles.find(p => p.id === ligne.parcelle_id)?.num
    ?? '—';
</script>

<template>
    <Head title="Fiche de Réception" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-5 bg-[var(--background)] text-[var(--text)] font-sans">

            <HeaderFiche 
                title="Réception" 
                v-model:enqueteurId="enqueteurId" 
                :enqueteurs="props.enqueteurs"
            />

            <!-- ══ POIDS GLOBAL — hors modal ════════════════════════════════ -->
            <div class="flex items-center gap-4 bg-[var(--card)]
                        border border-[var(--sidebar-border)] rounded-2xl px-5 py-3 w-fit shadow-sm">
                <Weight class="w-5 h-5 text-[var(--brand-orange)] shrink-0" />
                <span class="text-[12px] font-black uppercase tracking-widest text-[var(--brand-orange)] whitespace-nowrap">
                    Poids litchi / caissette
                </span>
                <select
                    v-model="poidsGlobal"
                    class="bg-transparent outline-none text-[15px] font-black text-center
                        border-b-2 border-[var(--brand-orange)]/30
                        focus:border-[var(--brand-orange)] transition-colors cursor-pointer"
                >
                    <option :value="16">16</option>
                    <option :value="18">18</option>
                    <option :value="20">20</option>
                </select>
                <span class="text-[12px] font-bold">kg</span>
                <!-- <span class="text-[11px] text-gray-400 italic hidden sm:block">
                    mémorisé pour toutes les fiches
                </span> -->
                <button @click="savePoids" :disabled="poidsSaving"
                    class="h-8 px-4 bg-[var(--brand-green)] text-white rounded-xl
                           text-[11px] font-black uppercase tracking-widest
                           flex items-center gap-1.5 shadow shadow-[var(--brand-green)]/20
                           active:scale-95 transition-all disabled:opacity-50">
                    <CheckCircle2 v-if="poidsSuccess" class="w-3.5 h-3.5" />
                    <Save v-else class="w-3.5 h-3.5" />
                    {{ poidsSuccess ? 'Sauvegardé' : 'Sauvegarder' }}
                </button>
            </div>

            <!-- ══ EN-TÊTE LISTE ════════════════════════════════════════════ -->
            <div class="flex items-center justify-between">
                <h2 class="text-[13px] font-black uppercase tracking-widest flex items-center gap-2">
                    <ChevronDown class="w-4 h-4 text-[var(--brand-green)]" />
                    Fiches enregistrées
                    <span class="px-2 py-0.5 rounded-full bg-[var(--brand-green)]/10
                                 text-[var(--brand-green)] text-[11px]">
                        {{ props.fiches.total }}
                    </span>
                </h2>
                <button @click="openAddModal"
                    class="h-10 px-5 bg-[var(--brand-green)] text-white rounded-xl
                           text-[12px] font-black uppercase tracking-widest
                           flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20
                           active:scale-95 transition-all">
                    <Plus class="w-4 h-4" /> Nouvelle fiche
                </button>
            </div>

            <!-- ══ TABLEAU LISTE ════════════════════════════════════════════ -->
            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)]
                        bg-[var(--card)] shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1100px]">
                        <thead>
                            <tr class="bg-[var(--brand-green)]/80 text-white
                                       text-[10px] font-black uppercase tracking-wider">
                                <th class="px-3 py-3 text-center border-r border-white/10">N° Parcelle</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Qtté Caissette livré</th>
                                <th class="px-3 py-3 text-center border-r border-white/10 bg-[var(--brand-green)]/60">Quantité (kg)</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">N° Voiture</th>
                                <th class="px-3 py-3 text-route border-r border-white/10">Commune et district</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Collecte</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Départ champ</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Retour à la station</th>
                                
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--sidebar-border)]">

                            <tr v-if="props.fiches.total === 0">
                                <td colspan="9"
                                    class="py-12 text-center text-[12px] font-bold opacity-30 uppercase tracking-widest">
                                    Aucune fiche enregistrée
                                </td>
                            </tr>

                            <template v-for="fiche in props.fiches.data" :key="fiche.id">
                                <tr v-for="(ligne, li) in fiche.lignes" :key="ligne.id"
                                    class="hover:bg-[var(--brand-green)]/5 transition-colors text-[12px]">

                                    <td class="px-3 py-2 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">
                                        {{ parcelleNum(ligne) }}
                                    </td>
                                    <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                        {{ ligne.caissette ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-center bg-[var(--brand-green)]/5 border-r border-[var(--sidebar-border)]/30">
                                        <span class="font-black"
                                            :class="ligne.caissette && (fiche.poids_par_caissette ?? poidsGlobal)
                                                ? 'text-[var(--brand-green)]' : 'opacity-30'">
                                            {{
                                                ligne.caissette && (fiche.poids_par_caissette ?? poidsGlobal)
                                                    ? (ligne.caissette *  poidsGlobal).toFixed(2) + ' kg'
                                                    : '—'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">{{ ligne.voiture || '—' }}</td>
                                    <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">{{ ligne.commune || '—' }}</td>
                                    <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">{{ fmtDate(ligne.collecte) }}</td>
                                    <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">{{ fmtDate(ligne.depart_champ) }}</td>
                                    <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">{{ fmtDate(ligne.retour_station) }}</td>

                                    <!-- Actions : rowspan sur la 1ère sous-ligne -->
                                    <td v-if="li === 0"
                                        :rowspan="fiche.lignes.length"
                                        class="px-3 py-2 text-center align-middle">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button @click="openEditModal(fiche)"
                                                class="inline-flex items-center gap-1 px-3 py-1 rounded-lg
                                                       border border-[var(--sidebar-border)] text-[10px] font-black uppercase
                                                       hover:border-[var(--brand-green)] hover:text-[var(--brand-green)] transition-all">
                                                <Pencil class="w-3 h-3" /> Modifier
                                            </button>
                                            <button @click="confirmDelete(fiche)"
                                                class="p-1.5 rounded-lg border border-[var(--sidebar-border)]
                                                       hover:border-red-500 hover:text-red-500 transition-all">
                                                <Trash2 class="w-3 h-3" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- ══ PAGINATION ══════════════════════════════════════════════ -->
            <div v-if="props.fiches.last_page > 1"
                class="flex items-center justify-between pt-3">

                <span class="text-[11px] font-bold opacity-40 uppercase tracking-widest">
                    Page {{ props.fiches.current_page }} / {{ props.fiches.last_page }}
                    · {{ props.fiches.total }} fiches
                </span>

                <div class="flex items-center gap-1">
                    <template v-for="link in props.fiches.links" :key="link.label">
                        <button
                            v-if="link.url"
                            @click="router.get(link.url, {}, { preserveScroll: true })"
                            :class="[
                                'h-8 min-w-[2rem] px-2 rounded-lg text-[11px] font-black uppercase transition-all',
                                link.active
                                    ? 'bg-[var(--brand-green)] text-white shadow shadow-[var(--brand-green)]/20'
                                    : 'border border-[var(--sidebar-border)] hover:border-[var(--brand-green)] hover:text-[var(--brand-green)]'
                            ]"
                            v-html="link.label"
                        />
                        <span v-else
                            class="h-8 min-w-[2rem] px-2 flex items-center justify-center
                                text-[11px] opacity-25 font-black"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             MODAL FORMULAIRE
        ════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal"
                    class="fixed inset-0 z-50 flex items-start justify-center
                           bg-black/60 backdrop-blur-sm p-4 overflow-y-auto"
                    @click.self="closeModal">

                    <div class="bg-[var(--card)] rounded-2xl shadow-2xl w-full max-w-3xl
                                border border-[var(--sidebar-border)] overflow-hidden my-8">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4
                                    bg-[var(--brand-green)] text-white">
                            <h2 class="text-[13px] font-black uppercase tracking-widest flex items-center gap-2">
                                <Plus class="w-4 h-4" />
                                {{ editingFicheId ? 'Modifier la fiche' : 'Nouvelle fiche de réception' }}
                            </h2>
                            <button @click="closeModal" class="hover:opacity-70 transition-opacity">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="p-6 space-y-5">

                            <!-- ── Bloc 1 : infos fiche ──────────────────── -->
                            <!-- ── Bloc 1 : infos fiche ──────────────────── -->
                            <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl overflow-hidden">
                                <div class="flex items-center gap-3 px-5 py-3
                                            bg-[var(--brand-green)]/8 border-b border-[var(--sidebar-border)]">
                                    <span class="w-6 h-6 rounded-full bg-[var(--brand-green)]/15
                                                text-[var(--brand-green)] text-[11px] font-black
                                                flex items-center justify-center shrink-0">1</span>
                                    <span class="text-[12px] font-black uppercase tracking-widest">
                                        Informations de la fiche
                                    </span>
                                </div>
                                <div class="p-5 grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-[12px] font-black uppercase tracking-wider">
                                            Enquêteur
                                        </label>
                                        <select v-model="modalEnqueteurId" class="input-line w-full">
                                            <option :value="null">— choisir —</option>
                                            <option v-for="e in props.enqueteurs" :key="e.id" :value="e.id">
                                                {{ e.prenom }} {{ e.nom }}
                                            </option>
                                        </select>
                                    </div>
                                    <!-- <div class="space-y-1.5">
                                        <label class="text-[12px] font-black uppercase tracking-wider opacity-60">
                                            N° fiche
                                        </label>
                                        <input v-model="modalFicheNum" type="text"
                                            placeholder="ex: REC-2024-001"
                                            class="input-line w-full" />
                                    </div> -->
                                </div>
                            </div>

                            <!-- ── Bloc 2 : lignes de réception ─────────── -->
                            <div class="bg-[var(--card)] border border-[var(--sidebar-border)]
                                        rounded-2xl overflow-hidden">
                                <div class="flex items-center gap-3 px-5 py-3
                                            bg-[var(--brand-green)]/8 border-b border-[var(--sidebar-border)]">
                                    <span class="w-6 h-6 rounded-full bg-[var(--brand-green)]/15
                                                 text-[var(--brand-green)] text-[11px] font-black
                                                 flex items-center justify-center shrink-0">2</span>
                                    <span class="text-[12px] font-black uppercase tracking-widest">
                                        Lignes de réception
                                    </span>
                                    <span class="ml-auto px-2.5 py-0.5 rounded-full
                                                 bg-[var(--brand-green)]/10 text-[var(--brand-green)]
                                                 text-[11px] font-black">
                                        {{ modalRows.length }} ligne{{ modalRows.length > 1 ? 's' : '' }}
                                    </span>
                                </div>

                                <div class="p-4 space-y-3">

                                    <!-- Chaque ligne en accordéon -->
                                    <div v-for="(row, i) in modalRows" :key="row._key"
                                        class="border border-[var(--sidebar-border)] rounded-xl overflow-hidden">

                                        <!-- En-tête ligne (toujours visible) -->
                                        <div class="flex items-center gap-3 px-4 py-2.5
                                                    bg-[var(--background)] border-b border-[var(--sidebar-border)]"
                                             :class="{ 'border-b-0': !row.open }">
                                            <span class="w-6 h-6 rounded-full bg-[var(--brand-orange)]/10
                                                         text-[var(--brand-orange)] text-[11px] font-black
                                                         flex items-center justify-center shrink-0">
                                                {{ i + 1 }}
                                            </span>

                                            <!-- Résumé condensé quand fermé -->
                                            <template v-if="!row.open">
                                                <span class="text-[12px] font-black">
                                                    {{ rowSummary(row).num }}
                                                </span>
                                                <span class="text-[12px]">
                                                    {{ row.caissette ?? '—' }} caissettes
                                                </span>
                                                <span v-if="rowSummary(row).kg"
                                                    class="px-2.5 py-0.5 rounded-full
                                                           bg-[var(--brand-orange)]/10 text-[var(--brand-orange)]
                                                           text-[11px] font-black">
                                                    {{ rowSummary(row).kg }}
                                                </span>
                                                <span v-if="row.voiture"
                                                    class="text-[12px]">
                                                    {{ row.voiture }}
                                                </span>
                                            </template>
                                            <span v-else
                                                class="text-[12px] font-black uppercase tracking-widest">
                                                Ligne de collecte
                                            </span>

                                            <div class="ml-auto flex items-center gap-1.5">
                                                <button @click="toggleRow(row)"
                                                    class="p-1.5 rounded-lg border border-[var(--sidebar-border)]
                                                           hover:border-[var(--brand-green)]/60 transition-all">
                                                    <ChevronUp v-if="row.open" class="w-3.5 h-3.5" />
                                                    <ChevronDown v-else class="w-3.5 h-3.5" />
                                                </button>
                                                <button @click="removeModalRow(i)"
                                                    class="p-1.5 rounded-lg border border-[var(--sidebar-border)]
                                                           hover:border-red-500 hover:text-red-500 transition-all">
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Corps accordéon -->
                                        <div v-show="row.open" class="p-4 grid grid-cols-3 gap-4">

                                            <!-- N° Parcelle -->
                                            <div class="space-y-1.5">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    N° Parcelle
                                                </label>
                                                <select v-model="row.parcelle_id" class="input-line w-full">
                                                    <option :value="null" disabled>— choisir —</option>
                                                    <option v-for="p in props.parcelles"
                                                            :key="p.id" :value="p.id">
                                                        {{ p.num }}
                                                    </option>
                                                </select>
                                            </div>

                                            <!-- Nb caissettes -->
                                            <div class="space-y-1.5">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    Nb caissettes livrées
                                                </label>
                                                <input v-model="row.caissette"
                                                    type="number" min="0"
                                                    class="input-line w-full" />
                                            </div>

                                            <!-- Quantité kg (calculée) -->
                                            <div class="space-y-1.5">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    Quantité litchis (kg)
                                                </label>
                                                <div class="flex items-center gap-2 h-10">
                                                    <span v-if="ligneKg(row.caissette)"
                                                        class="px-3 py-1.5 rounded-xl
                                                               bg-[var(--brand-orange)]/10
                                                               text-[var(--brand-orange)]
                                                               text-[13px] font-black">
                                                        {{ ligneKg(row.caissette) }} kg
                                                    </span>
                                                    <span v-else class="text-[12px] font-bold">—</span>
                                                    <span v-if="row.caissette && poidsGlobal"
                                                        class="text-[10px]">
                                                        {{ row.caissette }} × {{ poidsGlobal }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Voiture -->
                                            <div class="space-y-1.5">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    N° Voiture
                                                </label>
                                                <input v-model="row.voiture" type="text"
                                                    class="input-line w-full" />
                                            </div>

                                            <!-- Commune -->
                                            <div class="space-y-1.5">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    Commune / District
                                                </label>
                                                <input v-model="row.commune" type="text"
                                                    class="input-line w-full" />
                                            </div>

                                            <!-- Collecte -->
                                            <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    Collecte
                                                </label>
                                                <input v-model="row.collecte"
                                                    type="datetime-local"
                                                    class="input-line w-full text-[12px]" 
                                                    @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                            </div>

                                            <!-- Départ champ -->
                                            <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    Départ au champ
                                                </label>
                                                <input v-model="row.depart_champ"
                                                    type="datetime-local"
                                                    class="input-line w-full text-[12px]" 
                                                    @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                            </div>

                                            <!-- Retour station -->
                                            <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                <label class="text-[12px] font-black uppercase tracking-wider">
                                                    Retour à la station
                                                </label>
                                                <input v-model="row.retour_station"
                                                    type="datetime-local"
                                                    class="input-line w-full text-[12px]" 
                                                    @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Bouton ajouter ligne -->
                                    <button @click="addModalRow"
                                        class="w-full h-9 border-2 border-dashed border-[var(--brand-orange)]/30
                                               hover:border-[var(--brand-orange)] text-[var(--brand-orange)]
                                               rounded-xl text-[11px] font-black uppercase tracking-widest
                                               flex items-center justify-center gap-2
                                               transition-all active:scale-[0.99]">
                                        <Plus class="w-3.5 h-3.5" /> Ajouter une ligne
                                    </button>

                                    <!-- Total -->
                                    <div v-if="totalCaissettes > 0"
                                        class="flex items-center justify-end gap-3
                                               pt-3 border-t border-[var(--sidebar-border)]/50">
                                        <span class="text-[12px] font-bold uppercase tracking-widest">
                                            Total · {{ totalCaissettes }} caissettes
                                        </span>
                                        <span v-if="totalKg"
                                            class="px-3 py-1 rounded-xl bg-[var(--brand-orange)]/10
                                                   text-[var(--brand-orange)] text-[13px] font-black">
                                            {{ totalKg }} kg
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Footer modal ──────────────────────────── -->
                            <div class="flex items-center justify-between pt-1
                                        border-t border-[var(--sidebar-border)]/40">
                                <p v-if="modalError"
                                    class="text-[12px] font-bold text-red-500">
                                    ⚠️ {{ modalError }}
                                </p>
                                <p v-else-if="modalSuccess"
                                    class="text-[12px] font-bold text-emerald-500 flex items-center gap-1">
                                    <CheckCircle2 class="w-4 h-4" /> Enregistré
                                </p>
                                <span v-else />

                                <div class="flex gap-3">
                                    <button @click="closeModal"
                                        class="h-10 px-5 border border-[var(--sidebar-border)] rounded-xl
                                               text-[12px] font-black uppercase tracking-widest
                                               hover:bg-[var(--sidebar-border)]/20 transition-all">
                                        Annuler
                                    </button>
                                    <button @click="submitModal" :disabled="modalSaving"
                                        class="h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl
                                               text-[12px] font-black uppercase tracking-widest
                                               flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20
                                               active:scale-95 transition-all disabled:opacity-50">
                                        <svg v-if="modalSaving" class="animate-spin w-4 h-4"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8v8H4z"/>
                                        </svg>
                                        <Save v-else class="w-4 h-4" />
                                        {{ modalSaving ? 'Enregistrement...' : 'Enregistrer' }}
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Modal suppression ──────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showDeleteModal"
                class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center
                       justify-center z-50 p-4"
                @click.self="showDeleteModal = false">
                <div class="bg-[var(--card)] border border-[var(--sidebar-border)]
                            rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                    <div class="p-8 text-center space-y-4">
                        <div class="w-14 h-14 bg-red-100 dark:bg-red-950/40 text-red-500
                                    rounded-full flex items-center justify-center mx-auto
                                    border border-red-200 dark:border-red-800">
                            <AlertTriangle class="w-7 h-7" />
                        </div>
                        <div>
                            <h2 class="font-black uppercase tracking-widest">Supprimer la fiche ?</h2>
                            <p class="text-[12px] text-[var(--text)]/50 font-bold mt-1.5">
                                Fiche <span class="font-black text-[var(--text)]">
                                    {{ deleteTarget?.fiche_number || '#' + deleteTarget?.id }}
                                </span> et toutes ses lignes — action irréversible.
                            </p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="showDeleteModal = false" :disabled="deleting"
                            class="flex-1 h-11 text-[12px] font-black uppercase
                                   border border-[var(--sidebar-border)] rounded-xl
                                   hover:bg-[var(--sidebar-border)]/20 transition-all">
                            Annuler
                        </button>
                        <button @click="executeDelete" :disabled="deleting"
                            class="flex-1 h-11 text-[12px] font-black uppercase
                                   bg-red-600 text-white rounded-xl shadow-lg shadow-red-600/20
                                   disabled:opacity-60 flex items-center justify-center gap-2
                                   transition-all active:scale-95">
                            <svg v-if="deleting" class="animate-spin w-4 h-4"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ deleting ? 'Suppression...' : 'Supprimer' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
    transform: scale(0.97);
}
</style>