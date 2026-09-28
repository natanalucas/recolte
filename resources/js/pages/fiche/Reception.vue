<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import {
    Trash2, Plus, Save, CheckCircle2,
    Pencil, X, AlertTriangle, Weight, ChevronDown, Clock
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [];

// ─── Types ────────────────────────────────────────────────────────────────────

interface Parcelle {
    id: number;
    num: string;
    producteur: { id: number; nom: string; prenom: string; societe_id: number } | null;
}

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
    calibre:             string | null;
    qualite_livraison:   string | null;
    societe_id:          number | null;
}

interface Enqueteur {
    id: number;
    nom: string;
    prenom: string;
    poste: string;
    societe_id: number | null;
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
        from: number | null;
        to: number | null;
    };
    poids_par_caissette: number;
    enqueteurs: Enqueteur[];
    societes: { id: number; nom: string }[];
    isAdmin:             boolean;
    isManager:           boolean;
    currentEnqueteurId:  number | null;
    currentEnqueteurLabel: string | null;
    userSocieteId:       number | null;
}>();

// ─── Rôle courant ─────────────────────────────────────────────────────────────

const isEnqueteurRole = computed(() => !props.isAdmin && !props.isManager);

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
    societe_id:          null as number | null,
    parcelle_id:         null as number | null,
    voiture:             '',
    commune:             '',
    caissette:           null as number | null,
    pourcentage_dechet:  null as number | null,
    collecte:            new Date().toISOString().slice(0, 16),
    depart_champ:        '',
    retour_station:      '',
    calibre:             '',
    qualite_livraison:   '',
});

const modalSaving  = ref(false);
const modalError   = ref<string | null>(null);
const modalSuccess = ref(false);

// ─── Filtrage dynamique (incluant toujours l'élément actuel) ─────────────────

const filteredEnqueteurs = computed(() => {
    if (!props.isAdmin) return props.enqueteurs;
    
    let filtered = props.enqueteurs;
    if (form.societe_id) {
        filtered = filtered.filter(e => e.societe_id === form.societe_id);
    }
    
    if (form.enqueteur_id) {
        const current = props.enqueteurs.find(e => e.id === form.enqueteur_id);
        if (current && !filtered.some(e => e.id === current.id)) {
            filtered.push(current);
        }
    }
    return filtered;
});

const filteredParcelles = computed(() => {
    if (!props.isAdmin) return props.parcelles;
    
    let filtered = props.parcelles;
    if (form.societe_id) {
        filtered = filtered.filter(p => p.producteur?.societe_id === form.societe_id);
    }
    
    if (form.parcelle_id) {
        const current = props.parcelles.find(p => p.id === form.parcelle_id);
        if (current && !filtered.some(p => p.id === current.id)) {
            filtered.push(current);
        }
    }
    return filtered;
});

// ─── Watcher : réinitialisation des champs dépendants ──────────────────────
watch(() => form.societe_id, (newVal, oldVal) => {
    if (props.isAdmin && newVal !== oldVal) {
        form.enqueteur_id = null;
        form.parcelle_id = null;
    }
});

// ─── Calcul des kg ──────────────────────────────────────────────────────────

const quantiteKg = computed(() => {
    if (form.caissette && poidsGlobal.value) {
        return (form.caissette * poidsGlobal.value).toFixed(2);
    }
    return null;
});

// ─── Durée de transport (Réception station - Collecte) ─────────────────────────

const calcDuree = (debut: string | null | undefined, fin: string | null | undefined): string | null => {
    if (!debut || !fin) return null;
    const d = new Date(debut).getTime();
    const f = new Date(fin).getTime();
    if (isNaN(d) || isNaN(f)) return null;
    const diff = f - d;
    if (diff < 0) return null;

    const totalMin = Math.floor(diff / 60000);
    const h = Math.floor(totalMin / 60);
    const m = totalMin % 60;
    return h > 0
        ? `${h}h${m.toString().padStart(2, '0')}`
        : `${m} min`;
};

const dureeTransport = computed(() =>
    calcDuree(form.collecte, form.retour_station)
);

// ─── Ouverture modal ──────────────────────────────────────────────────────────

const openAddModal = () => {
    editingFicheId.value = null;
    const defaultSocieteId = props.isAdmin ? null : props.userSocieteId;
    Object.assign(form, {
        enqueteur_id:        isEnqueteurRole.value ? props.currentEnqueteurId : null,
        societe_id:          defaultSocieteId,
        parcelle_id:         null,
        voiture:             '',
        commune:             '',
        caissette:           null,
        pourcentage_dechet:  null,
        collecte:            new Date().toISOString().slice(0, 16),
        depart_champ:        '',
        retour_station:      '',
        calibre:             '',
        qualite_livraison:   '',
    });
    modalError.value   = null;
    modalSuccess.value = false;
    showModal.value    = true;
};

