<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
  Plus, Search, Pencil, Trash2, X, AlertTriangle,
  MapPin, Phone, Building2, FileText, Globe, CheckCircle2
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/pages/components/Pagination.vue';

interface Societe {
  id: number;
  nom: string;
  adresse: string;
  phone: string;
  nif: string;
  stat: string;
  latitude: number | null;
  longitude: number | null;
  logo: string | null;
  logo_url: string | null;
  manager?: {
    id: number;
    name: string;
    email: string;
    role_id: number;
  };
}

interface PaginatedSocietes {
  data: Societe[];
  links: any[];
  current_page: number;
  last_page: number;
  total: number;
}

const props = defineProps<{
  societes: PaginatedSocietes;
  filters: { search?: string };
}>();

const breadcrumbs = [{ title: 'Gestion des Sociétés', href: '#' }];

// Recherche avec Filtre
const search = ref(props.filters.search || '');
watch(search, (value) => {
  router.get(
    route('societes.index'),
    { search: value },
    { preserveState: true, replace: true }
  );
});

// Modales
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const showDeleteModal = ref(false);
const deletingId = ref<number | null>(null);

// Formulaire Inertia mis à jour avec les champs du Manager
const form = useForm({
  // Champs Société
  nom: '',
  adresse: '',
  phone: '',
  nif: '',
  stat: '',
  latitude: '' as string | number,
  longitude: '' as string | number,
  logo: null as File | null,
  
  // Champs Manager
  manager_name: '',
  manager_email: '',
  manager_password: '',
  manager_role_id: 2, // Par défaut, l'ID du rôle "Manager"
});

