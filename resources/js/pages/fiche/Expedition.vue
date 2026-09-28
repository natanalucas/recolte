<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import HeaderFiche from './HeaderFiche.vue';
import { Pencil, Trash2 } from 'lucide-vue-next';

// Props
const props = defineProps<{
    expeditions: any[];
    availablePalettes: any[];
    certifications: any[];
    enqueteurs: { id: number; nom: string; prenom: string; poste: string }[];
    usedPaletteIds: number[];
    societes: { id: number; nom: string }[];
    isAdmin: boolean;
    isManager: boolean;
    currentEnqueteurId: number | null;
    currentEnqueteurLabel: string | null;
}>();

const isEnqueteur = computed(() => !props.isAdmin && !props.isManager);

const isModalOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<number | null>(null);

// ─── Pagination ──────────────────────────────────────────────
const pageSize = ref(10);
const currentPage = ref(1);

const pageCount = computed(() => Math.max(1, Math.ceil(props.expeditions.length / pageSize.value)));

watch([() => props.expeditions.length, pageSize], () => {
    if (currentPage.value > pageCount.value) currentPage.value = pageCount.value;
});

const paginatedExpeditions = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    return props.expeditions.slice(start, start + pageSize.value);
});

const paginationRangeLabel = computed(() => {
    if (props.expeditions.length === 0) return '0–0 sur 0';
    const start = (currentPage.value - 1) * pageSize.value + 1;
    const end = Math.min(props.expeditions.length, currentPage.value * pageSize.value);
    return `${start}–${end} sur ${props.expeditions.length}`;
});

function goToPage(page: number) {
    currentPage.value = Math.min(Math.max(1, page), pageCount.value);
}
function goToPreviousPage() { goToPage(currentPage.value - 1); }
function goToNextPage() { goToPage(currentPage.value + 1); }

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

// ─── Formulaire ──────────────────────────────────────────────
const form = useForm({
    enqueteur_id: null as number | null,
    societe_id: null as number | null,
    fiche_number: '',
    conteneur: '',
    immatriculation: '',
    proprete_conteneur: 'propre',
    proprete_camion: 'propre',
    debut_empotage: '',
    fin_empotage: '',
    depart_station: '',
    arrivee_port: '',
    bateau: '',
    bon_livraison: '',
    observations: '',
    palettes: [] as Array<{ id: number; paletisation_id: string | number; type: string; certifs: string }>
});

// ─── Filtrage des palettes disponibles ──────────────────────
const availablePalettesFiltered = computed(() => {
    const currentPaletteIds = new Set<number>();
    if (isEditing.value && currentId.value) {
        const currentExpedition = props.expeditions.find(e => e.id === currentId.value);
        if (currentExpedition) {
            currentExpedition.palettes?.forEach((p: any) => {
                if (p.paletisation_id) currentPaletteIds.add(p.paletisation_id);
            });
        }
    }

    return props.availablePalettes.filter(pal => {
        if (props.usedPaletteIds.includes(pal.id) && !currentPaletteIds.has(pal.id)) {
            return false;
        }
        return true;
    });
});

// ─── Filtrage des enquêteurs pour admin ─────────────────────
const filteredEnqueteurs = computed(() => {
    if (!props.isAdmin) return props.enqueteurs;
    if (!form.societe_id) return [];
    return props.enqueteurs.filter(e => e.societe_id === form.societe_id);
});

// ─── Gestion des palettes ──────────────────────────────────
const addPaletteRow = () => {
    if (form.palettes.length >= 20) {
        // Optionnel : notifier l'utilisateur
        console.warn('Limite de 20 palettes atteinte');
        // ou avec une alerte : alert('Vous ne pouvez pas ajouter plus de 20 palettes');
        return;
    }

    const nextId = form.palettes.length + 1;
    form.palettes.push({
        id: nextId,
        paletisation_id: '',
        type: '-',
        certifs: '-'
    });
};

