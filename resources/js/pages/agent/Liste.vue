<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { Plus, Search, Pencil, Trash2, X, Sun, Moon, AlertTriangle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Fiche Traçabilité', href: '/fiche-tracabilite' },
    { title: 'Enquêteurs', href: '/enqueteurs' }
];
 
const enqueteurs = ref([
  { id: 1, nom: 'Randrianarisoa', prenom: 'Marie', poste: 'Triage', travail: 'Jour' },
  { id: 2, nom: 'Rakotonirina', prenom: 'Paul', poste: 'Réception', travail: 'Nuit' },
  { id: 3, nom: 'Razafimahefa', prenom: 'Sophie', poste: 'Expédition', travail: 'Jour' },
])
 
const postes = ['Triage', 'Soufrage', 'Réception', 'Expédition', 'Palettisation']
 
const searchQuery   = ref('')
const filterPoste   = ref('')
const filterTravail = ref('')
 
const filtered = computed(() => {
  const q = searchQuery.value.toLowerCase()
  return enqueteurs.value.filter(e => {
    const matchText    = !q || e.nom.toLowerCase().includes(q) || e.prenom.toLowerCase().includes(q)
    const matchPoste   = !filterPoste.value   || e.poste   === filterPoste.value
    const matchTravail = !filterTravail.value || e.travail === filterTravail.value
    return matchText && matchPoste && matchTravail
  })
})
 
// --- Modal Formulaire ---
const showModal = ref(false)
const editId    = ref(null)
const formError = ref('')
const form = ref({ nom: '', prenom: '', poste: '', travail: '' })
let nextId = 100
 
// --- Modal Suppression ---
const showDeleteModal = ref(false)
const enqueteurToDelete = ref<any>(null)

function openAddModal() {
  editId.value = null
  formError.value = ''
  form.value = { nom: '', prenom: '', poste: '', travail: 'Jour' }
  showModal.value = true
}
 
function openEditModal(enqueteur) {
  editId.value = enqueteur.id
  formError.value = ''
  form.value = { ...enqueteur }
  showModal.value = true
}
 
function closeModal() { showModal.value = false }
function handleOverlayClick(e) { if (e.target === e.currentTarget) closeModal() }
 
function saveEnqueteur() {
  if (!form.value.nom.trim() || !form.value.prenom.trim() || !form.value.poste || !form.value.travail) {
    formError.value = 'Veuillez remplir tous les champs.'
    return
  }
  if (editId.value) {
    const idx = enqueteurs.value.findIndex(e => e.id === editId.value)
    if (idx !== -1) enqueteurs.value[idx] = { id: editId.value, ...form.value }
  } else {
    enqueteurs.value.push({ id: nextId++, ...form.value })
  }
  closeModal()
}

// --- Fonctions de suppression ---
function confirmDelete(enqueteur) {
  enqueteurToDelete.value = enqueteur
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  enqueteurToDelete.value = null
}

function executeDelete() {
  if (enqueteurToDelete.value) {
    enqueteurs.value = enqueteurs.value.filter(e => e.id !== enqueteurToDelete.value.id)
  }
  cancelDelete()
}
 
function getPosteBadge(poste) {
  const common = 'text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border'
  if (poste === 'Réception' || poste === 'Triage') {
      return `${common} bg-emerald-500/10 text-emerald-500 border-emerald-500/20`
  }
  return `${common} bg-orange-500/10 text-orange-500 border-orange-500/20`
}
</script>
 
