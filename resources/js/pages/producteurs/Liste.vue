<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
  Plus, Search, Pencil, Trash2, X, AlertTriangle,
  MapPin, Phone, TreeDeciduous, Navigation, Calendar, CheckCircle2, Layers, UserCheck
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/pages/components/Pagination.vue';

// -------------------------------------------------------
// Types
// -------------------------------------------------------
interface Parcelle {
  num: number
  pieds: number
  surface: number
  gps_lat: string
  gps_long: string
  localisation: string
}

interface Producteur {
  id: number
  nom: string
  prenom: string
  email: string
  telephone: string
  adresse: string
  ggn: string
  produit: string
  datesControle: string[]
  parcelles: Omit<Parcelle, '_newLoc'>[]
}

// -------------------------------------------------------
// Props
// -------------------------------------------------------
const props = defineProps<{ producteurs: Producteur[]; }>();

const breadcrumbs = [{ title: 'Registre des Producteurs', href: '#' }];

// -------------------------------------------------------
// Formulaire Inertia
// -------------------------------------------------------
const form = useForm({
  nom: '', prenom: '', email: '', adresse: '', telephone: '',
  ggn: '', produit: '',
  dates_controle: [] as string[],
  parcelles: [emptyParcelle()] as Parcelle[],
});

// -------------------------------------------------------
// État UI
// -------------------------------------------------------
const showModal       = ref(false);
const editId          = ref<number | null>(null);
const newDate         = ref('');
const searchQuery     = ref('');
const currentPage     = ref(1);
const itemsPerPage    = ref(10);
const deletingId      = ref<number | null>(null);
const showDeleteModal = ref(false);
const itemToDelete    = ref<Producteur | null>(null);

// -------------------------------------------------------
// Helpers
// -------------------------------------------------------
function emptyParcelle(): Parcelle {
  return {
    num: 0, pieds: 0, surface: 0,
    gps_lat: '', gps_long: '',
    localisation: '',
  };
}

function formatDate(d: string) {
  if (!d) return '';
  const [y, m, day] = d.split('-');
  return `${day}/${m}/${y}`;
}

// -------------------------------------------------------
// Modal
// -------------------------------------------------------
function openAddModal() {
  editId.value          = null;
  form.nom              = '';
  form.prenom           = '';
  form.email            = '';
  form.adresse          = '';
  form.telephone        = '';
  form.ggn              = '';
  form.produit          = '';
  form.dates_controle   = [];
  form.parcelles        = [emptyParcelle()];
  form.clearErrors();
  showModal.value       = true;
}

function openEditModal(item: Producteur) {
  editId.value        = item.id;
  form.nom            = item.nom;
  form.prenom         = item.prenom;
  form.email          = item.email;
  form.adresse        = item.adresse;
  form.telephone      = item.telephone;
  form.ggn            = item.ggn;
  form.produit        = item.produit;
  form.dates_controle = [...item.datesControle];
  form.parcelles      = (item.parcelles ?? []).map(p => ({
    num:          p.num,
    pieds:        p.pieds,
    surface:      p.surface,
    gps_lat:      String(p.gps_lat  ?? ''),
    gps_long:     String(p.gps_long ?? ''),
    localisation: Array.isArray(p.localisation)
      ? (p.localisation as string[]).join('')
      : String(p.localisation ?? ''),
  }));
  form.clearErrors();
  showModal.value = true;
}

// -------------------------------------------------------
// Save
// -------------------------------------------------------
function save() {
  const payload = {
    ...form.data(),
    parcelles: form.parcelles,
  };

  if (editId.value) {
    form.transform(() => payload).put(route('producteurs.update', editId.value), {
      preserveScroll: true,
      onSuccess: () => { showModal.value = false; },
    });
  } else {
    form.transform(() => payload).post(route('producteurs.store'), {
      preserveScroll: true,
      onSuccess: () => { showModal.value = false; },
    });
  }
}

