<script setup>
// On définit les props pour le titre personnalisé
const props = defineProps({
  title: String
});

// Utilisation de defineModel pour une liaison bidirectionnelle propre
const agentName = defineModel('agentName');
const ficheNumber = defineModel('ficheNumber');
</script>

<template>
  <div class="grid grid-cols-5 gap-4 mb-6 text-[var(--foreground)]">
    <div class="col-span-2 bg-[var(--card)] rounded-xl shadow p-5 font-bold flex items-center uppercase">
      {{ title }}
    </div>
    
    <div class="col-span-2 bg-[var(--card)] rounded-xl shadow p-5 border border-[var(--sidebar-border)] flex flex-col gap-2">
      <label class="text-xs uppercase font-semibold tracking-wider">Nom de l'agent Traça</label>
      <input 
        v-model="agentName" 
        type="text" 
        placeholder="Nom complet..." 
        class="bg-transparent border-b border-[var(--sidebar-border)] focus:border-[var(--brand-green)] outline-none pb-1 text-sm"
      >
    </div>

    <div class="col-span-1 bg-[var(--card-alt)] rounded-xl shadow p-5 border border-[var(--sidebar-border)] flex flex-col gap-2">
      <label class="text-xs uppercase font-semibold text-center tracking-wider">Fiche N°</label>
      <input 
        v-model="ficheNumber" 
        type="number" 
        placeholder="000" 
        class="bg-transparent border-b border-[var(--sidebar-border)] focus:border-[var(--brand-green)] outline-none pb-1 text-sm text-center"
      >
    </div>
  </div>
</template>