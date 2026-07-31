<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import HeaderFiche from './HeaderFiche.vue';
import { Trash2, Plus, Loader2, AlertCircle, Pencil, X, Check, PackageOpen } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [];

// ---------------------------------------------------------------------------
// 1. Types — alignés sur les modèles Eloquent Paletisation / PaletisationLot
// ---------------------------------------------------------------------------
interface TypeCertificationOption {
    id: number;
    nom: string;
}

interface CodeTracaOption {
    id: number;
    code: string;
}

interface TriageCartonAgg {
    code_traca_id: number;
    nombre: number;
}

interface PaletisationLotApi {
    id?: number;
    lot_number: number;
    code_traca_id: number | null;
    nb_cartons: number | null;
}

interface PaletisationApi {
    id: number;
    enqueteur_id?: number | null;
    num_palette: string | null;
    type_carton: string | null;
    type_certification_id: number | null;
    debut: string | null;
    fin: string | null;
    lots: PaletisationLotApi[];
}

const props = defineProps<{
    ficheNumber?: string | null;
    paletisations: PaletisationApi[];
    typeCertifications: TypeCertificationOption[];
    souragesCodes: CodeTracaOption[];
    triageCartons: TriageCartonAgg[];
    enqueteurs: { id: number; nom: string; prenom: string; poste: string }[];
}>();

const enqueteurId = ref<number | null>(null);
// ---------------------------------------------------------------------------
// 2. Structures internes
// ---------------------------------------------------------------------------
interface LotForm {
    lot_number: number;
    code_traca_id: number | null;
    nb_cartons: number | null;
}

interface PaletisationFormState {
    enqueteurId: number | null;
    num_palette: string;
    type_carton: '2' | '5.5';
    type_certification_id: number | null;
    debut: string;
    fin: string;
    lots: [LotForm, LotForm, LotForm];
}

interface PaletisationRow extends PaletisationFormState {
    id: number;
}

const agentName = ref('');
const ficheNumber = ref(props.ficheNumber ?? '');

function blankLots(): [LotForm, LotForm, LotForm] {
    return [1, 2, 3].map((n) => ({ lot_number: n, code_traca_id: null, nb_cartons: null })) as [LotForm, LotForm, LotForm];
}

function toNumber(value: unknown): number {
    const n = Number(value);
    return Number.isFinite(n) ? n : 0;
}

function createBlankForm(): PaletisationFormState {
    return {
        enqueteurId: null,
        num_palette: '',
        type_carton: "5.5",
        type_certification_id: props.typeCertifications[0]?.id ?? null,
        debut: new Date().toISOString().slice(0, 16),
        fin: '',
        lots: blankLots(),
    };
}

function fromApi(p: PaletisationApi): PaletisationRow {
    const lots = blankLots();
    p.lots?.forEach((l) => {
        const target = lots[l.lot_number - 1];
        if (target) {
            target.code_traca_id = l.code_traca_id !== null && l.code_traca_id !== undefined ? toNumber(l.code_traca_id) : null;
            target.nb_cartons = l.nb_cartons !== null && l.nb_cartons !== undefined ? toNumber(l.nb_cartons) : null;
        }
    });

    return {
        id: p.id,
        enqueteurId: p.enqueteur_id ? toNumber(p.enqueteur_id) : null,
        num_palette: p.num_palette ?? '',
        type_carton: (p.type_carton as '2' | '5.5') ?? '5',
        type_certification_id: p.type_certification_id ?? props.typeCertifications[0]?.id ?? null,
        debut: p.debut ? p.debut.slice(0, 16) : '',
        fin: p.fin ? p.fin.slice(0, 16) : '',
        lots,
    };
}

const rows = ref<PaletisationRow[]>(props.paletisations.map(fromApi));

// ---------------------------------------------------------------------------
// 3. Lookup "nb. cartons" depuis le Triage, par code de traçabilité
// ---------------------------------------------------------------------------
const cartonsByCode = computed(() => {
    const map = new Map<number, number>();
    props.triageCartons.forEach((t) => map.set(t.code_traca_id, toNumber(t.nombre)));
    return map;
});

