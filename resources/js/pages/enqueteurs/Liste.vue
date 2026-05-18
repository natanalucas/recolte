<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
  Plus, Search, Pencil, Trash2, X, 
  Sun, Moon, AlertTriangle, Loader2 
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/pages/components/Pagination.vue';
import type { BreadcrumbItem } from '@/types';

// --- PROPS & DATA ---
const isDeleting = ref(false);
const props = defineProps<{
    enqueteurs: Array<{
        id: number;
        user_id: number;
        nom: string;
        prenom: string;
        email: string;
        poste: string;
        travail: string;
    }>;
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
    const pFilter = filterPoste.value; // La valeur du select

    console.log(pFilter);

    console.log(props.enqueteurs);

    return props.enqueteurs.filter(e => {
        // Recherche textuelle
        const matchText = !q || 
            e.nom.toLowerCase().includes(q) || 
            e.prenom.toLowerCase().includes(q) ||
            e.email.toLowerCase().includes(q);

        // Filtrage par Poste
        const matchPoste = !pFilter || (
            e.poste && 
            e.poste.toLowerCase() === pFilter.toLowerCase()
        );

        // Filtrage par Travail
        const tFilter = filterTravail.value;
        const matchTravail = !tFilter || (
            e.travail && 
            e.travail.toLowerCase() === tFilter.toLowerCase()
        );

        return matchText && matchPoste && matchTravail;
    });
});

const paginated = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filtered.value.slice(start, start + itemsPerPage.value);
});

watch([searchQuery, filterPoste, filterTravail], () => { 
    currentPage.value = 1; 
});

// --- FORMULAIRE (Inertia useForm) ---
const showModal = ref(false);
const editId = ref<number | null>(null);

const form = useForm({
    nom: '',
    prenom: '',
    poste: '',
    travail: 'jour',
    email: '',
    password: ''
});

