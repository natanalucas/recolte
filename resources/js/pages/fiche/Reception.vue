<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import {
    Trash2, Plus, Save, CheckCircle2,
    Pencil, X, AlertTriangle, Weight
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [];

// ─── Types ────────────────────────────────────────────────────────────────────

interface Parcelle { id: number; num: string; }

interface FicheReception {
    id:                  number;
    enqueteur_id:        number | null;
    fiche_number:        string | null;
    poids_par_caissette: number | null;
    parcelle_id:         number | null;
    parcelle:            { id: number; num: string } | null;
    voiture:             string | null;
    commune:             string | null;
    caissette:           number | null;
    pourcentage_dechet:  number | null;
    collecte:            string | null;
    depart_champ:        string | null;
    retour_station:      string | null;
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

// ─── Poids global ─────────────────────────────────────────────────────────────

const poidsGlobal = ref<number | null>(props.poids_par_caissette || null);
const poidsSaving = ref(false);
const poidsSuccess = ref(false);

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
const form = reactive({
    enqueteur_id:        null as number | null,
    parcelle_id:         null as number | null,
    voiture:             '',
    commune:             '',
    caissette:           null as number | null,
    pourcentage_dechet:  null as number | null,
    collecte:            new Date().toISOString().slice(0, 16),
    depart_champ:        '',
    retour_station:      '',
});

const modalSaving  = ref(false);
const modalError   = ref<string | null>(null);
const modalSuccess = ref(false);

// ─── Calcul des kg ──────────────────────────────────────────────────────────

const quantiteKg = computed(() => {
    if (form.caissette && poidsGlobal.value) {
        return (form.caissette * poidsGlobal.value).toFixed(2);
    }
    return null;
});

// ─── Ouverture modal ──────────────────────────────────────────────────────────

const openAddModal = () => {
    editingFicheId.value = null;
    Object.assign(form, {
        enqueteur_id:        null,
        parcelle_id:         null,
        voiture:             '',
        commune:             '',
        caissette:           null,
        pourcentage_dechet:  null,
        collecte:            new Date().toISOString().slice(0, 16),
        depart_champ:        '',
        retour_station:      '',
    });
    modalError.value   = null;
    modalSuccess.value = false;
    showModal.value    = true;
};

const openEditModal = (fiche: FicheReception) => {
    editingFicheId.value = fiche.id;
    Object.assign(form, {
        enqueteur_id:        fiche.enqueteur_id ?? null,
        parcelle_id:         fiche.parcelle_id ?? null,
        voiture:             fiche.voiture ?? '',
        commune:             fiche.commune ?? '',
        caissette:           fiche.caissette ?? null,
        pourcentage_dechet:  fiche.pourcentage_dechet ?? null,
        collecte:            fiche.collecte?.slice(0, 16) ?? '',
        depart_champ:        fiche.depart_champ?.slice(0, 16) ?? '',
        retour_station:      fiche.retour_station?.slice(0, 16) ?? '',
    });
    modalError.value   = null;
    modalSuccess.value = false;
    showModal.value    = true;
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
        ...form,
        poids_par_caissette: poidsGlobal.value,
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

const parcelleNum = (fiche: FicheReception) =>
    fiche.parcelle?.num ?? props.parcelles.find(p => p.id === fiche.parcelle_id)?.num ?? '—';
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

            <!-- ══ POIDS GLOBAL ────────────────────────────────────────────── -->
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

            <!-- ══ EN-TÊTE LISTE ───────────────────────────────────────────── -->
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

            <!-- ══ TABLEAU DES FICHES ──────────────────────────────────────── -->
            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)]
                        bg-[var(--card)] shadow">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1400px]">
                        <thead>
                            <tr class="bg-[var(--brand-green)]/80 text-white
                                       text-[10px] font-black uppercase tracking-wider">
                                <th class="px-3 py-3 text-center border-r border-white/10">N° Fiche</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Parcelle</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Caissettes</th>
                                <th class="px-3 py-3 text-center border-r border-white/10 bg-[var(--brand-green)]/60">Quantité (kg)</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Déchet (%)</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Voiture</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Commune</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Collecte</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Départ champ</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Retour station</th>
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--sidebar-border)]">

                            <tr v-if="props.fiches.total === 0">
                                <td colspan="11"
                                    class="py-12 text-center text-[12px] font-bold opacity-30 uppercase tracking-widest">
                                    Aucune fiche enregistrée
                                </td>
                            </tr>

                            <tr v-for="fiche in props.fiches.data" :key="fiche.id"
                                class="hover:bg-[var(--brand-green)]/5 transition-colors text-[12px]">

                                <td class="px-3 py-2 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">
                                    {{ fiche.fiche_number ?? '#' + fiche.id }}
                                </td>

                                <td class="px-3 py-2 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">
                                    {{ parcelleNum(fiche) }}
                                </td>

                                <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                    {{ fiche.caissette ?? '—' }}
                                </td>

                                <td class="px-3 py-2 text-center bg-[var(--brand-orange)]/5 border-r border-[var(--sidebar-border)]/30">
                                    <span class="font-black"
                                        :class="fiche.caissette && (fiche.poids_par_caissette ?? poidsGlobal)
                                            ? 'text-[var(--brand-orange)]' : 'opacity-30'">
                                        {{
                                            fiche.caissette && (fiche.poids_par_caissette ?? poidsGlobal)
                                                ? (fiche.caissette * (fiche.poids_par_caissette ?? poidsGlobal)).toFixed(2) + ' kg'
                                                : '—'
                                        }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                    <span v-if="fiche.pourcentage_dechet" class="font-black text-red-500">
                                        {{ fiche.pourcentage_dechet }}%
                                    </span>
                                    <span v-else>—</span>
                                </td>

                                <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                    {{ fiche.voiture || '—' }}
                                </td>

                                <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                    {{ fiche.commune || '—' }}
                                </td>

                                <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">
                                    {{ fmtDate(fiche.collecte) }}
                                </td>

                                <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">
                                    {{ fmtDate(fiche.depart_champ) }}
                                </td>

                                <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">
                                    {{ fmtDate(fiche.retour_station) }}
                                </td>

                                <td class="px-3 py-2 text-center">
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
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══ PAGINATION ────────────────────────────────────────────────── -->
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
             MODAL FORMULAIRE (une seule ligne)
        ════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal"
                    class="fixed inset-0 z-50 flex items-start justify-center
                           bg-black/60 backdrop-blur-sm p-4 overflow-y-auto"
                    @click.self="closeModal">

                    <div class="bg-[var(--card)] rounded-2xl shadow-2xl w-full max-w-2xl
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

                            <!-- ── Enquêteur ──────────────────────────────── -->
                            <div class="space-y-1.5">
                                <label class="text-[12px] font-black uppercase tracking-wider">
                                    Enquêteur
                                </label>
                                <select v-model="form.enqueteur_id" class="input-line w-full">
                                    <option :value="null">— choisir —</option>
                                    <option v-for="e in props.enqueteurs" :key="e.id" :value="e.id">
                                        {{ e.prenom }} {{ e.nom }}
                                    </option>
                                </select>
                            </div>

                            <!-- ── Champs de la fiche ────────────────────── -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        N° Parcelle
                                    </label>
                                    <select v-model="form.parcelle_id" class="input-line w-full">
                                        <option :value="null">— choisir —</option>
                                        <option v-for="p in props.parcelles" :key="p.id" :value="p.id">
                                            {{ p.num }}
                                        </option>
                                    </select>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        N° Voiture
                                    </label>
                                    <input v-model="form.voiture" type="text" class="input-line w-full" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Commune / District
                                    </label>
                                    <input v-model="form.commune" type="text" class="input-line w-full" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Nb caissettes livrées
                                    </label>
                                    <input v-model="form.caissette" type="number" min="0" class="input-line w-full" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Pourcentage du déchet
                                    </label>
                                    <div class="flex items-center gap-1">
                                        <input v-model="form.pourcentage_dechet" type="number" min="0" max="100" step="0.01"
                                               class="input-line w-full" />
                                        <span class="text-[12px] font-bold">%</span>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Quantité (kg) – calculée
                                    </label>
                                    <div class="flex items-center gap-2 h-10">
                                        <span v-if="quantiteKg"
                                            class="px-3 py-1.5 rounded-xl bg-[var(--brand-orange)]/10
                                                   text-[var(--brand-orange)] text-[13px] font-black">
                                            {{ quantiteKg }} kg
                                        </span>
                                        <span v-else class="text-[12px] font-bold">—</span>
                                        <span v-if="form.caissette && poidsGlobal" class="text-[10px]">
                                            {{ form.caissette }} × {{ poidsGlobal }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Dates ──────────────────────────────────── -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <label class="text-[12px] font-black uppercase tracking-wider">Collecte</label>
                                    <input v-model="form.collecte" type="datetime-local"
                                           class="input-line w-full text-[12px]" @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                </div>
                                <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <label class="text-[12px] font-black uppercase tracking-wider">Départ champ</label>
                                    <input v-model="form.depart_champ" type="datetime-local"
                                           class="input-line w-full text-[12px]" @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                </div>
                                <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <label class="text-[12px] font-black uppercase tracking-wider">Retour station</label>
                                    <input v-model="form.retour_station" type="datetime-local"
                                           class="input-line w-full text-[12px]" @input="(e) => (e.target as HTMLInputElement).blur()"/>
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
                                </span> — action irréversible.
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