function onLotCodeChange(lot: LotForm) {
    if (lot.code_traca_id && !lot.nb_cartons) {
        const suggested = cartonsByCode.value.get(lot.code_traca_id);
        if (suggested !== undefined) lot.nb_cartons = suggested;
    }
}

// ---------------------------------------------------------------------------
// 3bis. Filtrage des codes de traçabilité (utilisés ailleurs + dans le formulaire)
// ---------------------------------------------------------------------------
const editingId = ref<number | null>(null);

// Codes déjà utilisés dans d'autres palettes (hors palette en cours d'édition)
const usedCodeTracaIds = computed(() => {
    const usedIds = new Set<number>();
    for (const pal of props.paletisations) {
        // Si on est en édition, ignorer la palette courante
        if (editingId.value && pal.id === editingId.value) continue;
        for (const lot of pal.lots) {
            if (lot.code_traca_id) {
                usedIds.add(lot.code_traca_id);
            }
        }
    }
    return usedIds;
});

function availableCodesForLot(idx: number): CodeTracaOption[] {
    // Codes sélectionnés dans les autres lots du formulaire (pour éviter les doublons internes)
    const selectedOtherCodes = form.lots
        .map((l, i) => (i !== idx ? l.code_traca_id : null))
        .filter((id): id is number => id !== null && id !== undefined);

    const currentCode = form.lots[idx].code_traca_id;
    const usedCodes = usedCodeTracaIds.value;

    return props.souragesCodes.filter((c) => {
        // 1. Exclure les codes déjà utilisés dans d'autres palettes (sauf le code courant)
        if (usedCodes.has(c.id) && c.id !== currentCode) return false;
        // 2. Exclure les codes déjà pris par un autre lot dans le formulaire (sauf le code courant)
        if (selectedOtherCodes.includes(c.id) && c.id !== currentCode) return false;
        return true;
    });
}

// ---------------------------------------------------------------------------
// 4. Modal — ajout / modification
// ---------------------------------------------------------------------------
const isModalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const form = reactive<PaletisationFormState>(createBlankForm());
const formSaving = ref(false);
const formError = ref<string | null>(null);
const modalPanelRef = ref<HTMLElement | null>(null);
const numPaletteInputRef = ref<HTMLInputElement | null>(null);

function resetForm() {
    Object.assign(form, createBlankForm());
    formError.value = null;
}

function openCreateModal() {
    modalMode.value = 'create';
    editingId.value = null;
    resetForm();
    isModalOpen.value = true;
    nextTick(() => numPaletteInputRef.value?.focus());
}

function openEditModal(row: PaletisationRow) {
    modalMode.value = 'edit';
    editingId.value = row.id;
    Object.assign(form, {
        enqueteurId: row.enqueteurId,
        num_palette: row.num_palette,
        type_carton: row.type_carton,
        type_certification_id: row.type_certification_id,
        debut: row.debut,
        fin: row.fin,
        lots: row.lots.map((l) => ({ ...l })) as [LotForm, LotForm, LotForm],
    });
    formError.value = null;
    isModalOpen.value = true;
    nextTick(() => numPaletteInputRef.value?.focus());
}

function closeModal() {
    if (formSaving.value) return;
    isModalOpen.value = false;
}

const formTotal = computed(() => form.lots.reduce((sum, l) => sum + toNumber(l.nb_cartons), 0));

function toPayload() {
    return {
        fiche_number: ficheNumber.value || null,
        enqueteur_id: form.enqueteurId,
        num_palette: form.num_palette,
        type_carton: form.type_carton,
        type_certification_id: form.type_certification_id,
        debut: form.debut || null,
        fin: form.fin || null,
        lots: form.lots.map((l) => ({
            code_traca_id: l.code_traca_id,
            nb_cartons: l.nb_cartons === null ? null : toNumber(l.nb_cartons),
        })),
    };
}