// --- MODAL ACTIONS ---
function openAddModal() {
    editId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEditModal(enqueteur: any) {
    editId.value = enqueteur.id;
    form.clearErrors();
    form.nom = enqueteur.nom;
    form.prenom = enqueteur.prenom;
    form.travail = enqueteur.travail;
    form.email = enqueteur.email;
    form.password = '';

    // Trouver le poste correspondant dans la liste (insensible à la casse)
    form.poste = postes.find(
        p => p.toLowerCase() === enqueteur.poste?.toLowerCase()
    ) ?? enqueteur.poste;

    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
}

// --- PERSISTENCE (CRUD) ---
function saveEnqueteur() {
    if (editId.value) {
        // UPDATE : On envoie vers la route update
        // Laravel recevra les données et devra mettre à jour User ET Interviewer
        form.put(route('enqueteurs.update', editId.value), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        // STORE : On crée l'utilisateur + l'interviewer
        form.post(route('enqueteurs.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

// --- SUPPRESSION ---
const showDeleteModal = ref(false);
const enqueteurToDelete = ref<any>(null);

function confirmDelete(enqueteur: any) {
    enqueteurToDelete.value = enqueteur;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!enqueteurToDelete.value) return;
    isDeleting.value = true;
    
    router.delete(route('enqueteurs.destroy', enqueteurToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            enqueteurToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
}

// --- UI HELPERS ---
function getPosteBadge(poste: string) {
    const common = 'text-[11px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md border inline-block transition-all';
    
    // On normalise en minuscule pour éviter les problèmes de casse
    const p = poste ? poste.toLowerCase() : '';

    const styles: Record<string, string> = {
        'triage': 'bg-blue-500/10 text-blue-600 border-blue-500/20',
        'soufrage': 'bg-yellow-500/10 text-yellow-700 border-yellow-500/20',
        'réception': 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
        'reception': 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20', // Sécurité sans accent
        'expédition': 'bg-purple-500/10 text-purple-600 border-purple-500/20',
        'expedition': 'bg-purple-500/10 text-purple-600 border-purple-500/20', // Sécurité sans accent
        'palettisation': 'bg-pink-500/10 text-pink-600 border-pink-500/20',
    };

    // On retourne le style correspondant ou un style gris par défaut si le poste est inconnu
    const colorStyle = styles[p] || 'bg-gray-500/10 text-gray-500 border-gray-500/20';

    return `${common} ${colorStyle}`;
}
</script>
 
<template>
    <Head title="Enquêteurs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-foreground uppercase tracking-tighter">Gestion des Enquêteurs</h1>
                    <p class="text-[11px] text-foreground tracking-widest font-medium">{{ enqueteurs.length }} profil(s) actif(s)</p>
                </div>
                <button
                    @click="openAddModal"
                    class="h-9 px-6 bg-[var(--brand-green)] hover:opacity-90 text-white text-[11px] font-black uppercase tracking-widest rounded-lg transition-all active:scale-95 shadow-lg shadow-[var(--brand-green)]/20 flex items-center gap-2"
                >
                    <Plus class="w-4 h-4" /> Ajouter
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6 bg-card border border-border rounded-xl shadow-sm">
                <div class="relative">
                    <Search class="absolute left-0 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="RECHERCHER..."
                        class="w-full pl-7 pr-4 h-10 text-[11px] font-bold bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none transition-colors text-foreground uppercase"
                    />
                </div>
                <select v-model="filterPoste" class="h-10 text-[11px] font-bold bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none transition-colors text-foreground cursor-pointer uppercase">
                    <option value="" class="bg-card">Tous les postes</option>
                    <option v-for="p in postes" :key="p" :value="p" class="bg-card">{{ p }}</option>
                </select>
                <select v-model="filterTravail" class="h-10 text-[11px] font-bold bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none transition-colors text-foreground cursor-pointer uppercase">
                    <option value="" class="bg-card">Jour & Nuit</option>
                    <option value="jour" class="bg-card">Jour</option>
                    <option value="nuit" class="bg-card">Nuit</option>
                </select>
            </div>

            <div class="bg-card border border-border rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[var(--brand-green)] border-b border-border">
                                <th class="p-4 text-[12px] font-black uppercase">Enquêteur</th>
                                <th class="p-4 text-[12px] font-black uppercase">Poste</th>
                                <th class="p-4 text-[12px] font-black uppercase">Période</th>
                                <th class="p-4 text-[12px] font-black uppercase text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-if="paginated.length === 0">
                                <td colspan="4" class="p-12 text-center text-muted-foreground italic">Aucun enquêteur trouvé</td>
                            </tr>
                            <tr v-for="enqueteur in paginated" :key="enqueteur.id" class="hover:bg-muted/10 transition-colors group">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <p class="font-black text-foreground uppercase tracking-tight">{{ enqueteur.nom }}</p>
                                            <p class="text-[11px] text-foreground font-bold">{{ enqueteur.prenom }} - ({{ enqueteur.email }})</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span :class="getPosteBadge(enqueteur.poste)">{{ enqueteur.poste }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2 text-[12px] font-bold uppercase">
                                        <Sun v-if="enqueteur.travail === 'jour'" class="w-4 h-4 text-[var(--brand-orange)]" />
                                        <Moon v-else class="w-4 h-4 text-[var(--brand-green)]" />
                                        <span :class="enqueteur.travail === 'jour' ? 'text-[var(--brand-orange)]' : 'text-[var(--brand-green)]'">{{ enqueteur.travail }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(enqueteur)" class="p-2.5 hover:text-[var(--brand-green)] transition-colors group-hover:opacity-100"><Pencil class="w-4 h-4" /></button>
                                        <button @click="confirmDelete(enqueteur)" class="p-2.5 hover:text-red-500 transition-colors group-hover:opacity-100"><Trash2 class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination réutilisable -->
                <Pagination
                    :total="filtered.length"
                    v-model:currentPage="currentPage"
                    v-model:itemsPerPage="itemsPerPage"
                />
            </div>

            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click="handleOverlayClick">
                    <div class="bg-card border border-border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>
                        <div class="p-6 border-b border-border flex justify-between items-center bg-[var(--brand-green)]">
                            <h2 class="text-[16px] font-black uppercase tracking-widest text-foreground">{{ editId ? 'Modifier' : 'Ajouter un Enquêteur' }}</h2>
                            <button @click="closeModal" class="hover:text-foreground"><X class="w-5 h-5" /></button>
                        </div>
                        <div class="p-8 space-y-8">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[12px] font-bold ml-1">Nom</label>
                                    <input v-model="form.nom" type="text" :class="{'border-red-500': form.errors.nom}" class="input-line" />
                                    <p v-if="form.errors.nom" class="text-[12px] text-red-500 font-bold ml-1 mt-1">{{ form.errors.nom }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[12px] font-bold ml-1">Prénoms</label>
                                    <input v-model="form.prenom" type="text" class="input-line" />
                                    <p v-if="form.errors.prenom" class="text-[12px] text-red-500 font-bold ml-1 mt-1">{{ form.errors.prenom }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[12px] font-bold ml-1">Email</label>
                                    <input v-model="form.email" type="email" autocomplete="off" :class="{'border-red-500': form.errors.email}" class="input-line" />
                                    <!-- Affichage de l'erreur email -->
                                    <p v-if="form.errors.email" class="text-[12px] text-red-500 font-bold ml-1 mt-1">{{ form.errors.email }}</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[12px] font-bold ml-1">Mot de passe</label>
                                    <input v-model="form.password" type="password" autocomplete="new-password" :class="{'border-red-500': form.errors.password}" class="input-line" />
                                    <!-- Affichage de l'erreur password -->
                                    <p v-if="form.errors.password" class="text-[12px] text-red-500 font-bold ml-1 mt-1">{{ form.errors.password }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[12px] font-bold ml-1">Poste</label>
                                <select v-model="form.poste" class="input-line">
                                    <option v-for="p in postes" :key="p" :value="p" class="bg-card">{{ p }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[12px] font-bold ml-1">Période</label>
                                <div class="flex bg-muted/50 p-1 rounded-lg border border-border">
                                    <button v-for="opt in ['jour', 'nuit']" :key="opt" @click="form.travail = opt" :class="form.travail === opt ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'" class="flex-1 py-2 text-[11px] font-black rounded-md transition-all uppercase">{{ opt }}</button>
                                </div>
                            </div>
                            <p v-if="formError" class="text-[11px] text-red-500 font-bold">{{ formError }}</p>
                        </div>
                        <div class="p-6 bg-muted/10 border-t border-border flex gap-4">
                            <button @click="closeModal" :disabled="form.processing" class="flex-1 h-11 text-[11px] font-bold border border-border rounded-lg hover:bg-muted text-foreground transition-all disabled:opacity-40 disabled:cursor-not-allowed">Annuler</button>
                            <button 
                                @click="saveEnqueteur" 
                                :disabled="form.processing" 
                                class="flex-1 h-11 text-[11px] font-bold bg-[var(--brand-green)] hover:opacity-90 text-white rounded-lg shadow-lg shadow-[var(--brand-green)]/20 transition-all tracking-widest disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                <span>{{ form.processing ? 'Enregistrement...' : (editId ? 'Mettre à jour' : 'Confirmer') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div v-if="showDeleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-[60] p-4">
                    <div class="bg-card border border-border rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden" @click.stop>
                        <div class="p-8 text-center space-y-4">
                            <div class="w-16 h-16 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mx-auto border border-red-500/20">
                                <AlertTriangle class="w-8 h-8" />
                            </div>
                            <div class="space-y-2">
                                <h2 class="text-xs font-black tracking-widest text-foreground">Confirmation</h2>
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