// -------------------------------------------------------
// Delete
// -------------------------------------------------------
function confirmDelete(item: Producteur) {
  itemToDelete.value = item;
  showDeleteModal.value = true;
}

function executeDelete() {
  if (!itemToDelete.value) return;
  deletingId.value = itemToDelete.value.id;
  useForm({}).delete(route('producteurs.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
    onFinish:  () => { deletingId.value = null; },
  });
}

// -------------------------------------------------------
// Parcelles / Dates
// -------------------------------------------------------
function addParcelleBlock() { form.parcelles.push(emptyParcelle()); }

function removeParcelleBlock(i: number) {
  if (form.parcelles.length > 1) form.parcelles.splice(i, 1);
}

function addDate() {
  if (newDate.value && !form.dates_controle.includes(newDate.value)) {
    form.dates_controle.push(newDate.value);
    newDate.value = '';
  }
}

function removeDate(i: number) { form.dates_controle.splice(i, 1); }

// -------------------------------------------------------
// Filtrage + Pagination
// -------------------------------------------------------
const filtered = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  if (!q) return props.producteurs;
  return props.producteurs.filter(e =>
    e.nom.toLowerCase().includes(q) ||
    e.prenom.toLowerCase().includes(q) ||
    (e.ggn ?? '').includes(q) ||
    (e.adresse ?? '').toLowerCase().includes(q) ||
    (e.parcelles ?? []).some(p =>
      (p.localisation ?? '').toLowerCase().includes(q)
    )
  );
});

const paginated = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return filtered.value.slice(start, start + itemsPerPage.value);
});

watch([searchQuery, itemsPerPage], () => { currentPage.value = 1; });
</script>