function submitForm() {
    if (!form.num_palette.trim()) {
        formError.value = 'Le numéro de palette est obligatoire.';
        return;
    }

    formSaving.value = true;
    formError.value = null;

    const onSuccess = (page: any) => {
        const list = (page.props.paletisations as PaletisationApi[]) ?? [];
        const wasCreate = modalMode.value === 'create';
        rows.value = list.map(fromApi);
        isModalOpen.value = false;
        if (wasCreate) currentPage.value = pageCount.value;
    };
    const onError = (errors: Record<string, string>) => {
        formError.value = (Object.values(errors)[0] as string) ?? "Erreur lors de l'enregistrement.";
    };
    const onFinish = () => {
        formSaving.value = false;
    };

    if (modalMode.value === 'create') {
        router.post('/palettisation', toPayload(), { preserveScroll: true, preserveState: true, onSuccess, onError, onFinish });
    } else if (editingId.value) {
        router.put(`/palettisation/${editingId.value}`, toPayload(), { preserveScroll: true, preserveState: true, onSuccess, onError, onFinish });
    }
}

// ---------------------------------------------------------------------------
// 5. Suppression (confirmation inline)
// ---------------------------------------------------------------------------
const confirmingDeleteId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const listError = ref<string | null>(null);

function requestDelete(id: number) {
    confirmingDeleteId.value = id;
}

function cancelDelete() {
    confirmingDeleteId.value = null;
}

