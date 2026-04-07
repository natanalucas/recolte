<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
  Plus, Search, Pencil, Trash2, X, AlertTriangle, 
  MapPin, Phone, TreeDeciduous, Navigation, Calendar, CheckCircle2 
} from 'lucide-vue-next';

// --- Configuration des Breadcrumbs ---
const breadcrumbs = [
    { title: 'Fiche Traçabilité', href: '/fiche-tracabilite' },
    { title: 'Exploitations', href: '#' }
];

// --- Données Initiales (Exemple) ---
const exploitations = ref([
  { 
    id: 1, 
    nom: 'RAKOTO', 
    prenom: 'Jean Paul', 
    adresse: 'Lot IVG 12 Analamahitsy', 
    telephone: '034 00 000 01',
    localisations: ['ANTANANARIVO', 'AMBOHIDRATRIMO'],
    parcelle: 5,
    pieds: 1250,
    gps: { lat: '-18.8792', long: '47.5079' },
    surface: 1.25,
    datesControle: ['2026-01-10', '2026-03-15'],
    ggn: '4052852677264'
  }
]);

// --- États du Formulaire ---
const searchQuery = ref('');
const showModal = ref(false);
const editId = ref(null);
const form = ref(emptyForm());

// Champs temporaires pour l'ajout multi-valeurs
const newLocation = ref('');
const newDate = ref('');

function emptyForm() {
  return {
    nom: '', prenom: '', adresse: '', telephone: '',
    localisations: [] as string[],
    parcelle: 0, pieds: 0,
    gps: { lat: '', long: '' },
    surface: 0,
    datesControle: [] as string[],
    ggn: ''
  };
}

// --- Logique Multi-Valeurs ---
function addLocation() {
  if (newLocation.value.trim()) {
    form.value.localisations.push(newLocation.value.trim().toUpperCase());
    newLocation.value = '';
  }
}
function removeLocation(index: number) { form.value.localisations.splice(index, 1); }

function addDate() {
  if (newDate.value) {
    form.value.datesControle.push(newDate.value);
    newDate.value = '';
  }
}
function removeDate(index: number) { form.value.datesControle.splice(index, 1); }

// --- Actions CRUD ---
const filtered = computed(() => {
  const q = searchQuery.value.toLowerCase();
  return exploitations.value.filter(e => 
    !q || e.nom.toLowerCase().includes(q) || e.prenom.toLowerCase().includes(q) || e.ggn.includes(q)
  );
});

function openAddModal() {
  editId.value = null;
  form.value = emptyForm();
  showModal.value = true;
}

function openEditModal(item: any) {
  editId.value = item.id;
  form.value = JSON.parse(JSON.stringify(item));
  showModal.value = true;
}

function save() {
  if (editId.value) {
    const idx = exploitations.value.findIndex(e => e.id === editId.value);
    exploitations.value[idx] = { ...form.value, id: editId.value };
  } else {
    exploitations.value.push({ ...form.value, id: Date.now() });
  }
  showModal.value = false;
}

// --- Suppression ---
const showDeleteModal = ref(false);
const itemToDelete = ref<any>(null);

function confirmDelete(item: any) {
  itemToDelete.value = item;
  showDeleteModal.value = true;
}

function executeDelete() {
  exploitations.value = exploitations.value.filter(e => e.id !== itemToDelete.value.id);
  showDeleteModal.value = false;
}
</script>

