<script setup>
const props = defineProps({
  title: String,
  enqueteurs: { type: Array, default: () => [] }
});
const agentName = defineModel('agentName');
</script>

<template>
  <div class="w-full md:w-1/2 text-[var(--brand-orange)]">
    <div class="bg-[var(--card)] rounded-xl shadow p-5 font-bold flex items-center uppercase text-base md:text-lg lg:text-xl xl:text-2xl transition-all">
      {{ title }}
    </div>

    <!-- <div class="col-span-2 bg-[var(--card)] rounded-xl shadow p-5 border border-[var(--sidebar-border)] flex flex-col gap-2">
      <label class="text-xs uppercase font-semibold tracking-wider">Nom de l'agent Traça</label>

      <select v-model="agentName" class="input-line">
        <option value="" disabled>Sélectionner un enquêteur...</option>
        <option 
          v-for="user in enqueteurs" 
          :key="user.id" 
          :value="user.id"
        >
          {{ user.nom }} {{ user.prenom }}
        </option>
      </select>
    </div> -->
  </div>
</template>