<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, watch, computed, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import { Trash2, Plus, Save, CheckCircle2, Pencil, X, AlertTriangle, ChevronDown, QrCode } from 'lucide-vue-next';
import QRCode from 'qrcode';

const breadcrumbs: BreadcrumbItem[] = [];

interface TypeCertification { id: number; nom: string; }
interface TriageRecord {
    id: number; code_traca_id: string; type_carton: '2kg' | '5.5kg';
    type_certification_id: number | null; debut: string; fin: string;
    tapis: number[]; nombre: number | null; qualite: number;
    certification: TypeCertification | null;
}

const props = defineProps<{
    certifications: TypeCertification[];
    triages: TriageRecord[];
    souragesCodes: { id: number; code: string }[];
    enqueteurs: { id: number; nom: string; prenom: string; poste: string }[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [];
const agentName   = ref('');
const ficheNumber = ref('');

const makeForm = () => ({
    code_traca_id: null as number | null, type_carton: '5.5kg' as '2kg' | '5.5kg',
    type_certification_id: null as number | null,
    debut: new Date().toISOString().slice(0, 16), fin: '',
    tapis: [] as number[], nombre: null as number | null, qualite: 1,
});

const form        = reactive(makeForm());
const formSaving  = ref(false);
const formError   = ref<string | null>(null);
const formSuccess = ref(false);

const toggleTapis = (n: number) => {
    const i = form.tapis.indexOf(n);
    if (i === -1) form.tapis.push(n); else form.tapis.splice(i, 1);
};
const resetForm = () => { Object.assign(form, makeForm()); formError.value = null; formSuccess.value = false; };
const submitForm = () => {
    if (!form.code_traca_id) { formError.value = 'Le code de traçabilité est requis.'; return; }
    formSaving.value = true; formError.value = null; formSuccess.value = false;
    router.post(route('triage.store'), {
        agent_name: agentName.value, fiche_number: ficheNumber.value,
        ...form, tapis: JSON.stringify(form.tapis),
    }, {
        preserveScroll: true,
        onSuccess: () => { formSaving.value = false; formSuccess.value = true; resetForm(); },
        onError:   (e) => { formError.value = Object.values(e)[0] as string ?? 'Erreur'; formSaving.value = false; },
    });
};

const editingId  = ref<number | null>(null);
const editForm   = reactive<any>({});
const editSaving = ref(false);
const editError  = ref<string | null>(null);

const openEdit = (row: TriageRecord) => {
    editingId.value = row.id;
    Object.assign(editForm, {
        code_traca_id: row.code_traca_id, type_carton: row.type_carton,
        type_certification_id: row.certification?.id ?? null,
        debut: row.debut?.slice(0, 16) ?? '', fin: row.fin?.slice(0, 16) ?? '',
        tapis: [...(row.tapis ?? [])], nombre: row.nombre, qualite: row.qualite,
    });
    editError.value = null;
};
const closeEdit = () => { editingId.value = null; editError.value = null; };
const toggleEditTapis = (n: number) => {
    const i = editForm.tapis.indexOf(n);
    if (i === -1) editForm.tapis.push(n); else editForm.tapis.splice(i, 1);
};
const saveEdit = () => {
    if (!editingId.value) return;
    editSaving.value = true; editError.value = null;
    router.put(route('triage.update', editingId.value), {
        ...editForm, tapis: JSON.stringify(editForm.tapis),
    }, {
        preserveScroll: true,
        onSuccess: () => { editSaving.value = false; closeEdit(); },
        onError:   (e) => { editError.value = Object.values(e)[0] as string ?? 'Erreur'; editSaving.value = false; },
    });
};

const deleteTarget    = ref<TriageRecord | null>(null);
const showDeleteModal = ref(false);
const deleting        = ref(false);
const confirmDelete = (row: TriageRecord) => { deleteTarget.value = row; showDeleteModal.value = true; };
const executeDelete = () => {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(route('triage.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { showDeleteModal.value = false; deleteTarget.value = null; deleting.value = false; },
        onError:   () => { deleting.value = false; },
    });
};

// ── QR Code ───────────────────────────────────────────
const qrTarget    = ref<TriageRecord | null>(null);
const showQrModal = ref(false);
const qrCanvasRef = ref<HTMLCanvasElement | null>(null);
const qrGenerating = ref(false);

const qrForm = reactive({
    producteur:   '',
    date_recolte: '',
    num_parcelle: '',
    num_lot:      '',
});

const openQr = (row: TriageRecord) => {
    qrTarget.value       = row;
    qrForm.producteur    = '';
    qrForm.date_recolte  = row.debut?.slice(0, 10) ?? '';
    
    // 1. Trouver l'objet code correspondant à l'ID de la ligne
    const codeObjet = props.souragesCodes.find(s => s.id === row.code_traca_id);
    // 2. Récupérer la chaîne de caractères (ex: "12ABC") ou mettre une valeur vide par défaut
    const codeTexte = codeObjet ? codeObjet.code : '';

    // 3. Assigner le numéro de lot (le code complet)
    qrForm.num_lot       = codeTexte;
    
    // 4. Assigner la parcelle (les 2 premiers caractères du code)
    qrForm.num_parcelle  = codeTexte ? codeTexte.slice(0, 2) : '';
    
    showQrModal.value    = true;
};

const qrText = computed(() => {
    if (!qrTarget.value) return '';
    const cert = qrTarget.value.certification?.nom ?? 'Non certifié';
    return [
        `Nom du producteur: ${qrForm.producteur || '—'}`,
        `Date de récolte du litchi: ${qrForm.date_recolte || '—'}`,
        `Numéro de la parcelle de litchi récolté: ${qrForm.num_parcelle || '—'}`,
        `Numéro de lot du litchi récolté: ${qrForm.num_lot || '—'}`,
        `Litchi certifié: ${cert}`,
    ].join('\n');
});

const generateQr = async () => {
    if (!qrCanvasRef.value) return;
    qrGenerating.value = true;
    await nextTick();
    try {
        // @ts-ignore
        await QRCode.toCanvas(qrCanvasRef.value, qrText.value, {
            width: 256, margin: 2,
            color: { dark: '#1a2e1a', light: '#f8fdf8' },
        });
    } catch (e) { console.error(e); }
    qrGenerating.value = false;
};

// Re-génère à chaque ouverture ou changement de champ
watch(showQrModal, async (v) => { if (v) { await nextTick(); generateQr(); } });
watch(qrForm, () => { if (showQrModal.value) generateQr(); }, { deep: true });

const downloadQr = () => {
    if (!qrCanvasRef.value) return;
    const link = document.createElement('a');
    link.download = `qrcode-litchi-${qrTarget.value?.code ?? 'lot'}.png`;
    link.href = qrCanvasRef.value.toDataURL('image/png');
    link.click();
};

// ── Helpers ───────────────────────────────────────────
const fmtDate = (d: string | null) => {
    if (!d) return '—';
    return new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
};
const certifAbbr = (nom: string) => nom.slice(0, 2).toUpperCase();

const getCodeLibelle = (id) => {
  if (!id || !props.souragesCodes) return '—';
  
  // On cherche l'objet qui a le bon ID
  const found = props.souragesCodes.find(s => s.id === id);
  
  // Si on l'a trouvé, on retourne son code, sinon un tiret
  return found ? found.code : '—';
};

</script>

<template>
    <Head title="Fiche de Triage" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 bg-[var(--background)] text-[var(--text)] font-sans">

            <HeaderFiche
                title="Registre de Soufrage"
                v-model:agentName="agentName"
                :enqueteurs="props.enqueteurs"
            />

            <!-- ── Légende certifications ── -->
            <div class="flex justify-between items-center text-sm font-medium">
                <div class="flex flex-wrap gap-3">
                    <div v-for="cert in props.certifications" :key="cert.id" class="flex items-center gap-2">
                        <span class="text-[10px] font-black bg-[var(--card)] px-2 py-0.5 rounded-lg border border-[var(--sidebar-border)] uppercase tracking-wider">
                            {{ certifAbbr(cert.nom) }}
                        </span>
                        <span class="text-[12px] tracking-wide font-bold">{{ cert.nom }}</span>
                    </div>
                    <span v-if="props.certifications.length === 0" class="text-[11px] italic opacity-40">Aucune certification enregistrée</span>
                </div>
                <div class="italic text-[12px] opacity-60">
                    * Qualité de soufrage : <span class="font-black">1 à 3</span>
                </div>
            </div>

            <!-- ── FORMULAIRE D'AJOUT ── -->
            <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-[var(--brand-green)] flex items-center justify-between">
                    <h2 class="text-[13px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                        <Plus class="w-4 h-4" /> Nouvelle ligne de triage
                    </h2>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Code de traçabilité *</label>
                            <select v-model="form.code_traca_id" class="input-line w-full">
                                <option value="" disabled>Sélectionner un code...</option>
                                <option v-for="s in props.souragesCodes" :key="s.id" :value="s.id">{{ s.code }}</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Type de carton</label>
                            <div class="flex gap-2">
                                <button v-for="p in ['2kg', '5.5kg']" :key="p" type="button"
                                    @click="form.type_carton = p as '2kg' | '5.5kg'"
                                    :class="form.type_carton === p ? 'bg-[var(--brand-green)] text-white border-[var(--brand-green)] shadow-lg shadow-[var(--brand-green)]/20' : 'border-[var(--sidebar-border)] hover:border-[var(--brand-green)]/60'"
                                    class="flex-1 h-10 rounded-xl border-2 text-[12px] font-black uppercase tracking-widest transition-all">
                                    {{ p }}
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Type de certification</label>
                            <select v-model="form.type_certification_id" class="input-line w-full">
                                <option :value="null" disabled>{{ props.certifications.length === 0 ? 'Liste encore vide' : 'Sélectionner...' }}</option>
                                <option v-for="c in props.certifications" :key="c.id" :value="c.id">{{ c.nom }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Début de triage</label>
                            <input v-model="form.debut" type="datetime-local" class="input-line w-full text-[12px] font-bold"/>
                        </div>
                        <div class="space-y-1.5" @click="$event.currentTarget.querySelector('input').showPicker()">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Fin de triage</label>
                            <input v-model="form.fin" type="datetime-local" class="input-line w-full text-[12px] font-bold"/>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Nombre de cartons</label>
                            <input v-model="form.nombre" type="number" class="input-line w-full" placeholder="—"/>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Qualité de soufrage</label>
                            <div class="flex gap-2">
                                <button v-for="q in [1, 2, 3]" :key="q" type="button"
                                    @click="form.qualite = q"
                                    :class="form.qualite === q ? 'bg-[var(--brand-orange)] text-black border-[var(--brand-orange)]' : 'border-[var(--sidebar-border)] hover:border-[var(--brand-orange)]/60'"
                                    class="flex-1 h-10 rounded-xl border-2 text-[12px] font-black transition-all">
                                    {{ q }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-wider opacity-60">
                            N° du tapis utilisé
                            <span v-if="form.tapis.length > 0" class="ml-2 px-2 py-0.5 rounded-full bg-[var(--brand-green)]/10 text-[var(--brand-green)] text-[10px] font-black normal-case">
                                {{ form.tapis.slice().sort((a,b)=>a-b).map(n=>'T'+n).join(', ') }}
                            </span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="n in 14" :key="n" type="button" @click="toggleTapis(n)"
                                :class="form.tapis.includes(n) ? 'bg-[var(--brand-green)] text-white border-[var(--brand-green)] shadow shadow-[var(--brand-green)]/30' : 'border-[var(--sidebar-border)] hover:border-[var(--brand-green)]/60 text-[var(--text)]'"
                                class="w-10 h-10 rounded-xl border-2 text-[12px] font-black uppercase tracking-widest transition-all active:scale-95">
                                T{{ n }}
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-[var(--sidebar-border)]/30">
                        <p v-if="formError" class="text-[12px] font-bold text-red-500">⚠️ {{ formError }}</p>
                        <p v-else-if="formSuccess" class="text-[12px] font-bold text-emerald-500 flex items-center gap-1">
                            <CheckCircle2 class="w-4 h-4" /> Ligne enregistrée
                        </p>
                        <span v-else class="text-[11px] opacity-40 uppercase tracking-widest font-bold">Prêt à enregistrer</span>
                        <div class="flex gap-3">
                            <button @click="resetForm" type="button"
                                class="h-10 px-5 border border-[var(--sidebar-border)] rounded-xl text-[12px] font-black uppercase tracking-widest hover:bg-[var(--sidebar-border)]/20 transition-all">
                                Réinitialiser
                            </button>
                            <button @click="submitForm" :disabled="formSaving"
                                class="h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all disabled:opacity-50">
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

            <!-- ── TABLEAU ── -->
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-[13px] font-black uppercase tracking-widest flex items-center gap-2">
                        <ChevronDown class="w-4 h-4 text-[var(--brand-green)]" />
                        Lignes enregistrées
                        <span class="px-2 py-0.5 rounded-full bg-[var(--brand-green)]/10 text-[var(--brand-green)] text-[11px]">
                            {{ props.triages.length }}
                        </span>
                    </h2>
                </div>

                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[1200px]">
                            <thead>
                                <tr class="bg-[var(--brand-green)]/80 text-white text-[10px] font-black uppercase">
                                    <th class="px-3 py-3 text-center border-r border-white/10">Code</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10">Type Carton</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10">Certification</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10">Début</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10">Fin</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10">Tapis utilisés</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10">Nb Cartons</th>
                                    <th class="px-3 py-3 text-center border-r border-white/10">Qualité</th>
                                    <th class="px-3 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--sidebar-border)]">
                                <tr v-if="props.triages.length === 0">
                                    <td colspan="9" class="py-12 text-center text-[12px] font-bold opacity-30 uppercase tracking-widest">
                                        Aucune ligne enregistrée
                                    </td>
                                </tr>

                                <template v-for="row in props.triages" :key="row.id">
                                    <!-- Ligne normale -->
                                    <tr v-if="editingId !== row.id" class="hover:bg-[var(--brand-green)]/5 transition-colors text-[12px]">
                                        <td class="px-3 py-3 text-center font-mono font-black border-r border-[var(--sidebar-border)]/30">{{ getCodeLibelle(row.code_traca_id) || '—' }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[var(--brand-green)]/10 text-[var(--brand-green)] border border-[var(--brand-green)]/20">{{ row.type_carton }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <span v-if="row.certification" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ row.certification.nom }}</span>
                                            <span v-else class="opacity-30">—</span>
                                        </td>
                                        <td class="px-3 py-3 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">{{ fmtDate(row.debut) }}</td>
                                        <td class="px-3 py-3 text-center text-[11px] border-r border-[var(--sidebar-border)]/30">{{ fmtDate(row.fin) }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <div class="flex flex-wrap gap-1 justify-center">
                                                <span v-for="t in (row.tapis ?? []).slice().sort((a,b)=>a-b)" :key="t" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-[var(--brand-green)]/10 text-[var(--brand-green)]">T{{ t }}</span>
                                                <span v-if="!row.tapis?.length" class="opacity-30">—</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">{{ row.nombre ?? '—' }}</td>
                                        <td class="px-3 py-3 text-center border-r border-[var(--sidebar-border)]/30">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[var(--brand-orange)]/10 text-[var(--brand-orange)]">{{ row.qualite }}/3</span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button @click="openEdit(row)"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-[var(--sidebar-border)] text-[10px] font-black uppercase hover:border-[var(--brand-green)] hover:text-[var(--brand-green)] transition-all">
                                                    <Pencil class="w-3 h-3" /> Modifier
                                                </button>
                                                <!-- ── Bouton QR Code ── -->
                                                <button @click="openQr(row)"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-[var(--sidebar-border)] text-[10px] font-black uppercase hover:border-emerald-500 hover:text-emerald-600 transition-all"
                                                    title="Générer QR Code">
                                                    <QrCode class="w-3 h-3" /> QR
                                                </button>
                                                <button @click="confirmDelete(row)"
                                                    class="p-1.5 rounded-lg border border-[var(--sidebar-border)] text-[10px] hover:border-red-500 hover:text-red-500 transition-all">
                                                    <Trash2 class="w-3 h-3" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Ligne édition inline -->
                                    <tr v-else class="bg-[var(--brand-green)]/5 border-l-4 border-[var(--brand-green)]">
                                        <td colspan="9" class="p-4">
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[12px] font-black uppercase tracking-widest text-[var(--brand-green)] flex items-center gap-2">
                                                        <Pencil class="w-3 h-3" /> Modification — {{ getCodeLibelle(row.code_traca_id) }}
                                                    </span>
                                                    <button @click="closeEdit" class="opacity-40 hover:opacity-100 transition"><X class="w-4 h-4" /></button>
                                                </div>
                                                <div class="grid grid-cols-4 gap-3">
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-black uppercase opacity-50">Code</label>
                                                        <select v-model="editForm.code_traca_id" class="input-line w-full">
                                                            <option value="" disabled>Sélectionner un code...</option>
                                                            <option v-for="s in props.souragesCodes" :key="s.id" :value="s.id">{{ s.code }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-black uppercase opacity-50">Type de carton</label>
                                                        <div class="flex gap-2">
                                                            <button v-for="p in ['2kg', '5.5kg']" :key="p" type="button"
                                                                @click="editForm.type_carton = p"
                                                                :class="editForm.type_carton === p ? 'bg-[var(--brand-green)] text-white border-[var(--brand-green)]' : 'border-[var(--sidebar-border)]'"
                                                                class="flex-1 h-9 rounded-xl border-2 text-[11px] font-black transition-all">{{ p }}</button>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-black uppercase opacity-50">Certification</label>
                                                        <select v-model="editForm.type_certification_id" class="input-line w-full">
                                                            <option v-for="c in props.certifications" :key="c.id" :value="c.id">{{ c.nom }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-black uppercase opacity-50">Qualité</label>
                                                        <div class="flex gap-2">
                                                            <button v-for="q in [1, 2, 3]" :key="q" type="button"
                                                                @click="editForm.qualite = q"
                                                                :class="editForm.qualite === q ? 'bg-[var(--brand-orange)] text-black border-[var(--brand-orange)]' : 'border-[var(--sidebar-border)]'"
                                                                class="flex-1 h-9 rounded-xl border-2 text-[11px] font-black transition-all">{{ q }}</button>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-1" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                        <label class="text-[10px] font-black uppercase opacity-50">Début</label>
                                                        <input v-model="editForm.debut" type="datetime-local" class="input-line w-full text-[11px]" />
                                                    </div>
                                                    <div class="space-y-1" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                        <label class="text-[10px] font-black uppercase opacity-50">Fin</label>
                                                        <input v-model="editForm.fin" type="datetime-local" class="input-line w-full text-[11px]" />
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[10px] font-black uppercase opacity-50">Nombre de cartons</label>
                                                        <input v-model="editForm.nombre" type="number" class="input-line w-full" />
                                                    </div>
                                                </div>
                                                <div class="space-y-1.5">
                                                    <label class="text-[10px] font-black uppercase opacity-50">
                                                        Tapis utilisés
                                                        <span v-if="editForm.tapis?.length" class="ml-2 px-2 py-0.5 rounded-full bg-[var(--brand-green)]/10 text-[var(--brand-green)] text-[9px] font-black normal-case">
                                                            {{ editForm.tapis.slice().sort((a:number,b:number)=>a-b).map((n:number)=>'T'+n).join(', ') }}
                                                        </span>
                                                    </label>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <button v-for="n in 14" :key="n" type="button" @click="toggleEditTapis(n)"
                                                            :class="editForm.tapis?.includes(n) ? 'bg-[var(--brand-green)] text-white border-[var(--brand-green)]' : 'border-[var(--sidebar-border)] hover:border-[var(--brand-green)]/60'"
                                                            class="w-9 h-9 rounded-xl border-2 text-[11px] font-black transition-all active:scale-95">T{{ n }}</button>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between pt-2 border-t border-[var(--sidebar-border)]/30">
                                                    <p v-if="editError" class="text-[11px] font-bold text-red-500">⚠️ {{ editError }}</p>
                                                    <span v-else />
                                                    <div class="flex gap-2">
                                                        <button @click="closeEdit" class="h-9 px-4 border border-[var(--sidebar-border)] rounded-xl text-[11px] font-black uppercase hover:bg-[var(--sidebar-border)]/20 transition-all">Annuler</button>
                                                        <button @click="saveEdit" :disabled="editSaving"
                                                            class="h-9 px-5 bg-[var(--brand-green)] text-white rounded-xl text-[11px] font-black uppercase flex items-center gap-2 shadow active:scale-95 transition-all disabled:opacity-50">
                                                            <svg v-if="editSaving" class="animate-spin w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                                            </svg>
                                                            <Save v-else class="w-3 h-3" />
                                                            {{ editSaving ? 'Sauvegarde...' : 'Sauvegarder' }}
                                                        </button>
                                                    </div>
                                                </div>
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

        <!-- ══════════════════════════════════════════ -->
        <!-- ── Modales ── -->
        <!-- ══════════════════════════════════════════ -->
        <Teleport to="body">

            <!-- Modal Suppression -->
            <div v-if="showDeleteModal"
                class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50 p-4"
                @click.self="showDeleteModal = false">
                <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                    <div class="p-8 text-center space-y-4">
                        <div class="w-14 h-14 bg-red-100 dark:bg-red-950/40 text-red-500 rounded-full flex items-center justify-center mx-auto border border-red-200 dark:border-red-800">
                            <AlertTriangle class="w-7 h-7" />
                        </div>
                        <div>
                            <h2 class="font-black uppercase tracking-widest text-[var(--text)]">Supprimer la ligne ?</h2>
                            <p class="text-[12px] text-[var(--text)]/50 font-bold mt-1.5">
                                Code <span class="font-black text-[var(--text)]">{{ deleteTarget?.code }}</span> — cette action est irréversible.
                            </p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="showDeleteModal = false" :disabled="deleting"
                            class="flex-1 h-11 text-[12px] font-black uppercase border border-[var(--sidebar-border)] rounded-xl hover:bg-[var(--sidebar-border)]/20 transition-all disabled:opacity-50">
                            Annuler
                        </button>
                        <button @click="executeDelete" :disabled="deleting"
                            class="flex-1 h-11 text-[12px] font-black uppercase bg-red-600 text-white rounded-xl shadow-lg shadow-red-600/20 disabled:opacity-60 flex items-center justify-center gap-2 transition-all active:scale-95">
                            <svg v-if="deleting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            {{ deleting ? 'Suppression...' : 'Supprimer' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Modal QR Code ── -->
            <div v-if="showQrModal"
                class="fixed inset-0 bg-black/75 backdrop-blur-md flex items-center justify-center z-50 p-4"
                @click.self="showQrModal = false">
                <div class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">

                    <!-- Header -->
                    <div class="px-6 py-4 bg-[var(--brand-green)] flex items-center justify-between">
                        <h2 class="text-[13px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                            <QrCode class="w-4 h-4" />
                            QR Code — Lot {{ qrTarget?.code }}
                        </h2>
                        <button @click="showQrModal = false" class="text-white/70 hover:text-white transition">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 flex gap-8">

                        <!-- Formulaire de saisie -->
                        <div class="flex-1 space-y-4">
                            <p class="text-[11px] font-bold opacity-50 uppercase tracking-widest">
                                Compléter les informations du QR Code
                            </p>

                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Nom du producteur</label>
                                    <input v-model="qrForm.producteur" type="text"
                                        class="input-line w-full" placeholder="Ex: RAKOTO Jean" />
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Date de récolte du litchi</label>
                                    <input v-model="qrForm.date_recolte" type="date" class="input-line w-full text-[12px] font-bold" />
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Numéro de parcelle</label>
                                    <input v-model="qrForm.num_parcelle" type="text"
                                        class="input-line w-full" placeholder="Ex: P-042" />
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Numéro de lot</label>
                                    <input v-model="qrForm.num_lot" type="text"
                                        class="input-line w-full" placeholder="Pré-rempli depuis le code" />
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase tracking-wider opacity-60">Litchi certifié</label>
                                    <div class="input-line w-full text-[12px] font-black flex items-center gap-2">
                                        <span v-if="qrTarget?.certification"
                                            class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                            {{ qrTarget.certification.nom }}
                                        </span>
                                        <span v-else class="opacity-40 font-normal italic text-[11px]">Non certifié — modifiable depuis la ligne</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Aperçu texte -->
                            <div class="mt-3 p-3 rounded-xl bg-[var(--brand-green)]/5 border border-[var(--brand-green)]/20">
                                <p class="text-[9px] font-black uppercase opacity-50 mb-1.5 tracking-widest">Contenu encodé</p>
                                <pre class="text-[10px] font-mono opacity-70 whitespace-pre-wrap leading-relaxed">{{ qrText }}</pre>
                            </div>
                        </div>

                        <!-- QR Code + actions -->
                        <div class="flex flex-col items-center gap-4 pt-6">
                            <div class="p-3 bg-white rounded-2xl shadow-lg border border-[var(--sidebar-border)]">
                                <canvas ref="qrCanvasRef" class="block rounded-lg" />
                            </div>
                            <p class="text-[10px] font-bold opacity-40 uppercase tracking-widest text-center">
                                Durée de vie infinie<br/>données encodées en local
                            </p>
                            <button @click="downloadQr"
                                class="w-full h-10 bg-[var(--brand-green)] text-white rounded-xl text-[11px] font-black uppercase tracking-widest flex items-center justify-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                </svg>
                                Télécharger PNG
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </Teleport>

    </AppLayout>
</template>