function confirmDelete(id: number) {
    deletingId.value = id;
    listError.value = null;
    router.delete(`/palettisation/${id}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            rows.value = rows.value.filter((r) => r.id !== id);
        },
        onError: () => {
            listError.value = 'Suppression impossible, veuillez réessayer.';
        },
        onFinish: () => {
            deletingId.value = null;
            confirmingDeleteId.value = null;
        },
    });
}

// ---------------------------------------------------------------------------
// 6. Totaux & helpers d'affichage
// ---------------------------------------------------------------------------
const rowTotal = (row: PaletisationRow) => row.lots.reduce((sum, l) => sum + toNumber(l.nb_cartons), 0);
const grandTotal = computed(() => rows.value.reduce((sum, r) => sum + rowTotal(r), 0));

// ---------------------------------------------------------------------------
// 6bis. Pagination côté client
// ---------------------------------------------------------------------------
const pageSize = ref(10);
const currentPage = ref(1);
const pageCount = computed(() => Math.max(1, Math.ceil(rows.value.length / pageSize.value)));

watch([() => rows.value.length, pageSize], () => {
    if (currentPage.value > pageCount.value) currentPage.value = pageCount.value;
});

const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    return rows.value.slice(start, start + pageSize.value);
});

const paginationRangeLabel = computed(() => {
    if (rows.value.length === 0) return '0–0 sur 0';
    const start = (currentPage.value - 1) * pageSize.value + 1;
    const end = Math.min(rows.value.length, currentPage.value * pageSize.value);
    return `${start}–${end} sur ${rows.value.length}`;
});

function goToPage(page: number) {
    currentPage.value = Math.min(Math.max(1, page), pageCount.value);
}

function goToPreviousPage() {
    goToPage(currentPage.value - 1);
}

function goToNextPage() {
    goToPage(currentPage.value + 1);
}

const paginationItems = computed<(number | '…')[]>(() => {
    const total = pageCount.value;
    const current = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const items: (number | '…')[] = [1];
    if (current > 3) items.push('…');

    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);
    for (let p = start; p <= end; p++) items.push(p);

    if (current < total - 2) items.push('…');
    items.push(total);

    return items;
});

function certifLabel(id: number | null) {
    return props.typeCertifications.find((c) => c.id === id)?.nom ?? '—';
}

function codeLabel(id: number | null) {
    if (!id) return null;
    return props.souragesCodes.find((c) => c.id === id)?.code ?? null;
}

function formatDateTime(value: string) {
    if (!value) return '—';
    const [date, time] = value.split('T');
    if (!date) return value;
    const [y, m, d] = date.split('-');
    return `${d}/${m}/${y}${time ? ' ' + time : ''}`;
}
</script>

<template>
    <Head title="Palettisation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 transition-colors bg-[var(--background)] text-[var(--text)] font-sans">

            <HeaderFiche
                title="Palettisation"
                v-model:agentName="agentName"
                v-model:ficheNumber="ficheNumber"
                :enqueteurs="props.enqueteurs"
            />

            <div class="flex flex-wrap justify-between items-center gap-4 py-4 mt-2">
                <div class="flex gap-4 text-xs font-bold">
                    <div v-for="cert in typeCertifications" :key="cert.id" class="flex items-center gap-2 bg-[var(--card-alt)] px-3 py-1.5 rounded-lg border border-[var(--sidebar-border)]">
                        <span class="bg-[var(--brand-green)] text-white px-1.5 py-0.5 rounded text-[10px]">{{ cert.nom[0] }}</span>
                        <span>{{ cert.nom }}</span>
                    </div>
                </div>

                <button
                    @click="openCreateModal"
                    class="h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all"
                >
                    <Plus class="w-4 h-4" /> Ajouter une palette
                </button>
            </div>

            <p v-if="listError" class="flex items-center gap-1 text-[12px] text-red-500 mb-3">
                <AlertCircle class="w-3.5 h-3.5" /> {{ listError }}
            </p>

            <h3 class="text-[11px] font-black uppercase tracking-widest text-[var(--text)]/50 mb-2">
                Liste des palettisations
            </h3>

            <!-- Liste des palettisations -->
            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow">
                <div v-if="rows.length === 0" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                    <PackageOpen class="w-10 h-10 text-[var(--sidebar-border)]" />
                    <p class="text-[13px] text-[var(--text)]/60">Aucune palette enregistrée pour le moment.</p>
                    <button @click="openCreateModal" class="text-[12px] font-bold text-[var(--brand-green)] underline underline-offset-2">
                        Ajouter la première palette
                    </button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[980px]">
                        <thead>
                            <tr class="bg-[var(--brand-green)] text-white text-[11px] font-black uppercase">
                                <th class="px-4 py-3 text-[12px]">N° Palette</th>
                                <th class="px-4 py-3 text-[12px] text-center">Poids</th>
                                <th class="px-4 py-3 text-[12px] text-center">Certif.</th>
                                <th class="px-4 py-3 text-[12px] text-center">Début</th>
                                <th class="px-4 py-3 text-[12px] text-center">Fin</th>
                                <th class="px-4 py-3 text-[12px]">Lots (Code traça - Nombre de caisse)</th>
                                <th class="px-4 py-3 text-[12px] text-center bg-black/10">Total</th>
                                <th class="px-4 py-3 text-[12px] text-center w-28">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--sidebar-border)]">
                            <tr v-for="row in paginatedRows" :key="row.id" class="hover:bg-[var(--brand-green)]/5 transition-colors align-top">
                                <td class="px-4 py-4 text-[12px] font-bold uppercase">{{ row.num_palette || '—' }}</td>
                                <td class="px-4 py-4 text-[12px] text-center">{{ row.type_carton }}kg</td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="text-[10px] font-bold bg-[var(--card-alt)] px-1.5 py-0.5 rounded border border-[var(--sidebar-border)]"
                                        :title="certifLabel(row.type_certification_id)"
                                    >
                                        {{ certifLabel(row.type_certification_id).charAt(0).toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-[12px] text-center whitespace-nowrap">{{ formatDateTime(row.debut) }}</td>
                                <td class="px-4 py-4 text-[12px] text-center whitespace-nowrap">{{ formatDateTime(row.fin) }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="lot in row.lots"
                                            :key="lot.lot_number"
                                            class="text-[10px] font-mono px-1.5 py-0.5 rounded border"
                                            :class="codeLabel(lot.code_traca_id) ? 'border-[var(--brand-green)]/30 bg-[var(--brand-green)]/5' : 'border-[var(--sidebar-border)] text-[var(--text)]/40'"
                                        >
                                            {{ codeLabel(lot.code_traca_id) ?? '—' }}<template v-if="lot.nb_cartons"> · {{ lot.nb_cartons }}</template>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center font-black text-[12px]">{{ rowTotal(row) }}</td>
                                <td class="px-4 py-4 text-center">
                                    <div v-if="confirmingDeleteId === row.id" class="flex items-center justify-center gap-2">
                                        <button @click="confirmDelete(row.id)" :disabled="deletingId === row.id" class="text-red-500 hover:text-red-700">
                                            <Loader2 v-if="deletingId === row.id" class="w-4 h-4 animate-spin" />
                                            <Check v-else class="w-4 h-4" />
                                        </button>
                                        <button @click="cancelDelete" class="text-[var(--text)]/50 hover:text-[var(--text)]">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-3">
                                        <button @click="openEditModal(row)" class="text-[var(--brand-green)] hover:opacity-70 transition-opacity">
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button @click="requestDelete(row.id)" class="text-red-400 hover:text-red-600 transition-colors">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-[var(--card-alt)] font-black text-[12px]">
                                <td colspan="6" class="px-4 py-3 text-right border-t border-[var(--sidebar-border)]">Total général</td>
                                <td class="px-4 py-3 border-t border-[var(--sidebar-border)] text-center">{{ grandTotal }}</td>
                                <td class="border-t border-[var(--sidebar-border)]"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="rows.length > 0"
                    class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-[var(--sidebar-border)] bg-[var(--card-alt)]"
                >
                    <div class="flex items-center gap-2 text-[11px] font-bold text-[var(--text)]/60">
                        <span>{{ paginationRangeLabel }}</span>
                        <span class="text-[var(--sidebar-border)]">|</span>
                        <label class="flex items-center gap-1.5">
                            <span>Lignes par page</span>
                            <select
                                v-model.number="pageSize"
                                class="bg-[var(--card)] border border-[var(--sidebar-border)] rounded-md px-1.5 py-1 text-[11px] font-bold outline-none focus:border-[var(--brand-green)]"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            @click="goToPreviousPage"
                            :disabled="currentPage === 1"
                            class="h-8 px-2.5 rounded-lg text-[11px] font-black uppercase border border-[var(--sidebar-border)] hover:bg-[var(--card)] transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Préc.
                        </button>

                        <template v-for="(item, idx) in paginationItems" :key="idx">
                            <span v-if="item === '…'" class="px-1.5 text-[11px] text-[var(--text)]/40">…</span>
                            <button
                                v-else
                                @click="goToPage(item)"
                                class="h-8 min-w-8 px-2 rounded-lg text-[11px] font-black transition-colors"
                                :class="item === currentPage
                                    ? 'bg-[var(--brand-green)] text-white'
                                    : 'border border-[var(--sidebar-border)] hover:bg-[var(--card)]'"
                            >
                                {{ item }}
                            </button>
                        </template>

                        <button
                            @click="goToNextPage"
                            :disabled="currentPage === pageCount"
                            class="h-8 px-2.5 rounded-lg text-[11px] font-black uppercase border border-[var(--sidebar-border)] hover:bg-[var(--card)] transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Suiv.
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ajout / Modification -->
        <Teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                @click="closeModal"
            >
                <div
                    ref="modalPanelRef"
                    tabindex="-1"
                    @click.stop
                    @keydown.esc="closeModal"
                    class="w-full max-w-2xl rounded-2xl bg-[var(--card)] text-[var(--text)] shadow-2xl border border-[var(--sidebar-border)] max-h-[90vh] overflow-y-auto"
                >
                    <div class="flex items-center justify-between px-6 py-4 border-b border-[var(--sidebar-border)]">
                        <h2 class="text-[14px] font-black uppercase tracking-wide">
                            {{ modalMode === 'create' ? 'Ajouter une palette' : 'Modifier la palette' }}
                        </h2>
                        <button @click="closeModal" class="text-[var(--text)]/50 hover:text-[var(--text)] transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-5">
                        <p v-if="formError" class="flex items-center gap-1.5 text-[12px] text-red-500 bg-red-500/10 rounded-lg px-3 py-2">
                            <AlertCircle class="w-3.5 h-3.5 shrink-0" /> {{ formError }}
                        </p>

                        <div class="space-y-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider text-[var(--brand-orange)]">
                                Enquêteur Responsable *
                            </label>
                            <select v-model="form.enqueteurId" class="input-line w-full">
                                <option :value="null">— choisir un enquêteur —</option>
                                <option v-for="e in props.enqueteurs" :key="e.id" :value="e.id">
                                    {{ e.prenom }} {{ e.nom }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wide mb-1.5 text-[var(--text)]/60">N° Palette</label>
                            <input
                                ref="numPaletteInputRef"
                                v-model="form.num_palette"
                                type="text"
                                placeholder="Ex: PAL-001"
                                class="w-full bg-[var(--card-alt)] border border-[var(--sidebar-border)] rounded-lg px-3 py-2 text-[13px] font-bold uppercase outline-none focus:border-[var(--brand-green)]"
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wide mb-1.5 text-[var(--text)]/60">Poids</label>
                                <div class="flex gap-4">
                                    <label v-for="p in ['2', '5.5']" :key="p" class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="modal_poids" :value="p" v-model="form.type_carton" class="radio-green">
                                        <span class="text-[12px] font-medium">{{ p }}kg</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-wide mb-1.5 text-[var(--text)]/60">Certification</label>
                                <div class="flex gap-4 flex-wrap">
                                    <label v-for="cert in typeCertifications" :key="cert.id" class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" name="modal_certif" :value="cert.id" v-model="form.type_certification_id" class="radio-green">
                                        <span class="text-[12px] font-bold">{{ cert.nom }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div @click="$event.currentTarget.querySelector('input').showPicker()">
                                <label class="block text-[10px] font-black uppercase tracking-wide mb-1.5 text-[var(--text)]/60">Début</label>
                                <input
                                    @input="(e) => (e.target as HTMLInputElement).blur()"
                                    type="datetime-local"
                                    v-model="form.debut"
                                    class="w-full bg-[var(--card-alt)] border border-[var(--sidebar-border)] rounded-lg px-3 py-2 text-[12px] font-bold outline-none focus:border-[var(--brand-green)] text-black dark:text-white"
                                >
                            </div>
                            <div @click="$event.currentTarget.querySelector('input').showPicker()">
                                <label class="block text-[10px] font-black uppercase tracking-wide mb-1.5 text-[var(--text)]/60">Fin</label>
                                <input
                                    @input="(e) => (e.target as HTMLInputElement).blur()"
                                    type="datetime-local"
                                    v-model="form.fin"
                                    class="w-full bg-[var(--card-alt)] border border-[var(--sidebar-border)] rounded-lg px-3 py-2 text-[12px] font-bold outline-none focus:border-[var(--brand-green)] text-black dark:text-white"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wide mb-2 text-[var(--text)]/60">Lots</label>
                            <div class="space-y-2">
                                <div
                                    v-for="(lot, idx) in form.lots"
                                    :key="lot.lot_number"
                                    class="grid grid-cols-[auto_1fr_120px] items-center gap-3 bg-[var(--card-alt)] rounded-lg px-3 py-2 border border-[var(--sidebar-border)]"
                                >
                                    <span class="text-[11px] font-black text-[var(--text)]/50 w-12">Lot {{ lot.lot_number }}</span>
                                    <select
                                        v-model.number="lot.code_traca_id"
                                        @change="onLotCodeChange(lot)"
                                        class="bg-transparent text-[12px] font-mono uppercase outline-none border-b border-[var(--sidebar-border)] focus:border-[var(--brand-green)] py-1"
                                    >
                                        <option :value="null">— Code Traça —</option>
                                        <option
                                            v-for="c in availableCodesForLot(idx)"
                                            :key="c.id"
                                            :value="c.id"
                                        >
                                            {{ c.code }}
                                        </option>
                                    </select>
                                    <input
                                        disabled
                                        v-model.number="lot.nb_cartons"
                                        type="number"
                                        placeholder="Nb. cartons"
                                        class="bg-transparent text-[12px] font-mono outline-none border-b border-[var(--sidebar-border)] focus:border-[var(--brand-green)] py-1 text-center"
                                    >
                                </div>
                            </div>
                            <p class="text-right text-[12px] font-black mt-2">Total : {{ formTotal }} cartons</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-[var(--sidebar-border)]">
                        <button
                            @click="closeModal"
                            :disabled="formSaving"
                            class="h-10 px-5 rounded-xl text-[12px] font-black uppercase tracking-widest border border-[var(--sidebar-border)] hover:bg-[var(--card-alt)] transition-colors disabled:opacity-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="submitForm"
                            :disabled="formSaving"
                            class="h-10 px-6 bg-[var(--brand-green)] text-white rounded-xl text-[12px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all disabled:opacity-60"
                        >
                            <Loader2 v-if="formSaving" class="w-4 h-4 animate-spin" />
                            {{ modalMode === 'create' ? 'Ajouter' : 'Enregistrer' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* Supprimer les flèches des inputs number */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>