const handlePaletteChange = (index: number) => {
    const selectedId = form.palettes[index].paletisation_id;
    const item = props.availablePalettes.find(p => p.id == selectedId);
    
    if (item) {
        form.palettes[index].type = item.type_carton ? item.type_carton + 'kg' : 'Inconnu';
        // Récupérer toutes les certifications uniques des lots de cette palette
        const certSet = new Set<string>();
        if (item.lots) {
            item.lots.forEach((lot: any) => {
                if (lot.certifications) {
                    lot.certifications.forEach((c: any) => certSet.add(c.nom));
                }
            });
        }
        form.palettes[index].certifs = certSet.size > 0 ? Array.from(certSet).join(', ') : 'Non certifiée';
    } else {
        form.palettes[index].type = '-';
        form.palettes[index].certifs = '-';
    }
};

// ─── Ouverture / Fermeture du modal ─────────────────────────
const openCreateModal = () => {
    isEditing.value = false;
    currentId.value = null;
    form.reset();
    form.palettes = Array.from({ length: 1 }, (_, i) => ({
        id: i + 1,
        paletisation_id: '',
        type: '-',
        certifs: '-'
    }));
    isModalOpen.value = true;
};

const openEditModal = (fiche: any) => {
    isEditing.value = true;
    currentId.value = fiche.id;
    
    form.enqueteur_id = fiche.enqueteur_id;
    form.fiche_number = fiche.fiche_number;
    form.conteneur = fiche.conteneur;
    form.immatriculation = fiche.immatriculation;
    form.proprete_conteneur = fiche.proprete_conteneur;
    form.proprete_camion = fiche.proprete_camion;
    form.debut_empotage = fiche.debut_empotage ? fiche.debut_empotage.slice(0,16) : '';
    form.fin_empotage = fiche.fin_empotage ? fiche.fin_empotage.slice(0,16) : '';
    form.depart_station = fiche.depart_station ? fiche.depart_station.slice(0,16) : '';
    form.arrivee_port = fiche.arrivee_port ? fiche.arrivee_port.slice(0,16) : '';
    form.bateau = fiche.bateau;
    form.bon_livraison = fiche.bon_livraison;
    form.observations = fiche.observations;
    
    form.palettes = fiche.palettes.map((p: any, idx: number) => ({
        id: idx + 1,
        paletisation_id: p.paletisation_id,
        type: p.paletisation?.type_carton ? p.paletisation.type_carton + 'kg' : '-',
        certifs: (() => {
            const certs = new Set<string>();
            if (p.paletisation?.lots) {
                p.paletisation.lots.forEach((lot: any) => {
                    if (lot.certifications) {
                        lot.certifications.forEach((c: any) => certs.add(c.nom));
                    }
                });
            }
            return certs.size > 0 ? Array.from(certs).join(', ') : 'Non certifiée';
        })()
    }));

    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submit = () => {
    const cleanPalettes = form.palettes.filter(p => p.paletisation_id !== '');
    const payload = { ...form.data(), palettes: cleanPalettes };

    if (isEditing.value && currentId.value) {
        router.put(route('expeditions.update', currentId.value), payload, {
            onSuccess: () => closeModal()
        });
    } else {
        router.post(route('expeditions.store'), payload, {
            onSuccess: () => { closeModal(); currentPage.value = 1; }
        });
    }
};

const confirmDelete = (fiche: any) => {
    if(confirm('Voulez-vous vraiment supprimer cette fiche d\'expédition ?')) {
        router.delete(route('expeditions.destroy', fiche.id));
    }
};
</script>

<template>
    <Head title="Suivi des Expéditions" />

    <AppLayout :breadcrumbs="[]">
        <div class="p-6 space-y-4 bg-[var(--background)] text-[var(--text)] min-h-screen">
            
            <div class="flex justify-between items-center">
                <div>
                    <HeaderFiche 
                        title="Expéditions" 
                    />
                </div>
                <button @click="openCreateModal" class="bg-[var(--brand-green)] text-white font-bold text-xs uppercase px-4 py-2.5 rounded-xl shadow hover:bg-[var(--brand-green)]/90 transition-all">
                    + Nouvelle Fiche
                </button>
            </div>

            <div class="flex gap-4 text-xs font-bold">
                <div v-for="cert in certifications" :key="cert.id" class="flex items-center gap-2 bg-[var(--card-alt)] px-3 py-1.5 rounded-lg border border-[var(--sidebar-border)]">
                    <span class="bg-[var(--brand-green)] text-white px-1.5 py-0.5 rounded text-[10px]">{{ cert.nom[0] }}</span>
                    <span>{{ cert.nom }}</span>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[var(--brand-green)] text-white text-[12px] font-black uppercase">
                            <th class="p-4">Immatriculation Camion</th>
                            <th class="p-4">Numéro Conteneur</th>
                            <th class="p-4">Palettes Chargées</th>
                            <th class="p-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--sidebar-border)] text-[12px]">
                        <tr v-for="fiche in paginatedExpeditions" :key="fiche.id" class="hover:bg-[var(--brand-green)]/5 transition-colors">
                            <td class="p-4 font-medium">{{ fiche.immatriculation || '-' }}</td>
                            <td class="p-4 font-medium">{{ fiche.conteneur || '-' }}</td>
                            <td class="p-4">
                                <span class="bg-[var(--brand-green)] px-2 py-1 rounded text-[12px] font-bold">
                                    {{ fiche.palettes?.length || 0 }} palette(s)
                                </span>
                            </td>
                            <td class="p-4 text-center">
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
                        <tr v-if="expeditions.length === 0">
                            <td colspan="5" class="p-8 text-center text-sm text-slate-400 italic">Aucune fiche enregistrée.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="expeditions.length > 0"
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

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-[var(--background)] border border-[var(--sidebar-border)] rounded-2xl w-full max-w-6xl max-h-[90vh] overflow-y-auto shadow-2xl space-y-6 p-6">
                    
                    <div class="flex justify-between items-center border-b border-[var(--sidebar-border)] pb-3">
                        <h2 class="text-lg font-black uppercase text-[var(--brand-green)]">
                            {{ isEditing ? 'Modifier la Fiche' : 'Nouvelle Fiche de Traçabilité' }}
                        </h2>
                        <button @click="closeModal" class="text-slate-400 hover:text-[var(--brand-green)] text-xl font-bold">&times;</button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Société (admin uniquement) -->
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

                        <div class="flex flex-col lg:flex-row gap-6 items-start">
                            
                            <!-- Colonne gauche : Palettes -->
                            <div class="w-full lg:w-[45%] space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-[12px] font-bold uppercase tracking-wider">Détail des Palettes</span>
                                    <button type="button" @click="addPaletteRow" class="text-[12px] bg-[var(--brand-green)] text-white font-bold px-3 py-1.5 rounded-lg hover:bg-opacity-90 transition-colors shadow">
                                        + Ajouter une ligne
                                    </button>
                                </div>

                                <!-- Enquêteur -->
                                <div class="px-1 py-2">
                                    <div v-if="isAdmin || isManager" class="space-y-1.5">
                                        <label class="text-[12px] font-black uppercase tracking-wider text-[var(--brand-orange)]">
                                            Enquêteur Responsable *
                                        </label>
                                        <select v-model="form.enqueteur_id" class="input-line w-full">
                                            <option :value="null">— choisir un enquêteur —</option>
                                            <option v-for="e in filteredEnqueteurs" :key="e.id" :value="e.id">
                                                {{ e.prenom }} {{ e.nom }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-else class="space-y-1.5">
                                        <label class="text-[12px] font-black uppercase tracking-wider text-[var(--brand-orange)]">
                                            Enquêteur Responsable
                                        </label>
                                        <input type="text" class="input-line w-full" disabled :value="props.currentEnqueteurLabel || '—'" />
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm max-h-[500px] overflow-y-auto">
                                    <table class="w-full text-left border-collapse table-fixed">
                                        <thead class="sticky top-0 bg-[var(--brand-green)] text-white text-[12px] font-black uppercase z-10">
                                            <tr>
                                                <th class="px-2 py-3 w-12 text-center">N°</th>
                                                <th class="px-3 py-3 text-center w-35">N° Palette</th>
                                                <th class="px-2 py-3 text-center w-24">Type</th>
                                                <th class="px-2 py-3 text-center w-21">Certification(s)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[var(--sidebar-border)]">
                                            <tr v-for="(p, index) in form.palettes" :key="p.id" class="hover:bg-[var(--brand-green)]/5">
                                                <td class="px-2 py-2 text-center text-[12px] font-bold bg-[var(--card-alt)] italic">
                                                    {{ p.id }}
                                                </td>
                                                <td class="px-2 py-1 border-r border-[var(--sidebar-border)]">
                                                    <select 
                                                        v-model="p.paletisation_id" 
                                                        @change="handlePaletteChange(index)"
                                                        class="w-full bg-transparent outline-none text-[12px] font-mono font-bold text-center cursor-pointer focus:text-[var(--brand-green)]"
                                                    >
                                                        <option value="">-- Sélectionner --</option>
                                                        <option v-for="ap in availablePalettesFiltered" :key="ap.id" :value="ap.id">
                                                            {{ ap.num_palette }}
                                                        </option>
                                                    </select>
                                                </td>
                                                <td class="px-2 py-2 border-r border-[var(--sidebar-border)] text-center text-[12px] font-medium">
                                                    {{ p.type }}
                                                </td>
                                                <td class="px-2 py-2 text-center text-[11px] font-bold text-[var(--brand-green)] uppercase">
                                                    {{ p.certifs }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Colonne droite : Infos générales -->
                            <div class="w-full lg:w-[55%] space-y-4">
                                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-[var(--brand-green)] text-white text-[12px] font-black uppercase">
                                                <th class="px-4 py-2.5 border-r border-white/10 w-1/2">Numéro Conteneur</th>
                                                <th class="px-4 py-2.5 w-1/2">N° Immatriculation Camion</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="p-2 border-r border-[var(--sidebar-border)]">
                                                    <input v-model="form.conteneur" type="text" class="w-full bg-transparent outline-none text-xs font-bold uppercase placeholder:opacity-30" placeholder="....">
                                                </td>
                                                <td class="p-2">
                                                    <input v-model="form.immatriculation" type="text" class="w-full bg-transparent outline-none text-xs font-bold uppercase placeholder:opacity-30" placeholder="....">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                                    <table class="w-full text-left border-collapse">
                                        <tbody class="divide-y divide-[var(--sidebar-border)] text-[12px]">
                                            <tr v-for="item in [{l:'du Conteneur', k:'proprete_conteneur'}, {l:'du Camion', k:'proprete_camion'}]" :key="item.k">
                                                <td class="px-4 py-3 font-bold uppercase w-1/3 border-r border-[var(--sidebar-border)] bg-[var(--card-alt)]">Propreté {{ item.l }}</td>
                                                <td class="px-4 py-3">
                                                    <div class="flex gap-6">
                                                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold">
                                                            <input type="radio" v-model="form[item.k as 'proprete_conteneur' | 'proprete_camion']" value="propre" class="accent-[var(--brand-green)]"> PROPRE
                                                        </label>
                                                        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-red-500">
                                                            <input type="radio" v-model="form[item.k as 'proprete_conteneur' | 'proprete_camion']" value="sale" class="accent-red-500"> SALE
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                                    <table class="w-full border-collapse table-fixed">
                                        <tbody>
                                            <tr>
                                                <td class="w-1/3 bg-[var(--brand-green)] text-white text-center font-black uppercase text-[12px] leading-tight">Chargement / Empotage</td>
                                                <td class="w-2/3 p-0 text-white">
                                                    <div class="flex">
                                                        <div class="flex-1 border-r border-[var(--sidebar-border)]" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                            <div class="bg-[var(--brand-green)] py-1.5 text-center text-[10px] font-black uppercase border-b border-white/10">Début</div>
                                                            <input @input="(e) => (e.target as HTMLInputElement).blur()" v-model="form.debut_empotage" type="datetime-local" class="w-full bg-[var(--card)] text-black dark:text-white p-2 text-[12px] font-bold text-center outline-none">
                                                        </div>
                                                        <div class="flex-1" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                            <div class="bg-[var(--brand-green)] py-1.5 text-center text-[10px] font-black uppercase border-b border-white/10">Fin</div>
                                                            <input @input="(e) => (e.target as HTMLInputElement).blur()" v-model="form.fin_empotage" type="datetime-local" class="w-full bg-[var(--card)] text-black dark:text-white p-2 text-[12px] font-bold text-center outline-none">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                                    <table class="w-full border-collapse table-fixed">
                                        <tbody>
                                            <tr>
                                                <td class="w-1/3 bg-[var(--brand-green)] text-white text-center font-black uppercase text-[10px] leading-tight">Départ Station & Arrivée Port</td>
                                                <td class="w-2/3 p-0 text-white">
                                                    <div class="flex">
                                                        <div class="flex-1 border-r border-[var(--sidebar-border)]" @click="$event.currentTarget.querySelector('input').showPicker()" >
                                                            <div class="bg-[var(--brand-green)] py-1.5 text-center text-[10px] font-black uppercase border-b border-white/10">Départ Station</div>
                                                            <input @input="(e) => (e.target as HTMLInputElement).blur()" v-model="form.depart_station" type="datetime-local" class="w-full bg-[var(--card)] text-black dark:text-white p-2 text-[12px] font-bold text-center outline-none">
                                                        </div>
                                                        <div class="flex-1" @click="$event.currentTarget.querySelector('input').showPicker()">
                                                            <div class="bg-[var(--brand-green)] py-1.5 text-center text-[10px] font-black uppercase border-b border-white/10">Arrivée Port</div>
                                                            <input @input="(e) => (e.target as HTMLInputElement).blur()" v-model="form.arrivee_port" type="datetime-local" class="w-full bg-[var(--card)] text-black dark:text-white p-2 text-[12px] font-bold text-center outline-none">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-[var(--brand-green)] text-white text-[12px] font-black uppercase">
                                                <th class="px-4 py-2 border-r border-white/10 w-1/2">Nom du bateau</th>
                                                <th class="px-4 py-2 w-1/2">Bon de livraison (B.L)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="p-2 border-r border-[var(--sidebar-border)]">
                                                    <input v-model="form.bateau" type="text" class="w-full bg-transparent outline-none text-xs font-bold uppercase placeholder:opacity-30" placeholder="....">
                                                </td>
                                                <td class="p-2">
                                                    <input v-model="form.bon_livraison" type="text" class="w-full bg-transparent outline-none text-xs font-bold uppercase placeholder:opacity-30" placeholder="....">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-[var(--sidebar-border)] bg-[var(--card)] shadow-sm">
                                    <div class="bg-[var(--brand-green)] px-4 py-1.5 text-white text-[12px] font-black uppercase">Observations</div>
                                    <textarea v-model="form.observations" rows="3" placeholder="Notes complémentaires..." class="w-full p-3 bg-transparent outline-none text-[12px] resize-none focus:ring-1 focus:ring-[var(--brand-green)]/20 transition-all"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-[var(--sidebar-border)] pt-4">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-xs font-bold uppercase text-slate-400 hover:text-slate-600">Annuler</button>
                            <button type="submit" :disabled="form.processing" class="bg-[var(--brand-green)] text-white text-xs font-bold uppercase px-5 py-2 rounded-xl shadow hover:bg-opacity-90 transition-all">
                                {{ isEditing ? 'Enregistrer' : 'Sauvegarder la fiche' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.max-h-\[500px\]::-webkit-scrollbar {
  width: 5px;
}
.max-h-\[500px\]::-webkit-scrollbar-thumb {
  background: var(--sidebar-border);
  border-radius: 10px;
}
</style>