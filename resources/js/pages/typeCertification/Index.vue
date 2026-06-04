<!-- resources/js/Pages/TypeCertification/Index.vue -->
<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Award, Plus, Pencil, Trash2, X, AlertTriangle, CheckCircle2 } from 'lucide-vue-next';

interface TypeCertification {
    id: number;
    nom: string;
}

const props = defineProps<{ certifications: TypeCertification[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Types de Certification', href: '#' }];

// ── État UI ───────────────────────────────────────────
const showModal       = ref(false);
const showDeleteModal = ref(false);
const editId          = ref<number | null>(null);
const itemToDelete    = ref<TypeCertification | null>(null);
const deletingId      = ref<number | null>(null);
const searchQuery     = ref('');

// ── Formulaire ────────────────────────────────────────
const form = useForm({ nom: '' });

// ── Filtrage ──────────────────────────────────────────
const filtered = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return props.certifications;
    return props.certifications.filter(c =>
        c.nom.toLowerCase().includes(q)
    );
});

// ── Initiales avatar ──────────────────────────────────
const avatarColors = [
    'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
    'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300',
];

function avatarColor(id: number) {
    return avatarColors[id % avatarColors.length];
}

function initials(nom: string) {
    return nom.slice(0, 2).toUpperCase();
}

// ── Modal Ajout / Édition ─────────────────────────────
function openAddModal() {
    editId.value = null;
    form.nom = '';
    form.clearErrors();
    showModal.value = true;
}

function openEditModal(cert: TypeCertification) {
    editId.value = cert.id;
    form.nom = cert.nom;
    form.clearErrors();
    showModal.value = true;
}

