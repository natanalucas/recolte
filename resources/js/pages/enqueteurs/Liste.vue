<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { 
  Plus, Search, Pencil, Trash2, X, 
  Sun, Moon, AlertTriangle, Loader2, Layers 
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/pages/components/Pagination.vue';
import type { BreadcrumbItem } from '@/types';

// --- TYPES ---
interface Societe {
    id: number;
    nom: string;
}

interface EnqueteurItem {
    id: number;
    user_id: number;
    nom: string;
    prenom: string;
    email: string;
    poste: string;
    travail: string;
    societe_id?: number | null;
    societe_nom?: string | null;
}

// --- PROPS & DATA ---
const isDeleting = ref(false);
const props = defineProps<{
    enqueteurs: EnqueteurItem[];
    isAdmin: boolean;
    societes: Societe[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuration', href: '#' },
    { title: 'Enquêteurs', href: '/enqueteurs' }
];

const postes = ['triage', 'soufrage', 'réception', 'expédition', 'palettisation'];

// --- FILTRAGE (Local) ---
const searchQuery = ref('');
const filterPoste = ref('');
const filterTravail = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(10);

const filtered = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    const pFilter = filterPoste.value;
    const tFilter = filterTravail.value;

    return props.enqueteurs.filter(item => {
        const matchSearch = !q || 
            item.nom.toLowerCase().includes(q) || 
            item.prenom.toLowerCase().includes(q) || 
            item.email.toLowerCase().includes(q);

        const matchPoste = !pFilter || item.poste === pFilter;
        const matchTravail = !tFilter || item.travail === tFilter;

        return matchSearch && matchPoste && matchTravail;
    });
});

const paginated = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filtered.value.slice(start, start + itemsPerPage.value);
});

watch([searchQuery, filterPoste, filterTravail, itemsPerPage], () => {
    currentPage.value = 1;
});

// --- MODAL ---
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingEnqueteur = ref<EnqueteurItem | null>(null);
const enqueteurToDelete = ref<EnqueteurItem | null>(null);

const form = useForm({
    nom: '',
    prenom: '',
    email: '',
    password: '',
    poste: 'triage',
    travail: 'jour',
    societe_id: null as number | null,
});