<template>
    <Head title="Enquêteurs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-foreground uppercase tracking-tighter">Gestion des Enquêteurs</h1>
                    <p class="text-[11px] text-muted-foreground uppercase tracking-widest font-medium opacity-60">{{ enqueteurs.length }} profil(s) actif(s)</p>
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
                    <option value="Jour" class="bg-card">Jour</option>
                    <option value="Nuit" class="bg-card">Nuit</option>
                </select>
            </div>

            <div class="bg-card border border-border rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-muted/20 border-b border-border">
                                <th class="p-4 text-[10px] font-black uppercase opacity-40">Enquêteur</th>
                                <th class="p-4 text-[10px] font-black uppercase opacity-40">Poste</th>
                                <th class="p-4 text-[10px] font-black uppercase opacity-40">Période</th>
                                <th class="p-4 text-[10px] font-black uppercase opacity-40 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-if="filtered.length === 0">
                                <td colspan="4" class="p-12 text-center text-muted-foreground text-sm italic">Aucun enquêteur trouvé</td>
                            </tr>
                            <tr v-for="enqueteur in filtered" :key="enqueteur.id" class="hover:bg-muted/10 transition-colors group">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[var(--brand-green)] text-white flex items-center justify-center font-black text-[10px]">
                                            {{ enqueteur.nom.charAt(0) }}{{ enqueteur.prenom.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-foreground uppercase tracking-tight">{{ enqueteur.nom }}</p>
                                            <p class="text-[10px] text-muted-foreground uppercase font-bold opacity-50">{{ enqueteur.prenom }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span :class="getPosteBadge(enqueteur.poste)">{{ enqueteur.poste }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2 text-[10px] font-bold uppercase">
                                        <Sun v-if="enqueteur.travail === 'Jour'" class="w-3.5 h-3.5 text-[var(--brand-orange)]" />
                                        <Moon v-else class="w-3.5 h-3.5 text-[var(--brand-green)]" />
                                        <span :class="enqueteur.travail === 'Jour' ? 'text-[var(--brand-orange)]' : 'text-[var(--brand-green)]'">{{ enqueteur.travail }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditModal(enqueteur)" class="p-2.5 hover:text-[var(--brand-green)] transition-colors opacity-40 group-hover:opacity-100"><Pencil class="w-4 h-4" /></button>
                                        <button @click="confirmDelete(enqueteur)" class="p-2.5 hover:text-red-500 transition-colors opacity-40 group-hover:opacity-100"><Trash2 class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Teleport to="body">
                <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click="handleOverlayClick">
                    <div class="bg-card border border-border rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>
                        <div class="p-6 border-b border-border flex justify-between items-center bg-muted/10">
                            <h2 class="text-[11px] font-black uppercase tracking-widest text-foreground">{{ editId ? 'Modifier' : 'Ajouter un Enquêteur' }}</h2>
                            <button @click="closeModal" class="text-muted-foreground hover:text-foreground"><X class="w-5 h-5" /></button>
                        </div>
                        <div class="p-8 space-y-8">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold uppercase opacity-40 ml-1">Nom</label>
                                    <input v-model="form.nom" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1 text-sm text-foreground transition-all uppercase font-bold" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold uppercase opacity-40 ml-1">Prénoms</label>
                                    <input v-model="form.prenom" type="text" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1 text-sm text-foreground transition-all uppercase font-bold" />
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[10px] font-bold uppercase opacity-40 ml-1">Poste</label>
                                <select v-model="form.poste" class="bg-transparent border-b border-border focus:border-[var(--brand-green)] outline-none pb-1 text-sm text-foreground transition-all uppercase font-bold">
                                    <option v-for="p in postes" :key="p" :value="p" class="bg-card">{{ p }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-[10px] font-bold uppercase opacity-40 ml-1">Période</label>
                                <div class="flex bg-muted/50 p-1 rounded-lg border border-border">
                                    <button v-for="opt in ['Jour', 'Nuit']" :key="opt" @click="form.travail = opt" :class="form.travail === opt ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground opacity-30'" class="flex-1 py-2 text-[11px] font-black rounded-md transition-all uppercase">{{ opt }}</button>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-muted/10 border-t border-border flex gap-4">
                            <button @click="closeModal" class="flex-1 h-11 text-[11px] font-bold uppercase border border-border rounded-lg hover:bg-muted text-foreground transition-all">Annuler</button>
                            <button @click="saveEnqueteur" class="flex-1 h-11 text-[11px] font-bold uppercase bg-[var(--brand-orange)] hover:opacity-90 text-white rounded-lg shadow-lg shadow-[var(--brand-orange)]/20 transition-all tracking-widest">{{ editId ? 'Mettre à jour' : 'Confirmer' }}</button>
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
                                <h2 class="text-xs font-black uppercase tracking-widest text-foreground">Confirmation</h2>
                                <p class="text-[11px] text-muted-foreground uppercase leading-relaxed font-bold">
                                    Voulez-vous supprimer l'enquêteur <span class="text-foreground font-black">{{ enqueteurToDelete?.nom }}</span> ? Cette action est irréversible.
                                </p>
                            </div>
                        </div>
                        <div class="p-6 bg-muted/10 border-t border-border flex gap-3">
                            <button @click="cancelDelete" class="flex-1 h-10 text-[10px] font-black uppercase border border-border rounded-lg hover:bg-muted text-foreground transition-all">Annuler</button>
                            <button @click="executeDelete" class="flex-1 h-10 text-[10px] font-black uppercase bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all shadow-lg shadow-red-600/20">Supprimer</button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>