const openEditModal = (fiche: FicheReception) => {
    editingFicheId.value = fiche.id;
    const societeId = props.isAdmin ? (fiche.societe_id ?? null) : props.userSocieteId;
    Object.assign(form, {
        enqueteur_id:        isEnqueteurRole.value ? props.currentEnqueteurId : (fiche.enqueteur_id ?? null),
        societe_id:          societeId,
        parcelle_id:         fiche.parcelle_id ?? null,
        voiture:             fiche.voiture ?? '',
        commune:             fiche.commune ?? '',
        caissette:           fiche.caissette ?? null,
        pourcentage_dechet:  fiche.pourcentage_dechet ?? null,
        collecte:            fiche.collecte?.slice(0, 16) ?? '',
        depart_champ:        fiche.depart_champ?.slice(0, 16) ?? '',
        retour_station:      fiche.retour_station?.slice(0, 16) ?? '',
        calibre:             fiche.calibre ?? '',
        qualite_livraison:   fiche.qualite_livraison ?? '',
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

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, {}, { preserveScroll: true, preserveState: true, replace: true });
};
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
                                <th class="px-3 py-3 text-center border-r border-white/10">N° Parcelle</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Qtté Caissette Livrée</th>
                                <th class="px-3 py-3 text-center border-r border-white/10 bg-[var(--brand-green)]/60">Quantité (kg)</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Déchet (%)</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Calibre</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Qualité livraison</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">N° Immatriculation Voiture</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Commune et District</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Départ champ</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Reception station</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Collecte</th>
                                <th class="px-3 py-3 text-center border-r border-white/10">Heure de différence</th>
                                <th class="px-3 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--sidebar-border)]">

                            <tr v-if="props.fiches.total === 0">
                                <td colspan="13"
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
                                    {{ fiche.calibre || '—' }}
                                </td>

                                <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                    {{ fiche.qualite_livraison || '—' }}
                                </td>

                                <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                    {{ fiche.voiture || '—' }}
                                </td>

                                <td class="px-3 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                    {{ fiche.commune || '—' }}
                                </td>

                                <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">
                                    {{ fmtDate(fiche.depart_champ) }}
                                </td>

                                <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">
                                    <div>{{ fmtDate(fiche.retour_station) }}</div>
                                </td>

                                <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">
                                    {{ fmtDate(fiche.collecte) }}
                                </td>

                                <td class="px-3 py-2 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">
                                    <div v-if="calcDuree(fiche.collecte, fiche.retour_station)"
                                        class="inline-flex items-center gap-1 mt-0.5
                                               text-[11px] font-black text-[var(--brand-orange)]">
                                        <Clock class="w-2.5 h-2.5" />
                                        {{ calcDuree(fiche.collecte, fiche.retour_station) }}
                                    </div>
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
                            @click="goToPage(link.url)"
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

                            <!-- ── Société (admin uniquement) ──────────────── -->
                            <div v-if="isAdmin" class="space-y-1.5">
                                <label class="text-[12px] font-black uppercase tracking-wider">
                                    Société <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.societe_id" class="input-line w-full" required>
                                    <option :value="null">— choisir —</option>
                                    <option v-for="s in props.societes" :key="s.id" :value="s.id">
                                        {{ s.nom }}
                                    </option>
                                </select>
                            </div>

                            <!-- ── Enquêteur ──────────────────────────────── -->
                            <div class="space-y-1.5" v-if="isAdmin || isManager">
                                <label class="text-[12px] font-black uppercase tracking-wider">
                                    Enquêteur <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.enqueteur_id" class="input-line w-full" required>
                                    <option :value="null">— choisir —</option>
                                    <option v-for="e in filteredEnqueteurs" :key="e.id" :value="e.id">
                                        {{ e.prenom }} {{ e.nom }}
                                    </option>
                                </select>
                            </div>

                            <!-- ENQUÊTEUR : champ désactivé -->
                            <div v-else class="space-y-1.5">
                                <label class="text-[12px] font-black uppercase tracking-wider">Enquêteur</label>
                                <input type="text" class="input-line w-full" disabled
                                    :value="props.currentEnqueteurLabel || '—'" />
                            </div>

                            <!-- ── Champs de la fiche ────────────────────── -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        N° Parcelle
                                    </label>
                                    <select v-model="form.parcelle_id" class="input-line w-full">
                                        <option :value="null">— choisir —</option>
                                        <option v-for="p in filteredParcelles" :key="p.id" :value="p.id">
                                            {{ p.num }}
                                        </option>
                                    </select>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        N° Immatriculation Voiture
                                    </label>
                                    <input v-model="form.voiture" type="text" class="input-line w-full" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Commune et District
                                    </label>
                                    <input v-model="form.commune" type="text" class="input-line w-full" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Qtté Caissette Livrée
                                    </label>
                                    <input v-model="form.caissette" type="number" min="0" max="250" class="input-line w-full" />
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

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Calibre
                                    </label>
                                    <input v-model="form.calibre" type="text" class="input-line w-full" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-[12px] font-black uppercase tracking-wider">
                                        Qualité livraison
                                    </label>
                                    <input v-model="form.qualite_livraison" type="text" class="input-line w-full" />
                                </div>
                            </div>

                            <!-- ── Dates ──────────────────────────────────── -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <label class="text-[12px] font-black uppercase tracking-wider">Départ champ</label>
                                    <input v-model="form.depart_champ" type="datetime-local"
                                           class="input-line w-full text-[12px]" @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                </div>
                                <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <label class="text-[12px] font-black uppercase tracking-wider">Reception station</label>
                                    <input v-model="form.retour_station" type="datetime-local"
                                           class="input-line w-full text-[12px]" @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                </div>
                                <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                                    <label class="text-[12px] font-black uppercase tracking-wider">Collecte</label>
                                    <input v-model="form.collecte" type="datetime-local"
                                           class="input-line w-full text-[12px]" @input="(e) => (e.target as HTMLInputElement).blur()"/>
                                </div>
                            </div>

                            <!-- ── Durée de transport (Réception station − Collecte) ── -->
                            <div v-if="dureeTransport"
                                class="flex items-center gap-2 text-[12px] font-black uppercase tracking-widest text-[var(--brand-orange)]">
                                <Clock class="w-3.5 h-3.5" />
                                Heure de différence : {{ dureeTransport }}
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