const openCreateModal = () => {
    editingEnqueteur.value = null;
    form.reset();
    form.societe_id = null;
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (item: EnqueteurItem) => {
    editingEnqueteur.value = item;
    form.clearErrors();
    form.nom = item.nom;
    form.prenom = item.prenom;
    form.email = item.email;
    form.password = ''; 
    form.poste = item.poste;
    form.travail = item.travail;
    form.societe_id = item.societe_id ?? null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submitForm = () => {
    if (editingEnqueteur.value) {
        form.put(route('enqueteurs.update', editingEnqueteur.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('enqueteurs.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (item: EnqueteurItem) => {
    enqueteurToDelete.value = item;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    enqueteurToDelete.value = null;
};

const executeDelete = () => {
    if (!enqueteurToDelete.value) return;
    isDeleting.value = true;
    
    form.delete(route('enqueteurs.destroy', enqueteurToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            enqueteurToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

const avatarColors = [
    'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
    'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
];

const getAvatarColor = (id: number) => avatarColors[id % avatarColors.length];
</script>

<template>
    <Head title="Liste des Enquêteurs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full max-w-none">
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-foreground uppercase tracking-tighter">Gestion des Enquêteurs</h1>
                    <p class="text-[12px] text-foreground tracking-[0.2em] font-bold">
                        {{ props.enqueteurs.length }} Agent(s) Enregistré(s)
                    </p>
                </div>
                <button 
                    @click="openCreateModal"
                    class="h-10 px-6 bg-[var(--brand-green)] text-white text-[12px] font-black tracking-widest rounded-xl flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all uppercase"
                >
                    <Plus class="w-4 h-4" /> Nouveau Enquêteur
                </button>
            </div>

            <div class="p-6 bg-card border border-border rounded-2xl shadow-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher nom, prénom, email..." 
                            class="w-full pl-9 pr-4 h-10 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground transition-colors uppercase"
                        />
                    </div>
                    <div>
                        <select 
                            v-model="filterPoste"
                            class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground uppercase"
                        >
                            <option value="">Tous les postes</option>
                            <option v-for="p in postes" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>
                    <div>
                        <select 
                            v-model="filterTravail"
                            class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground uppercase"
                        >
                            <option value="">Tous les shifts</option>
                            <option value="jour">Jour</option>
                            <option value="nuit">Nuit</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-border bg-[var(--brand-green)] text-white text-center">
                                <th class="p-4 text-[12px] font-black uppercase text-left">Agent & Contact</th>
                                <th class="p-4 text-[12px] font-black uppercase text-center">Poste Affecté</th>
                                <th class="p-4 text-[12px] font-black uppercase text-center">Horaires (Shift)</th>
                                <th v-if="props.isAdmin" class="p-4 text-[12px] font-black uppercase text-center">Société</th>
                                <th class="p-4 text-[12px] font-black uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr 
                                v-for="(item, idx) in paginated" 
                                :key="item.id"
                                :class="idx % 2 === 0 ? 'bg-white dark:bg-zinc-900' : 'bg-zinc-50 dark:bg-zinc-800/60'"
                                class="hover:bg-[var(--brand-green)]/5 transition-colors group align-middle"
                            >
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div 
                                            :class="getAvatarColor(item.id)"
                                            class="w-10 h-10 rounded-xl border flex items-center justify-center font-black text-xs shrink-0 uppercase"
                                        >
                                            {{ item.prenom[0] }}{{ item.nom[0] }}
                                        </div>
                                        <div>
                                            <p class="text-[13px] font-black text-foreground uppercase tracking-tight">
                                                {{ item.nom }} {{ item.prenom }}
                                            </p>
                                            <p class="text-[11px] text-muted-foreground font-medium">
                                                {{ item.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 text-[11px] font-black uppercase rounded-lg border bg-muted/30 border-border text-foreground">
                                        {{ item.poste }}
                                    </span>
                                </td>

                                <td class="p-4 text-center">
                                    <span 
                                        :class="item.travail === 'jour' ? 'bg-amber-500/10 text-amber-600 border-amber-500/20' : 'bg-indigo-500/10 text-indigo-600 border-indigo-500/20'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-black uppercase rounded-lg border"
                                    >
                                        <Sun v-if="item.travail === 'jour'" class="w-3.5 h-3.5" />
                                        <Moon v-else class="w-3.5 h-3.5" />
                                        {{ item.travail }}
                                    </span>
                                </td>

                                <td v-if="props.isAdmin" class="p-4 text-center">
                                    <span v-if="item.societe_nom" class="text-[10px] font-black text-blue-800 uppercase bg-blue-100 border border-blue-200 px-2 py-1 rounded inline-flex items-center gap-1">
                                        <Layers class="w-3 h-3" /> {{ item.societe_nom }}
                                    </span>
                                    <span v-else class="text-[10px] italic opacity-50 uppercase">—</span>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            @click="openEditModal(item)" 
                                            class="p-2 hover:bg-muted rounded-lg text-muted-foreground hover:text-[var(--brand-green)] transition-all"
                                        >
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button 
                                            @click="confirmDelete(item)" 
                                            class="p-2 hover:bg-red-500/10 rounded-lg text-muted-foreground hover:text-red-600 transition-all"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="paginated.length === 0">
                                <td :colspan="props.isAdmin ? 5 : 4" class="p-12 text-center text-[12px] font-black uppercase opacity-40 tracking-widest">
                                    Aucun enquêteur trouvé
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination 
                    :total="filtered.length" 
                    v-model:currentPage="currentPage" 
                    v-model:itemsPerPage="itemsPerPage" 
                />
            </div>

            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden" @click.stop>
                        
                        <div class="p-6 border-b border-border flex justify-between items-center bg-[var(--brand-green)] text-white">
                            <h2 class="text-[16px] font-black uppercase tracking-wider">
                                {{ editingEnqueteur ? 'Modifier l\'Enquêteur' : 'Nouvel Enquêteur' }}
                            </h2>
                            <button @click="closeModal" class="hover:opacity-70 transition-opacity">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="p-6 space-y-4">
                            
                            <div v-if="props.isAdmin" class="flex flex-col gap-1.5 p-4 bg-[var(--brand-green)]/10 rounded-xl border border-[var(--brand-green)]/30">
                                <label class="text-[12px] font-black uppercase text-[var(--brand-green)]">Société de Rattachement</label>
                                <select 
                                    v-model="form.societe_id" 
                                    class="w-full h-10 px-3 text-[12px] font-bold bg-white dark:bg-zinc-900 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground uppercase"
                                    :class="{ 'border-red-500': form.errors.societe_id }"
                                >
                                    <option :value="null" disabled>-- Sélectionner une société --</option>
                                    <option v-for="soc in props.societes" :key="soc.id" :value="soc.id">
                                        {{ soc.nom }}
                                    </option>
                                </select>
                                <p v-if="form.errors.societe_id" class="text-[11px] text-red-500 font-bold mt-1">{{ form.errors.societe_id }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase">Nom</label>
                                    <input 
                                        v-model="form.nom"
                                        type="text" 
                                        required
                                        class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground uppercase"
                                    />
                                    <p v-if="form.errors.nom" class="text-[10px] text-red-500 font-bold">{{ form.errors.nom }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase">Prénom</label>
                                    <input 
                                        v-model="form.prenom"
                                        type="text" 
                                        required
                                        class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground"
                                    />
                                    <p v-if="form.errors.prenom" class="text-[10px] text-red-500 font-bold">{{ form.errors.prenom }}</p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[11px] font-black uppercase">Email Identifiant</label>
                                <input 
                                    v-model="form.email"
                                    type="email" 
                                    required
                                    class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground"
                                />
                                <p v-if="form.errors.email" class="text-[10px] text-red-500 font-bold">{{ form.errors.email }}</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[11px] font-black uppercase">
                                    Mot de passe {{ editingEnqueteur ? '(laisser vide si inchangé)' : '' }}
                                </label>
                                <input 
                                    v-model="form.password"
                                    type="password" 
                                    :required="!editingEnqueteur"
                                    class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground"
                                />
                                <p v-if="form.errors.password" class="text-[10px] text-red-500 font-bold">{{ form.errors.password }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase">Poste</label>
                                    <select 
                                        v-model="form.poste"
                                        class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground uppercase"
                                    >
                                        <option v-for="p in postes" :key="p" :value="p">{{ p }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[11px] font-black uppercase">Horaires (Shift)</label>
                                    <select 
                                        v-model="form.travail"
                                        class="w-full h-10 px-3 text-[12px] font-bold bg-muted/20 border border-border rounded-xl focus:border-[var(--brand-green)] outline-none text-foreground uppercase"
                                    >
                                        <option value="jour">Jour</option>
                                        <option value="nuit">Nuit</option>
                                    </select>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-border flex gap-3">
                                <button 
                                    type="button" 
                                    @click="closeModal"
                                    class="flex-1 h-10 text-[12px] font-black border border-border rounded-xl hover:bg-muted text-foreground transition-all"
                                >
                                    Annuler
                                </button>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="flex-1 h-10 text-[12px] font-black bg-[var(--brand-orange)] text-black rounded-xl shadow-lg shadow-[var(--brand-orange)]/20 active:scale-95 transition-all flex items-center justify-center gap-2"
                                >
                                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                    <span>{{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}</span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div v-if="showDeleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50 p-4">
                    <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                        <div class="p-6 text-center space-y-4">
                            <div class="w-12 h-12 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mx-auto border border-red-500/20">
                                <AlertTriangle class="w-6 h-6" />
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-black uppercase tracking-wider text-sm">Suppression Définitive</h3>
                                <p class="text-[11px] text-muted-foreground leading-relaxed font-bold">
                                    Voulez-vous supprimer l'enquêteur <span class="text-foreground font-black">{{ enqueteurToDelete?.nom }}</span> ? Cette action est irréversible.
                                </p>
                            </div>
                        </div>
                        <div class="p-6 bg-muted/10 border-t border-border flex gap-3">
                            <button @click="cancelDelete" :disabled="isDeleting" class="flex-1 h-10 text-[12px] font-black border border-border rounded-lg hover:bg-muted text-foreground transition-all disabled:opacity-40 disabled:cursor-not-allowed">Annuler</button>
                            <button 
                                @click="executeDelete" 
                                :disabled="isDeleting"
                                class="flex-1 h-10 text-[12px] font-black bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all shadow-lg shadow-red-600/20 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <Loader2 v-if="isDeleting" class="w-4 h-4 animate-spin" />
                                <span>{{ isDeleting ? 'Suppression...' : 'Supprimer' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>