<template>
    <Head title="Exploitations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full max-w-none">
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-foreground uppercase tracking-tighter">Registre des Exploitations</h1>
                    <p class="text-[10px] text-muted-foreground uppercase tracking-[0.2em] font-bold opacity-50">
                        {{ exploitations.length }} Entité(s) GGN GlobalGap
                    </p>
                </div>
                <button @click="openAddModal" class="h-10 px-6 bg-[var(--brand-green)] text-white text-[11px] font-black uppercase tracking-widest rounded-xl flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all">
                    <Plus class="w-4 h-4" /> Nouveau Producteur
                </button>
            </div>

            <div class="p-6 bg-card border border-border rounded-2xl shadow-sm">
                <div class="relative max-w-md">
                    <Search class="absolute left-0 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <input v-model="searchQuery" type="text" placeholder="RECHERCHER NOM OU GGN..." class="w-full pl-8 pr-4 h-10 text-[11px] font-bold bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none text-foreground uppercase tracking-widest transition-colors" />
                </div>
            </div>

            <div class="bg-card border border-border rounded-2xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-muted/30 border-b border-border">
                                <th class="p-5 text-[10px] font-black uppercase opacity-40 tracking-widest">Producteur & Localisation</th>
                                <th class="p-5 text-[10px] font-black uppercase opacity-40 tracking-widest">Coordonnées</th>
                                <th class="p-5 text-[10px] font-black uppercase opacity-40 tracking-widest">Données Techniques</th>
                                <th class="p-5 text-[10px] font-black uppercase opacity-40 tracking-widest">Statut & Contrôles</th>
                                <th class="p-5 text-[10px] font-black uppercase opacity-40 tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="item in filtered" :key="item.id" class="hover:bg-muted/10 transition-colors group">
                                <td class="p-5">
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-[var(--brand-green)] text-white flex items-center justify-center font-black text-xs shadow-inner">
                                            {{ item.nom.charAt(0) }}{{ item.prenom.charAt(0) }}
                                        </div>
                                        <div class="space-y-1.5">
                                            <p class="text-sm font-black text-foreground uppercase tracking-tight">{{ item.nom }} {{ item.prenom }}</p>
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="loc in item.localisations" :key="loc" class="text-[9px] bg-muted/50 px-2 py-0.5 rounded-md font-bold text-muted-foreground border border-border uppercase">
                                                    {{ loc }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-foreground">
                                            <Phone class="w-3.5 h-3.5 text-[var(--brand-orange)]" /> {{ item.telephone }}
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] font-medium text-muted-foreground uppercase leading-tight max-w-[200px]">
                                            <MapPin class="w-3.5 h-3.5 flex-shrink-0" /> {{ item.adresse }}
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="grid grid-cols-2 gap-x-6 gap-y-1.5">
                                        <p class="text-[10px] font-bold"><span class="opacity-40 uppercase mr-1">Pieds:</span> {{ item.pieds }}</p>
                                        <p class="text-[10px] font-bold"><span class="opacity-40 uppercase mr-1">Surf:</span> {{ item.surface }} ha</p>
                                        <p class="text-[10px] font-black text-[var(--brand-green)] col-span-2 flex items-center gap-1">
                                            <Navigation class="w-3 h-3" /> {{ item.gps.lat }}, {{ item.gps.long }}
                                        </p>
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] font-black text-[var(--brand-green)] uppercase bg-[var(--brand-green)]/10 px-2 py-0.5 rounded flex items-center gap-1">
                                                <CheckCircle2 class="w-3 h-3" /> Approuvé
                                            </span>
                                            <span class="text-[10px] font-mono font-bold opacity-50">{{ item.ggn }}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="date in item.datesControle" :key="date" class="text-[9px] font-bold text-[var(--brand-orange)] border border-[var(--brand-orange)]/20 px-1.5 py-0.5 rounded uppercase">
                                                {{ date }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(item)" class="p-2.5 hover:text-[var(--brand-green)] transition-colors opacity-30 group-hover:opacity-100"><Pencil class="w-4 h-4" /></button>
                                        <button @click="confirmDelete(item)" class="p-2.5 hover:text-red-500 transition-colors opacity-30 group-hover:opacity-100"><Trash2 class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden" @click.stop>
                        <div class="p-6 border-b border-border flex justify-between items-center bg-muted/10">
                            <h2 class="text-[11px] font-black uppercase tracking-widest text-foreground">Fiche Producteur / GlobalGap</h2>
                            <button @click="showModal = false" class="text-muted-foreground hover:text-foreground"><X class="w-6 h-6" /></button>
                        </div>
                        
                        <div class="p-8 grid grid-cols-2 gap-x-8 gap-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1">Nom du Producteur</label>
                                <input v-model="form.nom" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1.5 text-sm text-foreground font-black uppercase transition-all" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1">Prénoms</label>
                                <input v-model="form.prenom" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1.5 text-sm text-foreground font-black uppercase transition-all" />
                            </div>

                            <div class="col-span-2 flex flex-col gap-1.5">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1">Adresse Administrative</label>
                                <input v-model="form.adresse" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1.5 text-sm text-foreground uppercase" />
                            </div>

                            <div class="col-span-2 space-y-3">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1 text-[var(--brand-green)]">Localisations de l'Exploitation</label>
                                <div class="flex gap-3">
                                    <input v-model="newLocation" @keyup.enter="addLocation" type="text" placeholder="AJOUTER UNE ZONE..." class="flex-1 bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1.5 text-xs text-foreground uppercase font-bold" />
                                    <button @click="addLocation" class="h-8 px-4 bg-[var(--brand-green)] text-white rounded-lg text-[10px] font-black uppercase transition-all active:scale-95">Ajouter</button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="(loc, index) in form.localisations" :key="index" class="inline-flex items-center gap-2 px-3 py-1.5 bg-muted border border-border rounded-lg text-[10px] font-black uppercase text-foreground">
                                        {{ loc }}
                                        <X @click="removeLocation(index)" class="w-3.5 h-3.5 cursor-pointer text-red-500" />
                                    </span>
                                </div>
                            </div>

                            <div class="col-span-2 space-y-3">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1 text-[var(--brand-orange)]">Dates de Contrôle Interne</label>
                                <div class="flex gap-3">
                                    <input v-model="newDate" type="date" class="flex-1 bg-transparent border-b border-border focus:border-[var(--brand-orange)] outline-none pb-1.5 text-xs text-foreground font-bold" />
                                    <button @click="addDate" class="h-8 px-4 bg-[var(--brand-orange)] text-white rounded-lg text-[10px] font-black uppercase transition-all active:scale-95">Ajouter</button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="(d, index) in form.datesControle" :key="index" class="inline-flex items-center gap-2 px-3 py-1.5 border border-[var(--brand-orange)]/30 bg-[var(--brand-orange)]/5 rounded-lg text-[10px] font-black text-[var(--brand-orange)]">
                                        <Calendar class="w-3.5 h-3.5" /> {{ d }}
                                        <X @click="removeDate(index)" class="w-3.5 h-3.5 cursor-pointer text-red-500" />
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1">Téléphone</label>
                                <input v-model="form.telephone" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1.5 text-sm text-foreground font-bold" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1">N° GGN GLOBALGAP</label>
                                <input v-model="form.ggn" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1.5 text-sm text-foreground font-mono font-bold" />
                            </div>

                            <div class="bg-muted/30 p-5 rounded-2xl col-span-2 grid grid-cols-3 gap-6 border border-border shadow-inner">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-black uppercase opacity-50">N° Parcelle</label>
                                    <input v-model="form.parcelle" type="number" class="bg-transparent border-b border-border outline-none text-sm font-black text-foreground" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-black uppercase opacity-50">Nb Pieds</label>
                                    <input v-model="form.pieds" type="number" class="bg-transparent border-b border-border outline-none text-sm font-black text-foreground" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-black uppercase opacity-50">Surface (ha)</label>
                                    <input v-model="form.surface" type="number" step="0.01" class="bg-transparent border-b border-border outline-none text-sm font-black text-foreground" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1 text-[var(--brand-orange)]">Latitude GPS</label>
                                <input v-model="form.gps.lat" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-orange)] outline-none pb-1.5 text-sm text-foreground font-bold" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black uppercase opacity-40 ml-1 text-[var(--brand-orange)]">Longitude GPS</label>
                                <input v-model="form.gps.long" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-orange)] outline-none pb-1.5 text-sm text-foreground font-bold" />
                            </div>
                        </div>

                        <div class="p-6 bg-muted/20 border-t border-border flex gap-4">
                            <button @click="showModal = false" class="flex-1 h-12 text-[11px] font-black uppercase border border-border rounded-xl hover:bg-muted text-foreground tracking-widest transition-all">Annuler</button>
                            <button @click="save" class="flex-1 h-12 text-[11px] font-black uppercase bg-[var(--brand-orange)] text-white rounded-xl shadow-xl shadow-[var(--brand-orange)]/20 tracking-widest transition-all active:scale-95">Enregistrer Fiche</button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
                <div v-if="showDeleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-[60] p-4">
                    <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                        <div class="p-8 text-center space-y-5">
                            <div class="w-16 h-16 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mx-auto border border-red-500/20 shadow-lg shadow-red-500/5">
                                <AlertTriangle class="w-8 h-8" />
                            </div>
                            <div class="space-y-2">
                                <h2 class="text-xs font-black uppercase tracking-widest text-foreground">Suppression Définitive</h2>
                                <p class="text-[11px] text-muted-foreground uppercase leading-relaxed font-bold">
                                    Supprimer le dossier de <span class="text-foreground font-black">{{ itemToDelete?.nom }}</span> ?<br>Toutes les données GGN seront perdues.
                                </p>
                            </div>
                        </div>
                        <div class="p-6 bg-muted/10 border-t border-border flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 h-11 text-[10px] font-black uppercase border border-border rounded-xl hover:bg-muted text-foreground">Annuler</button>
                            <button @click="executeDelete" class="flex-1 h-11 text-[10px] font-black uppercase bg-red-600 text-white rounded-xl shadow-lg shadow-red-600/20">Supprimer</button>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Scrollbar personnalisée pour le formulaire long */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
</style>