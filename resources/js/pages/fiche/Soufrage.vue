<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, computed, watch } from 'vue';
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

interface SoufrageRecord {
    id: number;
    agent_name: string | null;
    fiche_number: string | null;
    lieu_traitement: string | null;
    cycle: string;
    box: string;
    concent: string;
    parcelle: string;
    code: string;
    caissette: number | null;
    soufre: number | null;
    debut: string;
    fin: string;
    controle_raqt: boolean;
    operateur: { id: number; nom: string; prenom: string; travail: 'jour' | 'nuit' } | null;
    raqt: { id: number; nom: string; prenom: string } | null;
    created_at: string;
}

interface Parcelle {
    id: number;
    num: string;
    localisation: string | null;
    producteur?: { nom: string } | null;
}

// ── Props ─────────────────────────────────────────────
const props = defineProps<{
    operateurs: Operateur[];
    raqts: Raqt[];
    soufrages: {
        data: SoufrageRecord[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    parcelles: Parcelle[]; 
    enqueteurs: { id: number; nom: string; prenom: string; poste: string }[];
}>();

// ── État global de la fiche ───────────────────────────
const agentName      = ref('');
const ficheNumber    = ref('');
const selectedRaqt   = ref<number | null>(null);
const lieuTraitement = ref('');

// ── Compteur cycle ────────────────────────────────────
const cycleCounter = ref(
    props.soufrages.data.length > 0
        ? Math.max(...props.soufrages.data.map(s => parseInt(s.cycle) || 0)) + 1
        : 1
);

// ── Formulaire d'ajout ────────────────────────────────
const makeForm = () => ({
    cycle:        String(cycleCounter.value).padStart(3, '0'),
    box:          '',
    concent:      '',
    parcelle:     '',
    code:         '',
    caissette:    null as number | null,
    soufre:       null as number | null,
    debut:        new Date().toISOString().slice(0, 16),
    fin:          '',
    operateur_id: null as number | null,
    controle_raqt: false,
});

const form        = reactive(makeForm());
const formSaving  = ref(false);
const formError   = ref<string | null>(null);
const formSuccess = ref(false);

// Code traçabilité calculé automatiquement pour l'ajout
watch([() => form.parcelle, () => form.cycle], () => {
    const parcelle = String(form.parcelle ?? '').padStart(2, '0').slice(-2);
    const cycle    = String(form.cycle).padStart(3, '0').slice(-3);
    form.code = parcelle + cycle;
});

const resetForm = () => {
    cycleCounter.value++;
    const fresh = makeForm();
    Object.assign(form, fresh);
    formError.value   = null;
    formSuccess.value = false;
};

// ── Soumission formulaire ─────────────────────────────
const submitForm = () => {
    if (!selectedRaqt.value) {
        formError.value = 'Veuillez sélectionner un RAQT.';
        return;
    }
    formSaving.value  = true;
    formError.value   = null;
    formSuccess.value = false;

    router.post(route('soufrage.store'), {
        agent_name:      agentName.value,
        fiche_number:    ficheNumber.value,
        raqt_id:         selectedRaqt.value,
        lieu_traitement: lieuTraitement.value,
        ...form,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            formSaving.value  = false;
            formSuccess.value = true;
            resetForm();
        },
        onError: (errors) => {
            formError.value  = Object.values(errors)[0] as string ?? 'Erreur inconnue';
            formSaving.value = false;
        },
    });
};

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

// ── État du tableau & Réactivité Contrôle RAQT ────────
const controleMap = reactive<Record<number, boolean>>({});

// Synchronisation initiale et lors des mises à jour d'Inertia (Résout le problème)
watch(() => props.soufrages.data, (newData) => {
    newData.forEach(s => {
        controleMap[s.id] = s.controle_raqt;
    });
}, { immediate: true, deep: true });

const savingControle = reactive<Record<number, boolean>>({});

const toggleControle = (id: number) => {
    const currentValue = controleMap[id];
    const newValue = !currentValue;
    
    // Changement optimiste pour l'UI
    controleMap[id] = newValue;
    savingControle[id] = true;

    router.patch(route('soufrage.update', id), {
        controle_raqt: newValue,
    }, {
        preserveScroll: true,
        onFinish: () => { 
            savingControle[id] = false; 
        },
        onError:  () => { 
            // En cas d'erreur côté serveur, retour en arrière (rollback)
            controleMap[id] = currentValue; 
        }, 
    });
};

// ── Édition d'une ligne ───────────────────────────────
const editingId  = ref<number | null>(null);
const editForm   = reactive({
    cycle: '',
    box: '',
    concent: '',
    parcelle: '',
    code: '',
    caissette: null as number | null,
    soufre: null as number | null,
    debut: '',
    fin: '',
    operateur_id: null as number | null,
    controle_raqt: false,
});
const editSaving = ref(false);
const editError  = ref<string | null>(null);

const openEdit = (row: SoufrageRecord) => {
    editingId.value = row.id;
    Object.assign(editForm, {
        cycle:         row.cycle,
        box:           row.box || '',
        concent:       row.concent || '',
        parcelle:      row.parcelle || '',
        code:          row.code,
        caissette:     row.caissette,
        soufre:        row.soufre,
        debut:         row.debut?.slice(0, 16) ?? '',
        fin:           row.fin?.slice(0, 16)   ?? '',
        operateur_id:  row.operateur?.id ?? null,
        controle_raqt: row.controle_raqt,
    });
    editError.value = null;
};

const closeEdit = () => {
    editingId.value = null;
    editError.value = null;
};

// Auto-calcul du code de traçabilité en édition
watch([() => editForm.parcelle, () => editForm.cycle], () => {
    if (!editingId.value) return;
    const parcelle = String(editForm.parcelle ?? '').padStart(2, '0').slice(-2);
    const cycle    = String(editForm.cycle ?? '').padStart(3, '0').slice(-3);
    editForm.code  = parcelle + cycle;
});

const saveEdit = () => {
    if (!editingId.value) return;
    editSaving.value = true;
    editError.value  = null;

    router.put(route('soufrage.update', editingId.value), {
        cycle:         editForm.cycle,
        box:           editForm.box,
        concent:       editForm.concent,
        parcelle:      editForm.parcelle,
        code:          editForm.code,
        caissette:     editForm.caissette,
        soufre:        editForm.soufre,
        debut:         editForm.debut,
        fin:           editForm.fin,
        operateur_id:  editForm.operateur_id,
        controle_raqt: editForm.controle_raqt,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editSaving.value = false;
            closeEdit();
        },
        onError: (errors) => {
            editError.value  = Object.values(errors)[0] as string ?? 'Erreur lors de la modification';
            editSaving.value = false;
        },
    });
};