// Règle de validation simple côté front-end
const isValid = (field: keyof typeof form.data) => {
  // Ces champs sont optionnels ou gérés différemment
  if (field === 'latitude' || field === 'longitude' || field === 'logo') return true;
  
  // Le mot de passe est optionnel en mode édition
  if (field === 'manager_password' && isEditing.value) {
    return true; 
  }

  // Pour les autres champs, on vérifie s'ils sont remplis
  return form[field] !== undefined && form[field] !== null && String(form[field]).trim().length > 0;
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (societe: Societe) => {
  isEditing.value = true;
  editingId.value = societe.id;
  
  // Remplissage Société
  form.nom = societe.nom;
  form.adresse = societe.adresse;
  form.phone = societe.phone;
  form.nif = societe.nif;
  form.stat = societe.stat;
  form.latitude = societe.latitude ?? '';
  form.longitude = societe.longitude ?? '';
  form.logo = null;
  
  // Remplissage Manager
  form.manager_name = societe.manager?.name || '';
  form.manager_email = societe.manager?.email || '';
  form.manager_password = ''; // On laisse vide pour ne pas forcer la modification
  form.manager_role_id = societe.manager?.role_id || 2;

  form.clearErrors();
  showModal.value = true;
};

const submitForm = () => {
  if (isEditing.value && editingId.value) {
    form.post(route('societes.update', editingId.value), {
      headers: { 'X-HTTP-Method-Override': 'PUT' },
      onSuccess: () => {
        showModal.value = false;
        form.reset();
      },
    });
  } else {
    form.post(route('societes.store'), {
      onSuccess: () => {
        showModal.value = false;
        form.reset();
      },
    });
  }
};

const confirmDelete = (id: number) => {
  deletingId.value = id;
  showDeleteModal.value = true;
};

const executeDelete = () => {
  if (deletingId.value) {
    router.delete(route('societes.destroy', deletingId.value), {
      onSuccess: () => {
        showDeleteModal.value = false;
        deletingId.value = null;
      },
    });
  }
};
</script>

<template>
  <Head title="Gestion des Sociétés" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">

      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-background p-4 rounded-xl border border-border shadow-sm">
        <div class="relative w-full sm:w-80">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--brand-green)]" />
          <input
            v-model="search"
            type="text"
            placeholder="Rechercher par Nom, NIF, STAT, Tél..."
            class="w-full pl-9 pr-4 h-10 text-sm bg-muted/20 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--brand-green)]/40 focus:border-[var(--brand-green)] transition-all"
          />
        </div>

        <button
          @click="openCreateModal"
          class="h-10 px-4 bg-[var(--brand-green)] text-white font-bold text-xs rounded-lg shadow-md hover:opacity-90 transition-all flex items-center gap-2 active:scale-95"
        >
          <Plus class="w-4 h-4" />
          <span>Nouvelle Société</span>
        </button>
      </div>

      <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-muted/40 border-b border-border text-xs font-bold uppercase text-muted-">
              <tr>
                <th class="px-4 py-3">Société</th>
                <th class="px-4 py-3">Identifiants (NIF / STAT)</th>
                <th class="px-4 py-3">Contact</th>
                <th class="px-4 py-3">Localisation & GPS</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="societe in societes.data" :key="societe.id" class="hover:bg-muted/10 transition-colors">
                
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-muted border border-[var(--brand-green)]/30 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                      <img v-if="societe.logo_url" :src="societe.logo_url" :alt="societe.nom" class="w-full h-full object-cover" />
                      <Building2 v-else class="w-5 h-5 text-[var(--brand-green)]" />
                    </div>
                    <div>
                      <span class="font-bold text- text-sm block">{{ societe.nom }}</span>
                      <span v-if="societe.manager" class="text-[10px] text-muted- flex items-center gap-1 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--brand-vert)]"></span>
                        Manager: {{ societe.manager.name }}
                      </span>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3">
                  <div class="space-y-1 text-xs">
                    <div class="flex items-center gap-1.5 text-muted-">
                      <FileText class="w-3.5 h-3.5 text-[var(--brand-vert)]" />
                      <span>NIF: <strong class="text- bg-[var(--brand-vert)]/10 text-[var(--brand-vert)] px-1.5 py-0.5 rounded font-mono">{{ societe.nif }}</strong></span>
                    </div>
                    <div class="flex items-center gap-1.5 text-muted-">
                      <FileText class="w-3.5 h-3.5 text-[var(--brand-green)]" />
                      <span>STAT: <strong class="text- bg-[var(--brand-green)]/10 text-[var(--brand-green)] px-1.5 py-0.5 rounded font-mono">{{ societe.stat }}</strong></span>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center gap-1.5 text-xs text-muted-">
                    <Phone class="w-3.5 h-3.5 text-[var(--brand-vert)]" />
                    <span class="font-medium text-">{{ societe.phone }}</span>
                  </div>
                </td>

                <td class="px-4 py-3">
                  <div class="space-y-1 text-xs">
                    <div class="flex items-center gap-1 text-muted-">
                      <MapPin class="w-3.5 h-3.5 shrink-0 text-[var(--brand-green)]" />
                      <span class="truncate max-w-[200px]">{{ societe.adresse }}</span>
                    </div>
                    <div v-if="societe.latitude && societe.longitude" class="flex items-center gap-1 text-[11px] text-[var(--brand-vert)] font-mono">
                      <Globe class="w-3 h-3" />
                      <span>{{ societe.latitude }}, {{ societe.longitude }}</span>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button 
                      @click="openEditModal(societe)" 
                      title="Modifier"
                      class="p-2 hover:bg-[var(--brand-vert)]/10 rounded-lg text-muted- hover:text-[var(--brand-vert)] transition-colors"
                    >
                      <Pencil class="w-4 h-4" />
                    </button>
                    <button 
                      @click="confirmDelete(societe.id)" 
                      title="Supprimer"
                      class="p-2 hover:bg-red-500/10 rounded-lg text-muted- hover:text-red-600 transition-colors"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>

              </tr>

              <tr v-if="societes.data.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-muted-">
                  Aucune société enregistrée pour le moment.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-4 border-t border-border">
          <Pagination :links="societes.links" />
        </div>
      </div>

      <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div class="bg-background border border-border w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            
            <div class="p-6 border-b border-border flex justify-between items-center bg-muted/20">
              <h3 class="text-lg font-bold flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[var(--brand-green)]"></span>
                {{ isEditing ? 'Modifier la Société' : 'Ajouter une Société' }}
              </h3>
              <button @click="showModal = false" class="p-1 hover:bg-muted rounded-lg transition-colors">
                <X class="w-5 h-5" />
              </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">Nom de l'entreprise *</label>
                  <div class="relative">
                    <input 
                      v-model="form.nom" 
                      type="text" 
                      required 
                      placeholder="Ex: SARL Madagascar Tech"
                      :class="[
                        'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm transition-all focus:outline-none',
                        form.errors.nom ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                        isValid('nom') ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                      ]" 
                    />
                    <CheckCircle2 v-if="isValid('nom')" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                  </div>
                  <span v-if="form.errors.nom" class="text-xs text-red-500 mt-1 block">{{ form.errors.nom }}</span>
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">Téléphone *</label>
                  <div class="relative">
                    <input 
                      v-model="form.phone" 
                      type="text" 
                      required 
                      placeholder="Ex: +261 34 00 000 00"
                      :class="[
                        'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm transition-all focus:outline-none',
                        form.errors.phone ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                        isValid('phone') ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                      ]" 
                    />
                    <CheckCircle2 v-if="isValid('phone')" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                  </div>
                  <span v-if="form.errors.phone" class="text-xs text-red-500 mt-1 block">{{ form.errors.phone }}</span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">NIF *</label>
                  <div class="relative">
                    <input 
                      v-model="form.nif" 
                      type="text" 
                      required 
                      placeholder="Ex: 0000000000"
                      :class="[
                        'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm font-mono transition-all focus:outline-none',
                        form.errors.nif ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                        isValid('nif') ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                      ]" 
                    />
                    <CheckCircle2 v-if="isValid('nif')" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                  </div>
                  <span v-if="form.errors.nif" class="text-xs text-red-500 mt-1 block">{{ form.errors.nif }}</span>
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">STAT *</label>
                  <div class="relative">
                    <input 
                      v-model="form.stat" 
                      type="text" 
                      required 
                      placeholder="Ex: 00000 00 0000 00000"
                      :class="[
                        'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm font-mono transition-all focus:outline-none',
                        form.errors.stat ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                        isValid('stat') ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                      ]" 
                    />
                    <CheckCircle2 v-if="isValid('stat')" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                  </div>
                  <span v-if="form.errors.stat" class="text-xs text-red-500 mt-1 block">{{ form.errors.stat }}</span>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold uppercase mb-1 text-muted-">Adresse complète *</label>
                <div class="relative">
                  <input 
                    v-model="form.adresse" 
                    type="text" 
                    required 
                    placeholder="Ex: Lot II M 40 Ankorondrano, Antananarivo"
                    :class="[
                      'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm transition-all focus:outline-none',
                      form.errors.adresse ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                      isValid('adresse') ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                    ]" 
                  />
                  <CheckCircle2 v-if="isValid('adresse')" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                </div>
                <span v-if="form.errors.adresse" class="text-xs text-red-500 mt-1 block">{{ form.errors.adresse }}</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">Latitude GPS</label>
                  <input 
                    v-model="form.latitude" 
                    type="number" 
                    step="any" 
                    placeholder="Ex: -18.8792"
                    class="w-full h-10 px-3 bg-muted/20 border border-border rounded-lg text-sm font-mono focus:border-[var(--brand-green)] focus:ring-2 focus:ring-[var(--brand-green)]/20 transition-all focus:outline-none" 
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">Longitude GPS</label>
                  <input 
                    v-model="form.longitude" 
                    type="number" 
                    step="any" 
                    placeholder="Ex: 47.5079"
                    class="w-full h-10 px-3 bg-muted/20 border border-border rounded-lg text-sm font-mono focus:border-[var(--brand-green)] focus:ring-2 focus:ring-[var(--brand-green)]/20 transition-all focus:outline-none" 
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold uppercase mb-1 text-muted-">Logo de l'entreprise</label>
                <input
                  type="file"
                  @change="(e: any) => form.logo = e.target.files[0]"
                  accept="image/*"
                  class="w-full text-sm text-muted- file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[var(--brand-green)] file:text-white hover:file:opacity-90 transition-all cursor-pointer"
                />
                <span v-if="form.errors.logo" class="text-xs text-red-500 mt-1 block">{{ form.errors.logo }}</span>
              </div>

              <div class="mt-6 mb-2 border-b border-border pb-2">
                <h4 class="text-sm font-bold text-muted- uppercase flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-[var(--brand-vert)]"></span>
                  Compte Manager Associé
                </h4>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">Nom du Manager *</label>
                  <div class="relative">
                    <input 
                      v-model="form.manager_name" 
                      type="text" 
                      required 
                      placeholder="Ex: John Doe"
                      :class="[
                        'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm transition-all focus:outline-none',
                        form.errors.manager_name ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                        isValid('manager_name') ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                      ]" 
                    />
                    <CheckCircle2 v-if="isValid('manager_name')" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                  </div>
                  <span v-if="form.errors.manager_name" class="text-xs text-red-500 mt-1 block">{{ form.errors.manager_name }}</span>
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase mb-1 text-muted-">Email de connexion *</label>
                  <div class="relative">
                    <input 
                      v-model="form.manager_email" 
                      type="email" 
                      required 
                      placeholder="Ex: manager@societe.com"
                      :class="[
                        'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm transition-all focus:outline-none',
                        form.errors.manager_email ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                        isValid('manager_email') ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                      ]" 
                    />
                    <CheckCircle2 v-if="isValid('manager_email')" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                  </div>
                  <span v-if="form.errors.manager_email" class="text-xs text-red-500 mt-1 block">{{ form.errors.manager_email }}</span>
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold uppercase mb-1 text-muted-">
                  Mot de passe 
                  <span v-if="!isEditing">*</span>
                  <span v-else class="normal-case text-[10px] text-muted- font-normal ml-1">(Laissez vide pour ne pas modifier)</span>
                </label>
                <div class="relative">
                  <input 
                    v-model="form.manager_password" 
                    type="password" 
                    :required="!isEditing" 
                    placeholder="Minimum 8 caractères"
                    :class="[
                      'w-full h-10 px-3 pr-8 bg-muted/20 border rounded-lg text-sm transition-all focus:outline-none',
                      form.errors.manager_password ? 'border-red-500 focus:ring-2 focus:ring-red-500/20' : 
                      (isValid('manager_password') && form.manager_password) ? 'border-[var(--brand-vert)] focus:ring-2 focus:ring-[var(--brand-vert)]/20' : 'border-border focus:border-[var(--brand-green)]'
                    ]" 
                  />
                  <CheckCircle2 v-if="isValid('manager_password') && form.manager_password.length > 0" class="w-4 h-4 text-[var(--brand-vert)] absolute right-2.5 top-1/2 -translate-y-1/2" />
                </div>
                <span v-if="form.errors.manager_password" class="text-xs text-red-500 mt-1 block">{{ form.errors.manager_password }}</span>
              </div>

              <div class="pt-4 flex gap-3 mt-4">
                <button 
                  type="button" 
                  @click="showModal = false" 
                  class="flex-1 h-10 border border-border rounded-lg text-xs font-bold uppercase hover:bg-muted transition-colors"
                >
                  Annuler
                </button>
                <button 
                  type="submit" 
                  :disabled="form.processing" 
                  class="flex-1 h-10 bg-[var(--brand-green)] text-white rounded-lg text-xs font-bold uppercase shadow-md hover:opacity-90 disabled:opacity-50 transition-all flex items-center justify-center gap-2"
                >
                  <span>{{ isEditing ? 'Mettre à jour' : 'Enregistrer' }}</span>
                </button>
              </div>

            </form>
          </div>
        </div>
      </Teleport>

      <Teleport to="body">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div class="bg-background border border-border w-full max-w-md rounded-2xl shadow-2xl overflow-hidden p-6 space-y-4 text-center animate-in fade-in zoom-in-95 duration-200">
            <div class="w-12 h-12 bg-red-500/10 text-red-600 rounded-full flex items-center justify-center mx-auto">
              <AlertTriangle class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-bold">Confirmer la suppression</h3>
            <p class="text-xs text-muted-">Êtes-vous sûr de vouloir supprimer définitivement cette société et son manager associé ? Cette opération ne peut pas être annulée.</p>
            <div class="flex gap-3 pt-2">
              <button @click="showDeleteModal = false" class="flex-1 h-10 border border-border rounded-lg text-xs font-bold uppercase hover:bg-muted transition-colors">
                Annuler
              </button>
              <button @click="executeDelete" class="flex-1 h-10 bg-red-600 text-white rounded-lg text-xs font-bold uppercase shadow-md hover:bg-red-700 transition-colors">
                Supprimer
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>