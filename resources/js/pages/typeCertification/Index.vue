<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, X, AlertTriangle, ShieldCheck, Layers } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

// -------------------------------------------------------
// Types
// -------------------------------------------------------
interface Societe {
  id: number
  nom: string
}

interface TypeCertification {
  id: number
  nom: string
  societe_id: number | null
  societe_nom: string | null
}

// -------------------------------------------------------
// Props
// -------------------------------------------------------
const props = defineProps<{ 
  certifications: TypeCertification[]; 
  isAdmin: boolean;
  societes: Societe[];
}>();

const breadcrumbs = [{ title: 'Types de Certification', href: '#' }];

// -------------------------------------------------------
// Formulaire Inertia
// -------------------------------------------------------
const form = useForm({
  nom: '',
  societe_id: null as number | null,
});

// -------------------------------------------------------
// État UI
// -------------------------------------------------------
const showModal       = ref(false);
const editId          = ref<number | null>(null);
const deletingId      = ref<number | null>(null);
const showDeleteModal = ref(false);
const itemToDelete    = ref<TypeCertification | null>(null);

// -------------------------------------------------------
// Modal Actions
// -------------------------------------------------------
function openAddModal() {
  editId.value    = null;
  form.nom        = '';
  form.societe_id = null;
  form.clearErrors();
  showModal.value = true;
}

function openEditModal(item: TypeCertification) {
  editId.value    = item.id;
  form.nom        = item.nom;
  form.societe_id = item.societe_id ?? null;
  form.clearErrors();
  showModal.value = true;
}

// -------------------------------------------------------
// Save
// -------------------------------------------------------
function save() {
  if (editId.value) {
    form.put(route('type-certifications.update', editId.value), {
      preserveScroll: true,
      onSuccess: () => { showModal.value = false; },
    });
  } else {
    form.post(route('type-certifications.store'), {
      preserveScroll: true,
      onSuccess: () => { showModal.value = false; },
    });
  }
}

// -------------------------------------------------------
// Delete
// -------------------------------------------------------
function confirmDelete(item: TypeCertification) {
  itemToDelete.value = item;
  showDeleteModal.value = true;
}

function executeDelete() {
  if (!itemToDelete.value) return;
  deletingId.value = itemToDelete.value.id;
  useForm({}).delete(route('type-certifications.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false; itemToDelete.value = null; },
    onFinish:  () => { deletingId.value = null; },
  });
}
</script>

