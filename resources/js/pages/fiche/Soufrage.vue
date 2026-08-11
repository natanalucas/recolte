<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import { Trash2, Plus, Save, CheckCircle2, Pencil, X, ChevronDown, Check } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [];

// ── Types ─────────────────────────────────────────────
interface Operateur {
    id: number;
    nom: string;
    prenom: string;
    travail: 'jour' | 'nuit';
}

interface Raqt {
    id: number;
    nom: string;
    prenom: string;
}

interface Parcelle {
    id: number;
    num: string;
    localisation: string | null;
    producteur?: { id: number; nom: string; prenom: string; societe_id: number } | null;
}

interface FicheReception {
    id: number;
    fiche_number: string | number | null;
    caissette_total: number;
    parcelle_id: number | null;
}

interface TypeCertification { id: number; nom: string; }

interface CodeTraca {
    id: number;
    code: string;
    societe_id?: number | null;
}

interface SoufrageRecord {
    id: number;
    enqueteur_id: number | null;
    fiche_number: string | null;
    lieu_traitement: string | null;
    cycle: string;
    box: string | number | null;
    concent: string;
    parcelle_id: number | null;
    code: string;
    caissette: number | null;
    soufre: number | null;
    debut: string;
    fin: string;
    controle_raqt: boolean;
    reception_id: number | null;
    societe_id: number | null;
    operateur: { id: number; nom: string; prenom: string; travail: 'jour' | 'nuit' } | null;
    raqt: { id: number; nom: string; prenom: string } | null;
    parcelle?: { id: number; num: string; localisation: string | null } | null;
    reception?: { id: number; fiche_number: string | number | null } | null;
    code_traca?: CodeTraca | null;
    created_at: string;
}

// ── Props ─────────────────────────────────────────────
const props = defineProps<{
    certifications: TypeCertification[];
    operateurs: Operateur[];
    raqts: Raqt[];
    soufrages: {
        data: SoufrageRecord[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
    };
    parcelles: Parcelle[];
    receptions: FicheReception[];
    enqueteurs: { id: number; nom: string; prenom: string; poste: string; societe_id: number | null }[];
    societes: { id: number; nom: string }[];
    isAdmin: boolean;
    isManager: boolean;
    currentEnqueteurId: number | null;
    currentEnqueteurLabel: string | null;
}>();

// ── Computed pour le rôle ──────────────────────────────
const isEnqueteur = computed(() => !props.isAdmin && !props.isManager);

// ── État global de la fiche ───────────────────────────
const enqueteurId = ref<number | null>(
    props.isAdmin || props.isManager ? null : props.currentEnqueteurId
);
const ficheNumber = ref('');
const selectedRaqt = ref<number | null>(null);
const lieuTraitement = ref('');

// ── Compteur cycle ────────────────────────────────────
const cycleCounter = ref(
    props.soufrages.data.length > 0
        ? Math.max(...props.soufrages.data.map(s => parseInt(s.cycle) || 0)) + 1
        : 1
);

// ── Formulaire d'ajout ────────────────────────────────
const makeForm = () => ({
    cycle: String(cycleCounter.value).padStart(3, '0'),
    box: '',
    concent: '',
    parcelle_id: null as number | null,
    code: '',
    caissette: null as number | null,
    soufre: null as number | null,
    debut: new Date().toISOString().slice(0, 16),
    fin: '',
    operateur_id: null as number | null,
    controle_raqt: false,
    reception_id: null as number | null,
    societe_id: null as number | null,
});

const form = reactive(makeForm());

// ── Filtrage dynamique pour Admin ─────────────────────
const filteredParcelles = computed(() => {
    if (!props.isAdmin) return props.parcelles;
    if (!form.societe_id) return [];
    return props.parcelles.filter(p => p.producteur?.societe_id === form.societe_id);
});

const filteredEnqueteurs = computed(() => {
    if (!props.isAdmin) return props.enqueteurs;
    if (!form.societe_id) return [];
    return props.enqueteurs.filter(e => e.societe_id === form.societe_id);
});

// Réinitialiser enquêteur si société change (admin)
watch(() => form.societe_id, () => {
    if (props.isAdmin) {
        enqueteurId.value = null;
    }
});

// ── Auto-remplissage de la caissette ──────────────────
watch(() => form.reception_id, () => {
    const reception = props.receptions.find(r => r.id === form.reception_id);
    form.caissette = reception ? reception.caissette_total : null;
    form.parcelle_id = reception ? reception.parcelle_id : null;
});

const resetForm = () => {
    cycleCounter.value++;
    const fresh = makeForm();
    Object.assign(form, fresh);
    formError.value = null;
    formSuccess.value = false;
};

// ── État du formulaire ────────────────────────────────
const formSaving = ref(false);
const formError = ref<string | null>(null);
const formSuccess = ref(false);

// ── Soumission formulaire ─────────────────────────────
const submitForm = () => {
    if (!enqueteurId.value && !isEnqueteur.value) {
        formError.value = 'Veuillez sélectionner un Enquêteur.';
        return;
    }
    if (!selectedRaqt.value) {
        formError.value = 'Veuillez sélectionner un RAQT.';
        return;
    }
    if (!form.code || form.code.length < 5) {
        formError.value = 'Le code de traçabilité doit faire au moins 5 caractères.';
        return;
    }

    formSaving.value = true;
    formError.value = null;
    formSuccess.value = false;

    const payload = {
        enqueteur_id: props.isAdmin || props.isManager ? enqueteurId.value : props.currentEnqueteurId,
        fiche_number: ficheNumber.value,
        raqt_id: selectedRaqt.value,
        lieu_traitement: lieuTraitement.value,
        ...form,
    };

    router.post(route('soufrage.store'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            formSaving.value = false;
            formSuccess.value = true;
            resetForm();
        },
        onError: (errors) => {
            formError.value = Object.values(errors)[0] as string ?? 'Erreur inconnue';
            formSaving.value = false;
        },
    });
};