function save() {
    if (editId.value) {
        form.put(route('type-certifications.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('type-certifications.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

// ── Suppression ───────────────────────────────────────
function confirmDelete(cert: TypeCertification) {
    itemToDelete.value  = cert;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!itemToDelete.value) return;
    deletingId.value = itemToDelete.value.id;
    useForm({}).delete(route('type-certifications.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            itemToDelete.value    = null;
        },
        onFinish: () => { deletingId.value = null; },
    });
}
</script>

<template>
    <Head title="Types de Certification" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full max-w-2xl mx-auto">

            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-foreground uppercase tracking-tighter flex items-center gap-2">
                        <Award class="w-5 h-5 text-[var(--brand-green)]" />
                        Types de Certification
                    </h1>
                    <p class="text-[12px] text-muted-foreground font-bold tracking-widest mt-0.5">
                        {{ props.certifications.length }} type(s) enregistré(s)
                    </p>
                </div>
                <button @click="openAddModal"
                    class="h-10 px-5 bg-[var(--brand-green)] text-white text-[12px] font-black tracking-widest
                           rounded-xl flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20
                           active:scale-95 transition-all uppercase">
                    <Plus class="w-4 h-4" /> Nouveau Type
                </button>
            </div>

            <!-- Recherche -->
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                </svg>
                <input v-model="searchQuery" type="text" placeholder="Rechercher un type..."
                    class="w-full pl-10 pr-4 h-10 rounded-xl border border-border bg-card text-[13px] font-medium
                           text-foreground placeholder:text-muted-foreground focus:outline-none
                           focus:ring-2 focus:ring-[var(--brand-green)]/30 focus:border-[var(--brand-green)] transition-all" />
            </div>

            <!-- Liste -->
            <div class="bg-card border border-border rounded-2xl overflow-hidden shadow-sm shadow-black/5">

                <!-- Header -->
                <div class="flex items-center gap-4 px-5 py-3 bg-muted/50 border-b border-border">
                    <span class="flex-1 text-[11px] font-black uppercase tracking-widest text-muted-foreground">
                        Nom du type
                    </span>
                    <span class="text-[11px] font-black uppercase tracking-widest text-muted-foreground w-20 text-right">
                        Actions
                    </span>
                </div>

                <!-- Rows -->
                <div v-if="filtered.length" class="divide-y divide-border">
                    <div v-for="(cert, idx) in filtered" :key="cert.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-[var(--brand-green)]/5 transition-colors group">

                        <!-- Avatar -->
                        <div :class="avatarColor(cert.id)"
                            class="w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-black shrink-0">
                            {{ initials(cert.nom) }}
                        </div>

                        <!-- Nom -->
                        <div class="flex-1 min-w-0">
                            <p class="text-[14px] font-black uppercase text-foreground tracking-tight truncate">
                                {{ cert.nom }}
                            </p>
                            <p class="text-[11px] text-muted-foreground font-bold">
                                Certification #{{ idx + 1 }}
                            </p>
                        </div>

                        <!-- Badge unicité -->
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider
                                     bg-[var(--brand-green)]/10 text-[var(--brand-green)] border border-[var(--brand-green)]/20">
                            <CheckCircle2 class="w-2.5 h-2.5 inline mr-0.5" />Unique
                        </span>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity w-20 justify-end">
                            <button @click="openEditModal(cert)"
                                class="p-2 rounded-lg hover:bg-muted hover:text-[var(--brand-green)] transition-all"
                                title="Modifier">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="confirmDelete(cert)" :disabled="deletingId === cert.id"
                                class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-500
                                       transition-all disabled:opacity-40"
                                title="Supprimer">
                                <svg v-if="deletingId === cert.id" class="animate-spin w-4 h-4"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                                </svg>
                                <Trash2 v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vide -->
                <div v-else class="py-16 text-center">
                    <Award class="w-10 h-10 mx-auto mb-3 text-muted-foreground opacity-30" />
                    <p class="text-[12px] font-black uppercase tracking-widest opacity-30">
                        {{ searchQuery ? 'Aucun résultat' : 'Aucun type enregistré' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Modal Ajout / Édition ── -->
        <Teleport to="body">
            <div v-if="showModal"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="showModal = false">
                <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">

                    <!-- Header -->
                    <div class="px-6 py-5 bg-[var(--brand-green)] flex items-center justify-between">
                        <h2 class="text-[15px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                            <Award class="w-4 h-4" />
                            {{ editId ? 'Modifier le type' : 'Nouveau type' }}
                        </h2>
                        <button @click="showModal = false" class="text-white/70 hover:text-white transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[12px] font-black uppercase tracking-wider text-foreground">
                                Nom du type
                            </label>
                            <input v-model="form.nom" type="text"
                                placeholder="Ex: Bio, GlobalGAP, Rainforest..."
                                class="h-10 px-3 rounded-xl border bg-background text-[13px] font-bold
                                       text-foreground placeholder:font-normal
                                       focus:outline-none focus:ring-2 focus:ring-[var(--brand-green)]/30
                                       focus:border-[var(--brand-green)] transition-all"
                                :class="form.errors.nom ? 'border-red-500' : 'border-border'"
                                @keyup.enter="save"
                                autofocus />
                            <p v-if="form.errors.nom"
                                class="text-[11px] text-red-500 font-bold flex items-center gap-1">
                                ⚠️ {{ form.errors.nom }}
                            </p>
                            <p class="text-[11px] text-muted-foreground font-bold mt-1">
                                Ce champ doit être unique dans le système.
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="showModal = false"
                            class="flex-1 h-11 text-[13px] font-black uppercase border border-border rounded-xl
                                   hover:bg-muted text-foreground tracking-widest transition-all">
                            Annuler
                        </button>
                        <button @click="save" :disabled="form.processing || !form.nom.trim()"
                            class="flex-1 h-11 text-[13px] font-black uppercase bg-[var(--brand-orange)] text-black
                                   rounded-xl shadow-lg shadow-[var(--brand-orange)]/20 tracking-widest transition-all
                                   active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed
                                   flex items-center justify-center gap-2">
                            <svg v-if="form.processing" class="animate-spin w-4 h-4"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Modal Suppression ── -->
        <Teleport to="body">
            <div v-if="showDeleteModal"
                class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-[60] p-4"
                @click.self="showDeleteModal = false">
                <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                    <div class="p-8 text-center space-y-4">
                        <div class="w-14 h-14 bg-red-100 dark:bg-red-950/40 text-red-500 rounded-full
                                    flex items-center justify-center mx-auto border border-red-200 dark:border-red-800">
                            <AlertTriangle class="w-7 h-7" />
                        </div>
                        <div>
                            <h2 class="font-black uppercase tracking-widest text-foreground">
                                Supprimer ce type ?
                            </h2>
                            <p class="text-[12px] text-muted-foreground font-bold mt-1.5">
                                <span class="font-black text-foreground uppercase">{{ itemToDelete?.nom }}</span>
                                sera supprimé définitivement.
                            </p>
                        </div>
                    </div>
                    <div class="px-6 pb-6 flex gap-3">
                        <button @click="showDeleteModal = false"
                            class="flex-1 h-11 text-[12px] font-black uppercase border border-border rounded-xl
                                   hover:bg-muted transition-all">
                            Annuler
                        </button>
                        <button @click="executeDelete" :disabled="deletingId !== null"
                            class="flex-1 h-11 text-[12px] font-black uppercase bg-red-600 text-white rounded-xl
                                   shadow-lg shadow-red-600/20 disabled:opacity-60 active:scale-95
                                   flex items-center justify-center gap-2 transition-all">
                            <svg v-if="deletingId !== null" class="animate-spin w-4 h-4"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                            {{ deletingId !== null ? 'Suppression...' : 'Supprimer' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>