<template>
  <Head title="Types de Certification" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6 w-full max-w-none">

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-black text-foreground uppercase tracking-tighter">Types de Certification</h1>
          <p class="text-[12px] text-foreground tracking-[0.2em] font-bold">
            {{ props.certifications.length }} Entité(s)
          </p>
        </div>

        <button
          @click="openAddModal"
          class="h-10 px-6 bg-[var(--brand-green)] text-white text-[12px] font-black tracking-widest rounded-xl
                 flex items-center gap-2 shadow-lg shadow-[var(--brand-green)]/20 active:scale-95 transition-all uppercase">
          <Plus class="w-4 h-4" /> Nouveau Type
        </button>
      </div>

      <div class="bg-card border border-border rounded-2xl overflow-hidden shadow-sm shadow-black/5">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-border bg-[var(--brand-green)] text-white text-center">
                <th class="p-5 text-[12px] font-black uppercase text-left">Nom de la Certification</th>
                <th v-if="props.isAdmin" class="p-5 text-[12px] font-black uppercase text-center w-[250px]">Société</th>
                <th class="p-5 text-[12px] font-black uppercase text-right w-[150px]">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="(item, idx) in props.certifications" :key="item.id"
                :class="idx % 2 === 0 ? 'bg-white dark:bg-zinc-900' : 'bg-zinc-50 dark:bg-zinc-800/60'"
                class="hover:bg-[var(--brand-green)]/5 transition-colors group align-middle">

                <td class="p-5 border-r border-border font-black text-foreground uppercase tracking-tight">
                  <div class="flex items-center gap-2">
                    <ShieldCheck class="w-4 h-4 text-[var(--brand-orange)]" />
                    {{ item.nom }}
                  </div>
                </td>

                <td v-if="props.isAdmin" class="p-5 border-r border-border text-center">
                  <span v-if="item.societe_nom" class="text-[10px] font-black text-blue-800 uppercase bg-blue-100 border border-blue-200
                               px-2 py-1 rounded inline-flex items-center gap-1">
                    <Layers class="w-3 h-3" /> {{ item.societe_nom }}
                  </span>
                  <span v-else class="text-[10px] italic opacity-50 uppercase">—</span>
                </td>

                <td class="p-5 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button @click="openEditModal(item)" :disabled="form.processing"
                      class="p-2.5 hover:bg-muted rounded-lg hover:text-[var(--brand-green)] transition-all">
                      <Pencil class="w-4 h-4" />
                    </button>
                    <button @click="confirmDelete(item)" :disabled="deletingId === item.id"
                      class="p-2.5 hover:bg-red-50 rounded-lg hover:text-red-500 transition-all">
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

              <tr v-if="props.certifications.length === 0">
                <td :colspan="props.isAdmin ? 3 : 2" class="p-12 text-center text-[12px] font-black uppercase opacity-30 tracking-widest">
                  Aucun type de certification trouvé
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <Teleport to="body">
        <div v-if="showModal"
          class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div class="bg-card border border-border rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden" @click.stop>

            <div class="p-6 border-b border-border flex justify-between items-center bg-[var(--brand-green)]">
              <h2 class="text-[18px] font-black uppercase tracking-widest text-white flex items-center gap-2">
                <ShieldCheck class="w-4 h-4" />
                {{ editId ? 'Modifier le Type' : 'Nouveau Type de Certification' }}
              </h2>
              <button @click="showModal = false" class="text-white hover:opacity-70 transition-opacity">
                <X class="w-6 h-6" />
              </button>
            </div>

            <div class="p-8 space-y-6">
              
              <div v-if="props.isAdmin" class="flex flex-col gap-1.5 p-4 bg-[var(--brand-green)]/10 rounded-xl border border-[var(--brand-green)]/30">
                <label class="text-[13px] font-black uppercase text-[var(--brand-green)]">Société de Rattachement</label>
                <select v-model="form.societe_id" class="input-line bg-white dark:bg-zinc-900"
                  :class="{ 'border-red-500': form.errors.societe_id }">
                  <option :value="null" disabled>-- Sélectionner une société --</option>
                  <option v-for="soc in props.societes" :key="soc.id" :value="soc.id">
                    {{ soc.nom }}
                  </option>
                </select>
                <p v-if="form.errors.societe_id" class="text-[11px] text-red-500 font-bold">{{ form.errors.societe_id }}</p>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-[12px] font-black">NOM DE LA CERTIFICATION</label>
                <input v-model="form.nom" type="text" class="input-line"
                  :class="{ 'border-red-500': form.errors.nom }" placeholder="Ex: BIO, Fairtrade..." />
                <p v-if="form.errors.nom" class="text-[11px] text-red-500 font-bold">{{ form.errors.nom }}</p>
              </div>

            </div>

            <div class="p-6 bg-muted/20 border-t border-border flex gap-4">
              <button @click="showModal = false"
                class="flex-1 h-12 text-[14px] font-black border border-border rounded-xl
                       hover:bg-muted text-foreground tracking-widest transition-all">
                Annuler
              </button>
              <button @click="save" :disabled="form.processing"
                class="flex-1 h-12 text-[14px] font-black bg-[var(--brand-orange)] text-black rounded-xl
                       shadow-xl shadow-[var(--brand-orange)]/20 tracking-widest transition-all active:scale-95
                       disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <svg v-if="form.processing" class="animate-spin w-4 h-4"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span>{{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>

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
                  Supprimer le type de certification<br>
                  <span class="font-black text-[var(--brand-orange)]">{{ itemToDelete?.nom }}</span> ?
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