// ── Helpers ─────────────────────────────────────────────
const fmtDate = (d: string | null) => {
    if (!d) return '—';
    return new Date(d).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const certificationName = (id: string | number | null) => {
    if (!id) return null;
    return props.certifications.find(c => String(c.id) === String(id))?.nom ?? id;
};

const operateurLabel = (op: SoufrageRecord['operateur']) => {
    if (!op) return '—';
    return `${op.prenom} ${op.nom}`;
};

// ── État du tableau & Réactivité Contrôle RAQT ────────
const controleMap = reactive<Record<number, boolean>>({});

watch(() => props.soufrages.data, (newData) => {
    newData.forEach(s => {
        controleMap[s.id] = s.controle_raqt;
    });
}, { immediate: true, deep: true });

const savingControle = reactive<Record<number, boolean>>({});

const toggleControle = (id: number) => {
    const currentValue = controleMap[id];
    const newValue = !currentValue;

    controleMap[id] = newValue;
    savingControle[id] = true;

    router.patch(
        route('soufrage.update', id),
        { controle_raqt: newValue },
        {
            preserveScroll: true,
            onFinish: () => {
                savingControle[id] = false;
            },
            onError: () => {
                controleMap[id] = currentValue;
            },
        }
    );
};

// ── Édition d'une ligne ───────────────────────────────
const editingId = ref<number | null>(null);
const editForm = reactive({
    cycle: '',
    box: '',
    concent: '',
    parcelle_id: null as number | null,
    code: '',
    caissette: null as number | null,
    soufre: null as number | null,
    debut: '',
    fin: '',
    operateur_id: null as number | null,
    controle_raqt: false,
    reception_id: null as number | null,
    societe_id: null as number | null,
});
const editSaving = ref(false);
const editError = ref<string | null>(null);
const suppressEditReceptionWatch = ref(false);

const openEdit = (row: SoufrageRecord) => {
    editingId.value = row.id;
    suppressEditReceptionWatch.value = true;
    Object.assign(editForm, {
        cycle: row.cycle,
        box: row.box !== null && row.box !== undefined ? String(row.box) : '',
        concent: row.concent || '',
        parcelle_id: row.parcelle_id,
        code: row.code || row.code_traca?.code || '',
        caissette: row.caissette,
        soufre: row.soufre,
        debut: row.debut?.slice(0, 16) ?? '',
        fin: row.fin?.slice(0, 16) ?? '',
        operateur_id: row.operateur?.id ?? null,
        controle_raqt: row.controle_raqt,
        reception_id: row.reception_id ?? row.reception?.id ?? null,
        societe_id: row.societe_id ?? null,
    });
    editError.value = null;
    nextTick(() => {
        suppressEditReceptionWatch.value = false;
    });
};

const closeEdit = () => {
    editingId.value = null;
    editError.value = null;
};

// Auto-remplissage de la caissette en édition
watch(() => editForm.reception_id, () => {
    if (suppressEditReceptionWatch.value) return;
    const reception = props.receptions.find(r => r.id === editForm.reception_id);
    editForm.caissette = reception ? reception.caissette_total : null;
    editForm.parcelle_id = reception ? reception.parcelle_id : null;
});

const saveEdit = () => {
    if (!editingId.value) return;
    if (editForm.code.length < 5) {
        editError.value = 'Le code de traçabilité doit faire au moins 5 caractères.';
        return;
    }
    editSaving.value = true;
    editError.value = null;

    const payload = {
        cycle: editForm.cycle,
        box: editForm.box,
        concent: editForm.concent,
        parcelle_id: editForm.parcelle_id,
        code: editForm.code,
        caissette: editForm.caissette,
        soufre: editForm.soufre,
        debut: editForm.debut,
        fin: editForm.fin,
        operateur_id: editForm.operateur_id,
        controle_raqt: editForm.controle_raqt,
        reception_id: editForm.reception_id,
        societe_id: editForm.societe_id,
    };

    router.put(route('soufrage.update', editingId.value), payload, {
        preserveScroll: true,
        onSuccess: () => {
            editSaving.value = false;
            closeEdit();
        },
        onError: (errors) => {
            editError.value = Object.values(errors)[0] as string ?? 'Erreur lors de la modification';
            editSaving.value = false;
        },
    });
};

// ── Suppression ───────────────────────────────────────
const deleteTarget = ref<SoufrageRecord | null>(null);
const showDeleteModal = ref(false);
const deleting = ref(false);

const confirmDelete = (row: SoufrageRecord) => {
    deleteTarget.value = row;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;

    router.delete(route('soufrage.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteTarget.value = null;
            deleting.value = false;
        },
        onError: () => {
            deleting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Registre de Soufrage" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 bg-[var(--background)] text-[var(--text)] font-sans">
            <HeaderFiche title="Soufrage" v-model:agentName="ficheNumber" :enqueteurs="props.enqueteurs" />

            <div class="flex gap-4">
                <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm flex-1 space-y-3">
                    <div class="flex items-baseline gap-3">
                        <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Produit traité :</span>
                        <p class="text-[12px] font-medium border-b border-[var(--sidebar-border)]/30 pb-1 flex-1">
                            Litchi, variété Kwai-Mee
                        </p>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Phytosanitaire :</span>
                        <p class="text-[12px] font-medium flex-1">
                            SOUFRE FLEUR à <span class="font-bold text-orange-500">99,9%</span>
                        </p>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Motif :</span>
                        <p class="text-[12px] font-medium italic flex-1">Conservation du litchi frais</p>
                    </div>
                </div>

                <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm flex-1 space-y-3">
                    <div class="flex items-baseline gap-3">
                        <span class="text-[12px] uppercase tracking-wider font-bold w-33 shrink-0">Lieu de traitement :</span>
                        <input v-model="lieuTraitement" type="text" class="input-line" />
                    </div>
                    <div class="flex items-baseline gap-3">
                        <span class="text-[12px] uppercase tracking-wider font-bold w-33 shrink-0">Type de traitement :</span>
                        <p class="text-[12px] font-medium flex-1">À l'anhydride sulfureux par fumigation</p>
                    </div>
                </div>
            </div>

            <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-[var(--brand-green)] flex items-center justify-between">
                    <h2 class="text-[13px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                        <Plus class="w-4 h-4" /> Nouvelle ligne de soufrage
                    </h2>
                    <span class="text-white text-[11px] font-bold uppercase tracking-widest">
                        Cycle de soufrage #{{ form.cycle }}
                    </span>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Ligne 1 : Société (admin) + Enquêteur + RAQT -->
                    <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Société - ADMIN uniquement -->
                        <div v-if="isAdmin" class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider text-[var(--brand-orange)]">
                                Société *
                            </label>
                            <select v-model="form.societe_id" class="input-line w-full" required>
                                <option :value="null">— choisir —</option>
                                <option v-for="s in props.societes" :key="s.id" :value="s.id">
                                    {{ s.nom }}
                                </option>
                            </select>
                        </div>

                        <!-- Enquêteur - ADMIN ou MANAGER -->
                        <div v-if="isAdmin || isManager" class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider text-[var(--brand-orange)]">
                                Enquêteur Responsable *
                            </label>
                            <select v-model="enqueteurId" class="input-line w-full">
                                <option :value="null">— choisir —</option>
                                <option v-for="e in filteredEnqueteurs" :key="e.id" :value="e.id">
                                    {{ e.prenom }} {{ e.nom }}
                                </option>
                            </select>
                        </div>
                        <!-- Enquêteur - ENQUÊTEUR (lecture seule) -->
                        <div v-else class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider text-[var(--brand-orange)]">
                                Enquêteur Responsable
                            </label>
                            <input type="text" class="input-line w-full" disabled
                                   :value="props.currentEnqueteurLabel || '—'" />
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider text-[var(--brand-orange)]">
                                Nom RAQT *
                            </label>
                            <select v-model="selectedRaqt" class="input-line w-full">
                                <option :value="null" disabled>
                                    {{ props.raqts.length === 0 ? 'Liste encore vide' : 'Sélectionner...' }}
                                </option>
                                <option v-for="r in props.raqts" :key="r.id" :value="r.id">
                                    {{ r.prenom }} {{ r.nom }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Ligne 2 : Réception, Caissette, Parcelle, Code Traça -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Fiche Réception</label>
                            <select v-model="form.reception_id" class="input-line w-full">
                                <option :value="null">—</option>
                                <option v-for="r in props.receptions" :key="r.id" :value="r.id">
                                    {{ r.fiche_number ?? r.id }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Qtté Caissette</label>
                            <input v-model="form.caissette" type="number" min="0" max="250" class="input-line w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Parcelle</label>
                            <select v-model="form.parcelle_id" class="input-line w-full">
                                <option :value="null" disabled>
                                    {{ filteredParcelles.length === 0 ? 'Aucune parcelle pour cette société' : 'Sélectionner...' }}
                                </option>
                                <option v-for="p in filteredParcelles" :key="p.id" :value="p.id">
                                    {{ p.num }}
                                    <template v-if="p.localisation"> — {{ p.localisation }}</template>
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Code Traçabilité *</label>
                            <input v-model="form.code" type="text"
                                class="input-line w-full font-mono tracking-widest text-center"
                                placeholder="5 caractères min" maxlength="10" />
                        </div>
                    </div>

                    <!-- Ligne 3 : Box, Concent, Soufre, Opérateur -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">N° Box</label>
                            <input v-model="form.box" type="number" class="input-line w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Concent. Soufre g/T</label>
                            <input v-model="form.concent" type="text" class="input-line w-full" placeholder="Concent." />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Qté Soufre (g)</label>
                            <input v-model="form.soufre" type="number" class="input-line w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Opérateur</label>
                            <select v-model="form.operateur_id" class="input-line w-full">
                                <option :value="null" disabled>
                                    {{ props.operateurs.length === 0 ? 'Liste encore vide' : 'Sélectionner...' }}
                                </option>
                                <option v-for="op in props.operateurs" :key="op.id" :value="op.id">
                                    {{ op.prenom }} {{ op.nom }} ({{ op.travail === 'jour' ? '☀️ Jour' : '🌙 Nuit' }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Ligne 4 : Dates et contrôle RAQT -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                            <label class="text-[12px] font-black uppercase tracking-wider">Début de Soufrage</label>
                            <input v-model="form.debut" type="datetime-local"
                                class="input-line w-full text-[12px] font-bold"
                                @input="(e) => (e.target as HTMLInputElement).blur()" />
                        </div>
                        <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                            <label class="text-[12px] font-black uppercase tracking-wider">Fin de Soufrage</label>
                            <input v-model="form.fin" type="datetime-local"
                                class="input-line w-full text-[12px] font-bold"
                                @input="(e) => (e.target as HTMLInputElement).blur()" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider">Contrôle RAQT</label>
                            <label class="flex items-center gap-3 cursor-pointer h-9">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.controle_raqt" class="sr-only peer" />
                                    <div class="w-10 h-5 rounded-full border-2 border-[var(--sidebar-border)] peer-checked:bg-[var(--brand-green)] peer-checked:border-[var(--brand-green)] transition-all relative">
                                        <div class="absolute top-0.5 left-0.5 w-3.5 h-3.5 rounded-full bg-[var(--sidebar-border)] peer-checked:translate-x-5 peer-checked:bg-white transition-all"></div>
                                    </div>
                                </div>
                                <span class="text-[12px] font-bold">
                                    {{ form.controle_raqt ? 'Contrôlé ✓' : 'Non contrôlé' }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer formulaire -->
                    <div class="flex items-center justify-between pt-2 border-t border-[var(--sidebar-border)]/30">
                        <p v-if="formError" class="text-[12px] font-bold text-red-500 flex items-center gap-1">
                            ⚠️ {{ formError }}
                        </p>
                        <p v-else-if="formSuccess" class="text-[12px] font-bold text-emerald-500 flex items-center gap-1">
                            <CheckCircle2 class="w-4 h-4" /> Ligne enregistrée avec succès
                        </p>
                        <span v-else class="text-[11px] opacity-40 uppercase tracking-widest font-bold">Prêt à enregistrer</span>

                        <div class="flex gap-3">
                            <button @click="resetForm" type="button"
                                class="h-10 px-5 border border-[var(--sidebar-border)] rounded-xl text-[12px] font-black uppercase tracking-widest hover:bg-[var(--sidebar-border)]/20 transition-all">
                                Réinitialiser
                            </button>
                            <button @click="submitForm" :disabled="formSaving"
                                class="h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all disabled:opacity-50">
                                <Save class="w-4 h-4" />
                                {{ formSaving ? 'Enregistrement...' : 'Enregistrer la ligne' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TABLEAU DES LIGNES ──────────────────────────────── -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-[13px] font-black uppercase tracking-widest flex items-center gap-2">
                        <ChevronDown class="w-4 h-4 text-[var(--brand-green)]" />
                        Lignes enregistrées
                        <span class="px-2 py-0.5 rounded-full bg-[var(--brand-green)]/10 text-[var(--brand-green)] text-[11px]">
                            {{ props.soufrages.total }}
                        </span>
                    </h2>
                </div>

                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[1400px]">
                            <thead>
                                <tr class="text-white bg-[var(--brand-green)]/80 text-[10px] font-black uppercase">
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-20">Cycle de Sougrafe</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-24">Fiche Réception</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-32">N° Parcelle</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-28">Code Traça</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-24">Qtté Caissette</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-20">N° Box</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-24">Concent. Soufre, g/T</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-28">Qtté Soufre Utilisée, g</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-40">Début de Soufrage</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-40">Fin de Soufrage</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-48">Opérateur</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-40">RAQT</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-36">Contrôle RAQT</th>
                                    <th class="px-3 py-3 text-center w-44">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-[var(--sidebar-border)]">
                                <tr v-if="props.soufrages.total === 0">
                                    <td colspan="14" class="py-12 text-center text-[12px] font-bold opacity-30 uppercase tracking-widest">
                                        Aucune ligne enregistrée
                                    </td>
                                </tr>

                                <template v-for="row in props.soufrages.data" :key="row.id">
                                    <tr v-if="editingId !== row.id" class="hover:bg-[var(--brand-green)]/5 transition-colors text-[12px]">
                                        <td class="px-3 py-3 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">{{ row.cycle }}</td>
                                        <td class="px-3 py-3 text-center text-[11px] font-bold border-r border-[var(--sidebar-border)]/30">
                                            {{ row.reception?.fiche_number ?? row.reception_id ?? '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            {{ row.parcelle ? row.parcelle.num : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center font-mono text-[11px] border-r border-[var(--sidebar-border)]/30">
                                            {{ row.code || row.code_traca?.code || '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">{{ row.caissette ?? '—' }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">{{ row.box || '—' }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <span v-if="row.concent" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[var(--brand-orange)]/10 text-[var(--brand-orange)]">
                                                {{ certificationName(row.concent) }}
                                            </span>
                                            <span v-else>—</span>
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">{{ row.soufre ?? '—' }}</td>
                                        <td class="px-3 py-3 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">{{ fmtDate(row.debut) }}</td>
                                        <td class="px-3 py-3 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">{{ fmtDate(row.fin) }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <span v-if="row.operateur" class="flex flex-col items-center gap-0.5">
                                                <span class="font-bold">{{ operateurLabel(row.operateur) }}</span>
                                                <span :class="row.operateur.travail === 'jour' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'" class="px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase">
                                                    {{ row.operateur.travail === 'jour' ? '☀️ Jour' : '🌙 Nuit' }}
                                                </span>
                                            </span>
                                            <span v-else class="opacity-30">—</span>
                                        </td>
                                        <td class="px-3 py-3 text-center text-[11px] font-bold border-r border-[var(--sidebar-border)]/30">
                                            {{ row.raqt ? `${row.raqt.prenom} ${row.raqt.nom}` : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <button @click="toggleControle(row.id)" :disabled="savingControle[row.id]"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase transition-all disabled:opacity-50"
                                                :class="controleMap[row.id] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 hover:bg-emerald-200' : 'bg-[var(--sidebar-border)]/20 hover:bg-[var(--sidebar-border)]/40'">
                                                <CheckCircle2 v-if="controleMap[row.id]" class="w-3 h-3" />
                                                <span>{{ controleMap[row.id] ? 'Contrôlé' : 'En attente' }}</span>
                                            </button>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="openEdit(row)" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-[var(--sidebar-border)] text-[10px] font-black uppercase hover:border-[var(--brand-green)] hover:text-[var(--brand-green)] transition-all">
                                                    <Pencil class="w-3 h-3" /> Modifier
                                                </button>
                                                <button @click="confirmDelete(row)" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-[var(--sidebar-border)] text-[10px] font-black uppercase hover:border-red-500 hover:text-red-500 transition-all">
                                                    <Trash2 class="w-3 h-3" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Ligne d'édition -->
                                    <tr v-else class="bg-[var(--brand-green)]/5 text-[12px] border-2 border-[var(--brand-green)]">
                                        <td class="px-2 py-2 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.cycle" type="text" class="input-line text-center w-full font-mono font-bold" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <select v-model="editForm.reception_id" class="input-line w-full text-center text-[11px]">
                                                <option :value="null">—</option>
                                                <option v-for="r in props.receptions" :key="r.id" :value="r.id">
                                                    {{ r.fiche_number ?? r.id }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <select v-model="editForm.parcelle_id" :disabled="!!editForm.reception_id" class="input-line w-full text-center font-bold disabled:opacity-60 disabled:cursor-not-allowed">
                                                <option :value="null">—</option>
                                                <option v-for="p in props.parcelles" :key="p.id" :value="p.id">
                                                    {{ p.num }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.code" type="text" class="input-line w-full text-center font-mono text-[11px]" placeholder="Code" maxlength="10" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.caissette" type="number" :disabled="!!editForm.reception_id" class="input-line text-center w-full disabled:opacity-60 disabled:cursor-not-allowed" placeholder="—" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.box" type="text" class="input-line text-center w-full" placeholder="—" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.concent" type="text" class="input-line text-center w-full" placeholder="—" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.soufre" type="number" class="input-line text-center w-full" placeholder="—" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30" @click="$event.currentTarget.querySelector('input').showPicker()">
                                            <input v-model="editForm.debut" type="datetime-local" class="input-line w-full text-[11px] font-medium" @input="(e) => (e.target as HTMLInputElement).blur()" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30" @click="$event.currentTarget.querySelector('input').showPicker()">
                                            <input v-model="editForm.fin" type="datetime-local" class="input-line w-full text-[11px] font-medium" @input="(e) => (e.target as HTMLInputElement).blur()" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <select v-model="editForm.operateur_id" class="input-line w-full text-center text-[11px]">
                                                <option :value="null">—</option>
                                                <option v-for="op in props.operateurs" :key="op.id" :value="op.id">
                                                    {{ op.prenom }} {{ op.nom }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30 text-[11px] opacity-40 italic">Inchangé</td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input type="checkbox" v-model="editForm.controle_raqt" class="w-4 h-4 rounded text-[var(--brand-green)] focus:ring-[var(--brand-green)]" />
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <div class="flex flex-col gap-1 items-center justify-center">
                                                <div class="flex gap-1">
                                                    <button @click="saveEdit" :disabled="editSaving" class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-black uppercase bg-[var(--brand-green)] text-white rounded-md shadow hover:opacity-90 transition-all disabled:opacity-50">
                                                        <Check class="w-3 h-3" />
                                                        <span>Sauver</span>
                                                    </button>
                                                    <button @click="closeEdit" :disabled="editSaving" class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-black uppercase border border-[var(--sidebar-border)] bg-[var(--card)] rounded-md hover:bg-[var(--sidebar-border)]/20 transition-all">
                                                        <X class="w-3 h-3" />
                                                    </button>
                                                </div>
                                                <p v-if="editError" class="text-red-500 text-[9px] font-bold mt-0.5 max-w-[120px] truncate" :title="editError">⚠️ Erreur</p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── MODAL DE CONFIRMATION DE SUPPRESSION ── -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-fade-in">
            <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl p-6 shadow-xl max-w-sm w-full space-y-4 animate-scale-in">
                <div class="flex items-center gap-3 text-red-500">
                    <div class="p-3 bg-red-500/10 rounded-xl">
                        <Trash2 class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[14px] font-black uppercase tracking-wider text-[var(--text)]">Supprimer la ligne ?</h3>
                        <p class="text-[11px] opacity-60 uppercase tracking-widest font-bold">Cette action est irréversible</p>
                    </div>
                </div>
                <p class="text-[12px] font-medium opacity-80 leading-relaxed">
                    Êtes-vous sûr de vouloir supprimer la ligne de soufrage pour le
                    <span class="font-mono font-bold text-red-500">Cycle #{{ deleteTarget?.cycle }}</span> ?
                </p>
                <div class="flex gap-3 pt-2 border-t border-[var(--sidebar-border)]/30">
                    <button @click="showDeleteModal = false; deleteTarget = null" type="button" :disabled="deleting"
                        class="flex-1 h-9 px-4 border border-[var(--sidebar-border)] rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-[var(--sidebar-border)]/20 transition-all disabled:opacity-50">
                        Annuler
                    </button>
                    <button @click="executeDelete" type="button" :disabled="deleting"
                        class="flex-1 h-9 px-4 bg-red-500 text-white rounded-xl text-[11px] font-black uppercase tracking-wider hover:bg-red-600 shadow-lg shadow-red-500/20 active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-1">
                        <Trash2 class="w-3.5 h-3.5" />
                        {{ deleting ? 'Suppression...' : 'Supprimer' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>