const operateurLabel = (op: SoufrageRecord['operateur']) => {
    if (!op) return '—';
    return `${op.prenom} ${op.nom}`;
};

// ── Suppression ───────────────────────────────────────
const deleteTarget  = ref<SoufrageRecord | null>(null);
const showDeleteModal = ref(false);
const deleting      = ref(false);

const confirmDelete = (row: SoufrageRecord) => {
    deleteTarget.value  = row;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;

    router.delete(route('soufrage.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteTarget.value    = null;
            deleting.value        = false;
        },
        onError: () => { deleting.value = false; },
    });
};
</script>

<template>
    <Head title="Registre de Soufrage" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 bg-[var(--background)] text-[var(--text)] font-sans">

            <HeaderFiche
                title="Registre de Soufrage"
                v-model:agentName="agentName"
                :enqueteurs="props.enqueteurs"
            />

            <!-- ── Infos fiche ── -->
            <div class="flex gap-4">
                <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm flex-1 space-y-3">
                    <div class="flex items-baseline gap-3">
                        <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Produit traité :</span>
                        <p class="text-[12px] font-medium border-b border-[var(--sidebar-border)]/30 pb-1 flex-1">Litchi, variété Kwai-Mee</p>
                    </div>
                    <div class="flex items-baseline gap-3">
                        <span class="text-[13px] uppercase tracking-wider font-bold w-33 shrink-0">Phytosanitaire :</span>
                        <p class="text-[12px] font-medium flex-1">SOUFRE FLEUR à <span class="font-bold text-orange-500">99,9%</span></p>
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

            <!-- ── RAQT ── -->
            <div class="flex justify-end">
                <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-xl p-5 shadow-sm flex items-baseline gap-3">
                    <span class="text-[12px] uppercase tracking-wider font-bold shrink-0">Nom RAQT :</span>
                    <select v-model="selectedRaqt" class="input-line min-w-[200px]">
                        <option :value="null" disabled>
                            {{ props.raqts.length === 0 ? 'Liste encore vide' : 'Sélectionner...' }}
                        </option>
                        <option v-for="r in props.raqts" :key="r.id" :value="r.id">
                            {{ r.prenom }} {{ r.nom }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- ── FORMULAIRE D'AJOUT ── -->
            <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-[var(--brand-green)] flex items-center justify-between">
                    <h2 class="text-[13px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                        <Plus class="w-4 h-4" /> Nouvelle ligne de soufrage
                    </h2>
                    <span class="text-white/70 text-[11px] font-bold uppercase tracking-widest">
                        Cycle #{{ form.cycle }}
                    </span>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">N° Box</label>
                            <input v-model="form.box" type="number" class="input-line w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Concentration</label>
                            <select v-model="form.concent" class="input-line w-full">
                                <option value="" disabled>—</option>
                                <option value="G">G</option>
                                <option value="F">F</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Parcelle</label>
                            <select v-model="form.parcelle" class="input-line w-full">
                                <option value="" disabled>
                                    {{ props.parcelles.length === 0 ? 'Liste encore vide' : 'Sélectionner...' }}
                                </option>
                                <option v-for="p in props.parcelles" :key="p.id" :value="p.num">
                                    {{ p.num }}
                                    <template v-if="p.localisation"> — {{ p.localisation }}</template>
                                </option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Code Traçabilité</label>
                            <input :value="form.code" type="text"
                                class="input-line w-full font-mono tracking-widest text-center bg-[var(--sidebar-border)]/10 cursor-not-allowed opacity-70"
                                disabled placeholder="Auto" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Qté Caissette</label>
                            <input v-model="form.caissette" type="number" class="input-line w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Qté Soufre (g)</label>
                            <input v-model="form.soufre" type="number" class="input-line w-full" placeholder="—" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Opérateur</label>
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

                    <div class="grid grid-cols-3 gap-4 items-end">
                        <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Début</label>
                            <input v-model="form.debut" type="datetime-local" class="input-line w-full text-[12px] font-bold" />
                        </div>
                        <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Fin</label>
                            <input v-model="form.fin" type="datetime-local" class="input-line w-full text-[12px] font-bold" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Contrôle RAQT</label>
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

                    <div class="flex items-center justify-between pt-2 border-t border-[var(--sidebar-border)]/30">
                        <p v-if="formError" class="text-[12px] font-bold text-red-500 flex items-center gap-1">
                            ⚠️ {{ formError }}
                        </p>
                        <p v-else-if="formSuccess" class="text-[12px] font-bold text-emerald-500 flex items-center gap-1">
                            <CheckCircle2 class="w-4 h-4" /> Ligne enregistrée avec succès
                        </p>
                        <span v-else class="text-[11px] opacity-40 uppercase tracking-widest font-bold">Prêt à enregistrer</span>

                        <div class="flex gap-3">
                            <button @click="resetForm" type="button" class="h-10 px-5 border border-[var(--sidebar-border)] rounded-xl text-[12px] font-black uppercase tracking-widest hover:bg-[var(--sidebar-border)]/20 transition-all">
                                Réinitialiser
                            </button>
                            <button @click="submitForm" :disabled="formSaving" class="h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all disabled:opacity-50">
                                <svg v-if="formSaving" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                <Save v-else class="w-4 h-4" />
                                {{ formSaving ? 'Enregistrement...' : 'Enregistrer la ligne' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TABLEAU DE CONSULTATION ── -->
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
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-20">Cycle</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-20">Box</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-24">Conc.</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-32">Parcelle</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-28">Code</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-24">Caissette</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-28">Soufre (g)</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-40">Début</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-40">Fin</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-48">Opérateur</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-40">RAQT</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10 w-36">Contrôle RAQT</th>
                                    <th class="px-3 py-3 text-center w-44">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-[var(--sidebar-border)]">
                                <tr v-if="props.soufrages.total === 0">
                                    <td colspan="13" class="py-12 text-center text-[12px] font-bold opacity-30 uppercase tracking-widest">
                                        Aucune ligne enregistrée
                                    </td>
                                </tr>

                                <template v-for="row in props.soufrages.data" :key="row.id">
                                    <!-- ── Ligne normale ── -->
                                    <tr v-if="editingId !== row.id" class="hover:bg-[var(--brand-green)]/5 transition-colors text-[12px]">
                                        <td class="px-3 py-3 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">{{ row.cycle }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">{{ row.box || '—' }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <span v-if="row.concent" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[var(--brand-green)]/10 text-[var(--brand-green)]">
                                                {{ row.concent }}
                                            </span>
                                            <span v-else>—</span>
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">{{ row.parcelle || '—' }}</td>
                                        <td class="px-3 py-3 text-center font-mono text-[11px] border-r border-[var(--sidebar-border)]/30">
                                            {{ row.parcelle && row.cycle ? String(row.parcelle).padStart(2, '0').slice(0, 2) + row.cycle : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">{{ row.caissette ?? '—' }}</td>
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
                                            <button @click="toggleControle(row.id)" :disabled="savingControle[row.id]" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase transition-all disabled:opacity-50" :class="controleMap[row.id] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 hover:bg-emerald-200' : 'bg-[var(--sidebar-border)]/20 hover:bg-[var(--sidebar-border)]/40'">
                                                <svg v-if="savingControle[row.id]" class="animate-spin w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                                </svg>
                                                <CheckCircle2 v-else-if="controleMap[row.id]" class="w-3 h-3" />
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

                                    <!-- ── Ligne en mode édition ── -->
                                    <tr v-else class="bg-[var(--brand-green)]/5 text-[12px] border-2 border-[var(--brand-green)]">
                                        <td class="px-2 py-2 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.cycle" type="text" class="input-line text-center w-full font-mono font-bold" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.box" type="number" class="input-line text-center w-full" placeholder="—" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <select v-model="editForm.concent" class="input-line w-full text-center font-bold">
                                                <option value="">—</option>
                                                <option value="G">G</option>
                                                <option value="F">F</option>
                                                <option value="C">C</option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <select v-model="editForm.parcelle" class="input-line w-full text-center font-bold">
                                                <option value="">—</option>
                                                <option v-for="p in props.parcelles" :key="p.id" :value="p.num">
                                                    {{ p.num }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center font-mono text-[11px] bg-[var(--sidebar-border)]/10 text-center font-bold border-r border-[var(--sidebar-border)]/30 opacity-80">
                                            {{ editForm.code || '—' }}
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.caissette" type="number" class="input-line text-center w-full" placeholder="—" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.soufre" type="number" class="input-line text-center w-full" placeholder="—" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.debut" type="datetime-local" class="input-line w-full text-[11px] font-medium" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input v-model="editForm.fin" type="datetime-local" class="input-line w-full text-[11px] font-medium" />
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <select v-model="editForm.operateur_id" class="input-line w-full text-center text-[11px]">
                                                <option :value="null">—</option>
                                                <option v-for="op in props.operateurs" :key="op.id" :value="op.id">
                                                    {{ op.prenom }} {{ op.nom }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30 text-[11px] opacity-40 italic">
                                            Inchangé
                                        </td>
                                        <td class="px-2 py-2 text-center border-r border-[var(--sidebar-border)]/30">
                                            <input type="checkbox" v-model="editForm.controle_raqt" class="w-4 h-4 rounded text-[var(--brand-green)] focus:ring-[var(--brand-green)]" />
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <div class="flex flex-col gap-1 items-center justify-center">
                                                <div class="flex gap-1">
                                                    <button @click="saveEdit" :disabled="editSaving" class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-black uppercase bg-[var(--brand-green)] text-white rounded-md shadow hover:opacity-90 transition-all disabled:opacity-50">
                                                        <svg v-if="editSaving" class="animate-spin w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                                        </svg>
                                                        <Check v-else class="w-3 h-3" />
                                                        <span>Sauver</span>
                                                    </button>
                                                    <button @click="closeEdit" :disabled="editSaving" class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-black uppercase border border-[var(--sidebar-border)] bg-[var(--card)] rounded-md hover:bg-[var(--sidebar-border)]/20 transition-all">
                                                        <X class="w-3 h-3" />
                                                    </button>
                                                </div>
                                                <p v-if="editError" class="text-red-500 text-[9px] font-bold mt-0.5 max-w-[120px] truncate" :title="editError">
                                                    ⚠️ Erreur
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="props.soufrages.last_page > 1" class="flex items-center justify-between pt-3">
                    <span class="text-[11px] font-bold opacity-40 uppercase tracking-widest">
                        Page {{ props.soufrages.current_page }} / {{ props.soufrages.last_page }} · {{ props.soufrages.total }} lignes
                    </span>

                    <div class="flex items-center gap-1">
                        <template v-for="link in props.soufrages.links" :key="link.label">
                            <button v-if="link.url" @click="router.get(link.url, {}, { preserveScroll: true })" :class="['h-8 min-w-[2rem] px-2 rounded-lg text-[11px] font-black transition-all', link.active ? 'bg-[var(--brand-green)] text-white shadow shadow-[var(--brand-green)]/20' : 'border border-[var(--sidebar-border)] hover:border-[var(--brand-green)] hover:text-[var(--brand-green)]']" v-html="link.label" />
                            <span v-else class="h-8 min-w-[2rem] px-2 flex items-center justify-center text-[11px] opacity-25 font-black" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal Suppression ── -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50 p-4">
                <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                    <div class="p-8 text-center space-y-4">
                        <div class="w-14 h-14 bg-red-100 dark:bg-red-950/40 text-red-500 rounded-full flex items-center justify-center mx-auto border border-red-200 dark:border-red-800">
                            <Trash2 class="w-7 h-7" />
                        </div>
                        <div>
                            <h2 class="font-black uppercase tracking-widest text-[var(--text)]">Supprimer la ligne ?</h2>
                            <p class="text-[12px] text-[var(--text)]/50 font-bold mt-1.5">
                                Cycle <span class="font-black text-[var(--text)]">{{ deleteTarget?.cycle }}</span> — cette action est irréversible.
                            </p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="showDeleteModal = false; deleteTarget = null" :disabled="deleting" class="flex-1 h-11 text-[12px] font-black uppercase border border-[var(--sidebar-border)] rounded-xl hover:bg-[var(--sidebar-border)]/20 transition-all disabled:opacity-50">
                            Annuler
                        </button>
                        <button @click="executeDelete" :disabled="deleting" class="flex-1 h-11 text-[12px] font-black uppercase bg-red-600 text-white rounded-xl shadow-lg shadow-red-600/20 disabled:opacity-60 flex items-center justify-center gap-2 transition-all active:scale-95">
                            <svg v-if="deleting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ deleting ? 'Suppression...' : 'Supprimer' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>