<template>
  <Head title="Registre des Producteurs" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6 w-full max-w-none">

      <!-- En-tête -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-black text-foreground uppercase tracking-tighter">Registre des Producteurs</h1>
          <p class="text-[12px] text-foreground tracking-[0.2em] font-bold">
            {{ props.producteurs.length }} Entité(s)
          </p>
        </div>

        <!-- Bouton si enquêteurs disponibles -->
        <button
          @click="openAddModal"
          class="h-10 px-6 bg-[var(--brand-green)] text-white text-[12px] font-black tracking-widest rounded-xl
                 flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all uppercase">
          <Plus class="w-4 h-4" /> Nouveau Producteur
        </button>
      </div>

      <!-- Recherche -->
      <div class="p-6 bg-card border border-border rounded-2xl shadow-sm shadow-black/5">
        <div class="relative max-w-md">
          <Search class="absolute left-0 top-1/2 -translate-y-1/2 w-4 h-4 text-foreground" />
          <input v-model="searchQuery" type="text" placeholder="Filtrer..."
            class="w-full pl-8 pr-4 h-10 text-[12px] font-bold bg-transparent border-b border-border
                   focus:border-[var(--brand-green)] outline-none text-foreground tracking-widest transition-colors uppercase" />
        </div>
      </div>

      <!-- Tableau -->
      <div class="bg-card border border-border rounded-2xl overflow-hidden shadow-sm shadow-black/5">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-border bg-[var(--brand-green)] text-white">
                <th class="p-5 text-[12px] font-black uppercase w-[280px]">Producteur & Coordonnées</th>
                <th class="p-5 text-[12px] font-black uppercase">Détails des Parcelles</th>
                <th class="p-5 text-[12px] font-black uppercase w-[170px]">Contrôle Interne</th>
                <th class="p-5 text-[12px] font-black uppercase text-right w-[100px]">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="(item, idx) in paginated" :key="item.id"
                :class="idx % 2 === 0
                  ? 'bg-white dark:bg-zinc-900'
                  : 'bg-zinc-50 dark:bg-zinc-800/60'"
                class="hover:bg-[var(--brand-green)]/5 transition-colors group align-top">

                <!-- Producteur -->
                <td class="p-5 border-r border-border">
                  <div class="space-y-3">
                    <div class="space-y-1.5">
                      <span v-if="item.produit"
                        :class="item.produit === 'Vanille'
                          ? 'bg-amber-100 text-amber-800 border-amber-200'
                          : 'bg-red-100 text-red-800 border-red-200'"
                        class="text-[11px] font-black uppercase px-1.5 py-0.5 rounded border inline-block">
                        {{ item.produit }}
                      </span>
                      <p class="text-sm font-black text-foreground uppercase tracking-tight">
                        {{ item.nom }} {{ item.prenom }}
                      </p>
                      <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-[11px] opacity-70">GGN: {{ item.ggn }}</p>
                        <span class="text-[9px] font-black text-white uppercase bg-[var(--brand-green)]
                                     px-1.5 py-0.5 rounded flex items-center gap-1 shrink-0">
                          <CheckCircle2 class="w-2.5 h-2.5" /> Approuvé
                        </span>
                      </div>
                    </div>
                    <div class="space-y-1.5 pt-2 border-t border-border/30">
                      <div class="flex items-center gap-2 text-[12px] font-bold">
                        <Phone class="w-3.5 h-3.5 text-[var(--brand-orange)] shrink-0" />
                        {{ item.telephone }}
                      </div>
                      <div class="flex items-start gap-2 text-[12px] font-medium uppercase leading-tight">
                        <MapPin class="w-3.5 h-3.5 shrink-0 mt-0.5 text-[var(--brand-green)]" />
                        {{ item.adresse }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Parcelles -->
                <td class="p-0 border-r border-border">
                  <table class="w-full border-collapse">
                    <tbody class="divide-y divide-border">
                      <tr v-for="(parcelle, pIdx) in (item.parcelles ?? [])" :key="pIdx"
                        class="hover:bg-[var(--brand-green)]/5 transition-colors">
                        <td class="p-4 min-w-[160px]">
                          <p class="text-[12px] font-black uppercase text-[var(--brand-orange)] mb-1.5">
                            Parcelle #{{ parcelle.num || pIdx + 1 }}
                          </p>
                          <span v-if="parcelle.localisation"
                            class="text-[9px] bg-muted border border-border px-1.5 py-0.5 rounded font-bold uppercase">
                            {{ parcelle.localisation }}
                          </span>
                          <span v-else class="text-[10px] opacity-30 italic">—</span>
                        </td>
                        <td class="p-4 min-w-[100px]">
                          <div class="space-y-1 text-[12px] font-bold">
                            <p><span class="opacity-60 uppercase text-[10px]">Pieds </span>{{ parcelle.pieds }}</p>
                            <p><span class="opacity-60 uppercase text-[10px]">Surf. </span>{{ parcelle.surface }} ha</p>
                          </div>
                        </td>
                        <td class="p-4 min-w-[130px]">
                          <div class="space-y-1 text-[12px]">
                            <div class="flex items-center gap-1">
                              <Navigation class="w-3 h-3 shrink-0 opacity-60" />
                              <span class="opacity-60 uppercase text-[10px]">Lat</span>
                              <span class="font-bold">{{ parcelle.gps_lat ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1 pl-4">
                              <span class="opacity-60 uppercase text-[10px]">Long</span>
                              <span class="font-bold">{{ parcelle.gps_long ?? '—' }}</span>
                            </div>
                          </div>
                        </td>
                      </tr>
                      <tr v-if="!(item.parcelles ?? []).length">
                        <td colspan="3" class="p-4 text-[11px] opacity-30 uppercase italic">Aucune parcelle</td>
                      </tr>
                    </tbody>
                  </table>
                </td>

                <!-- Dates contrôle -->
                <td class="p-5">
                  <div class="flex flex-col gap-2">
                    <div v-for="date in item.datesControle" :key="date"
                      class="flex items-center gap-1.5 px-2 py-1.5 border border-[var(--brand-orange)]/20
                             bg-[var(--brand-orange)]/5 rounded-lg text-[12px] font-black text-[var(--brand-orange)] w-fit">
                      <Calendar class="w-3 h-3" /> {{ formatDate(date) }}
                    </div>
                    <p v-if="!item.datesControle?.length"
                      class="text-[10px] font-bold opacity-30 uppercase italic">Aucun contrôle</p>
                  </div>
                </td>

                <!-- Actions -->
                <td class="p-5 text-right border-l border-border/50">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openEditModal(item)" :disabled="form.processing"
                      class="p-2.5 hover:bg-muted rounded-lg hover:text-[var(--brand-green)] transition-all
                             disabled:opacity-40 disabled:cursor-not-allowed">
                      <Pencil class="w-4 h-4" />
                    </button>
                    <button @click="confirmDelete(item)" :disabled="deletingId === item.id"
                      class="p-2.5 hover:bg-red-50 rounded-lg hover:text-red-500 transition-all
                             disabled:opacity-40 disabled:cursor-not-allowed">
                      <svg v-if="deletingId === item.id" class="animate-spin w-4 h-4"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                      </svg>
                      <Trash2 v-else class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="paginated.length === 0">
                <td colspan="5" class="p-12 text-center text-[12px] font-black uppercase opacity-30 tracking-widest">
                  Aucun producteur trouvé
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

      <!-- ============================================== -->
      <!-- Modal Ajout / Édition                         -->
      <!-- ============================================== -->
      <Teleport to="body">
        <div v-if="showModal"
          class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden" @click.stop>

            <div class="p-6 border-b border-border flex justify-between items-center bg-[var(--brand-green)]">
              <h2 class="text-[18px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                <TreeDeciduous class="w-4 h-4" />
                {{ editId ? 'Modifier la Fiche' : 'Nouvelle Fiche Producteur' }}
              </h2>
              <button @click="showModal = false" class="text-white hover:opacity-70 transition-opacity">
                <X class="w-6 h-6" />
              </button>
            </div>

            <div class="p-8 space-y-8 max-h-[75vh] overflow-y-auto custom-scrollbar">

              <!-- Infos de base -->
              <div class="grid grid-cols-2 gap-6">
                <!-- Produit -->
                <div class="col-span-2 flex flex-col gap-1.5">
                  <label class="text-[13px] font-black uppercase">Produit</label>
                  <select v-model="form.produit" class="input-line"
                    :class="{ 'border-red-500': form.errors.produit }">
                    <option value="" disabled selected>-- Sélectionner un produit --</option>
                    <option value="Vanille">Vanille</option>
                    <option value="Litchi">Litchi</option>
                  </select>
                  <p v-if="form.errors.produit" class="text-[11px] text-red-500 font-bold">{{ form.errors.produit }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-[12px] font-black">Nom</label>
                  <input v-model="form.nom" type="text" class="input-line"
                    :class="{ 'border-red-500': form.errors.nom }" />
                  <p v-if="form.errors.nom" class="text-[11px] text-red-500 font-bold">{{ form.errors.nom }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-[12px] font-black">Prénoms</label>
                  <input v-model="form.prenom" type="text" class="input-line"
                    :class="{ 'border-red-500': form.errors.prenom }" />
                  <p v-if="form.errors.prenom" class="text-[11px] text-red-500 font-bold">{{ form.errors.prenom }}</p>
                </div>

                <div class="col-span-2 flex flex-col gap-1.5">
                  <label class="text-[12px] font-black">Adresse Administrative</label>
                  <input v-model="form.adresse" type="text" class="input-line" />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-[12px] font-black">Téléphone</label>
                  <input v-model="form.telephone" type="text" class="input-line" />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-[12px] font-black uppercase">N° GGN GLOBALGAP</label>
                  <input v-model="form.ggn" type="text" class="input-line"
                    :class="{ 'border-red-500': form.errors.ggn }" />
                  <p v-if="form.errors.ggn" class="text-[11px] text-red-500 font-bold">{{ form.errors.ggn }}</p>
                </div>
              </div>

              <!-- Dates de contrôle -->
              <div class="space-y-3 bg-muted/20 p-4 rounded-2xl border border-[var(--brand-green)]">
                <label class="text-[12px] font-black uppercase">Date Contrôle Interne</label>
                <div class="flex gap-3">
                  <input v-model="newDate" type="date"
                    @click="($event.target as HTMLInputElement).showPicker()"
                    class="text-[12px] flex-1 bg-transparent border-b border-border
                           focus:border-[var(--brand-green)] outline-none pb-1.5 text-foreground font-bold" />
                  <button @click="addDate"
                    class="h-8 px-4 bg-[var(--brand-green)] text-white rounded-lg text-[12px] font-black active:scale-95 transition-all">
                    + Ajouter
                  </button>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span v-for="(d, idx) in form.dates_controle" :key="idx"
                    class="inline-flex items-center gap-2 px-3 py-1.5 border border-[var(--brand-green)]/30
                           bg-[var(--brand-orange)]/5 rounded-lg text-[12px] font-black">
                    <Calendar class="w-3.5 h-3.5" /> {{ formatDate(d) }}
                    <X @click="removeDate(idx)" class="w-3.5 h-3.5 cursor-pointer text-red-500 hover:scale-110 transition-transform" />
                  </span>
                </div>
              </div>

              <hr class="border-border">

              <!-- Parcelles -->
              <div class="space-y-6">
                <div class="flex items-center justify-between">
                  <h3 class="text-[12px] font-black uppercase tracking-tighter flex items-center gap-2">
                    <Layers class="w-4 h-4 text-[var(--brand-green)]" /> Détails des Parcelles
                  </h3>
                  <button @click="addParcelleBlock"
                    class="text-[12px] bg-[var(--brand-green)] text-white px-3 py-1.5 rounded-lg active:scale-95 transition-all">
                    + Ajouter une parcelle
                  </button>
                </div>

                <div v-for="(parcelle, pIdx) in form.parcelles" :key="pIdx"
                  class="relative p-6 bg-card border border-[var(--brand-green)] rounded-2xl space-y-5 group/block shadow-sm shadow-black/5">

                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-black uppercase text-[var(--brand-orange)]">
                      Parcelle {{ pIdx + 1 }}
                    </span>
                    <button v-if="form.parcelles.length > 1" @click="removeParcelleBlock(pIdx)"
                      class="w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center
                             shadow opacity-0 group-hover/block:opacity-100 transition-all hover:scale-110">
                      <X class="w-3.5 h-3.5" />
                    </button>
                  </div>

                  <div class="grid grid-cols-3 gap-4">
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[11px] font-black uppercase opacity-70">N° Parcelle</label>
                      <input v-model="parcelle.num" type="number" class="input-line"
                        :class="{ 'border-red-500': form.errors[`parcelles.${pIdx}.num`] }" />
                      <p v-if="form.errors[`parcelles.${pIdx}.num`]" class="text-[11px] text-red-500 font-bold">
                        {{ form.errors[`parcelles.${pIdx}.num`] }}
                      </p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[11px] font-black uppercase opacity-70">Nbre de Pieds</label>
                      <input v-model="parcelle.pieds" type="number" class="input-line"
                        :class="{ 'border-red-500': form.errors[`parcelles.${pIdx}.pieds`] }" />
                      <p v-if="form.errors[`parcelles.${pIdx}.pieds`]" class="text-[11px] text-red-500 font-bold">
                        {{ form.errors[`parcelles.${pIdx}.pieds`] }}
                      </p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[11px] font-black uppercase opacity-70">Surface (ha)</label>
                      <input v-model="parcelle.surface" type="number" step="0.01" class="input-line"
                        :class="{ 'border-red-500': form.errors[`parcelles.${pIdx}.surface`] }" />
                      <p v-if="form.errors[`parcelles.${pIdx}.surface`]" class="text-[11px] text-red-500 font-bold">
                        {{ form.errors[`parcelles.${pIdx}.surface`] }}
                      </p>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4 p-4 bg-muted/10 rounded-xl border border-border/50">
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[11px] font-black uppercase text-[var(--brand-orange)]">Latitude GPS</label>
                      <input v-model="parcelle.gps_lat" type="text" placeholder="-18.0000" class="input-line"
                        :class="{ 'border-red-500': form.errors[`parcelles.${pIdx}.gps_lat`] }" />
                      <p v-if="form.errors[`parcelles.${pIdx}.gps_lat`]" class="text-[11px] text-red-500 font-bold">
                        {{ form.errors[`parcelles.${pIdx}.gps_lat`] }}
                      </p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[11px] font-black uppercase text-[var(--brand-orange)]">Longitude GPS</label>
                      <input v-model="parcelle.gps_long" type="text" placeholder="47.0000" class="input-line"
                        :class="{ 'border-red-500': form.errors[`parcelles.${pIdx}.gps_long`] }" />
                      <p v-if="form.errors[`parcelles.${pIdx}.gps_long`]" class="text-[11px] text-red-500 font-bold">
                        {{ form.errors[`parcelles.${pIdx}.gps_long`] }}
                      </p>
                    </div>
                  </div>

                  <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] font-black uppercase opacity-70">Localisation</label>
                    <input v-model="parcelle.localisation" type="text"
                      placeholder="Zone / commune..."
                      class="input-line uppercase"
                      :class="{ 'border-red-500': form.errors[`parcelles.${pIdx}.localisation`] }" />
                    <p v-if="form.errors[`parcelles.${pIdx}.localisation`]" class="text-[11px] text-red-500 font-bold">
                      {{ form.errors[`parcelles.${pIdx}.localisation`] }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer modal -->
            <div class="p-6 bg-muted/20 border-t border-border flex gap-4">
              <button @click="showModal = false"
                class="flex-1 h-12 text-[14px] font-black border border-border rounded-xl
                       hover:bg-muted text-foreground tracking-widest transition-all">
                Annuler
              </button>
              <button @click="save" :disabled="form.processing"
                class="flex-1 h-12 text-[14px] font-black bg-[var(--brand-orange)] text-black rounded-xl
                       shadow-xl shadow-[var(--brand-orange)]/20 tracking-widest transition-all active:scale-95
                       disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100
                       flex items-center justify-center gap-2">
                <svg v-if="form.processing" class="animate-spin w-4 h-4"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span>{{ form.processing ? 'Enregistrement...' : 'Enregistrer la Fiche' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- ============================================== -->
      <!-- Modal Suppression                             -->
      <!-- ============================================== -->
      <Teleport to="body">
        <div v-if="showDeleteModal"
          class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-[60] p-4">
          <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="p-8 text-center space-y-5">
              <div class="w-16 h-16 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center
                          mx-auto border border-red-500/20 shadow-lg shadow-red-500/5">
                <AlertTriangle class="w-8 h-8" />
              </div>
              <div class="space-y-2">
                <h2 class="font-black uppercase tracking-widest">Suppression Définitive</h2>
                <p class="text-[12px] uppercase leading-relaxed font-bold">
                  Supprimer le dossier de
                  <span class="font-black">{{ itemToDelete?.nom }}</span> ?<br>
                  Toutes les parcelles liées seront supprimées.
                </p>
              </div>
            </div>
            <div class="p-6 bg-muted/10 border-t border-border flex gap-3">
              <button @click="showDeleteModal = false"
                class="flex-1 h-11 text-[12px] font-black uppercase border border-border rounded-xl hover:bg-muted">
                Annuler
              </button>
              <button @click="executeDelete" :disabled="deletingId !== null"
                class="flex-1 h-11 text-[12px] font-black uppercase bg-red-600 text-white rounded-xl
                       shadow-lg shadow-red-600/20 disabled:opacity-60 disabled:cursor-not-allowed
                       flex items-center justify-center gap-2">
                <svg v-if="deletingId !== null" class="animate-spin w-4 h-4"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span>{{ deletingId !== null ? 'Suppression...' : 'Supprimer' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
</style>