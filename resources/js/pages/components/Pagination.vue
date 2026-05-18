<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  total: number
  currentPage: number
  itemsPerPage: number
}>()

const emit = defineEmits<{
  'update:currentPage': [value: number]
  'update:itemsPerPage': [value: number]
}>()

const totalPages = computed(() => Math.ceil(props.total / props.itemsPerPage))

const visiblePages = computed(() => {
  const pages: (number | '...')[] = []
  for (let i = 1; i <= totalPages.value; i++) {
    if (
      i === 1 ||
      i === totalPages.value ||
      (i >= props.currentPage - 1 && i <= props.currentPage + 1)
    ) {
      pages.push(i)
    } else if (i === props.currentPage - 2 || i === props.currentPage + 2) {
      pages.push('...')
    }
  }
  return pages
})
</script>

<template>
  <div class="flex items-center justify-between px-5 py-4 border-t border-border bg-muted/10">

    <!-- Lignes par page + total -->
    <div class="flex items-center gap-3">
      <span class="text-[12px] font-bold text-foreground opacity-60 uppercase tracking-widest">Lignes par page</span>
      <select
        :value="itemsPerPage"
        @change="emit('update:itemsPerPage', Number(($event.target as HTMLSelectElement).value)); emit('update:currentPage', 1)"
        class="h-8 px-3 text-[12px] font-black bg-card border border-border rounded-lg outline-none focus:border-[var(--brand-green)] text-foreground cursor-pointer"
      >
        <option :value="10">10</option>
        <option :value="15">15</option>
        <option :value="25">25</option>
      </select>
      <span class="text-[12px] font-bold opacity-40 uppercase tracking-widest">
        {{ total }} résultat(s)
      </span>
    </div>

    <!-- Navigation -->
    <div class="flex items-center gap-1">
      <button
        @click="emit('update:currentPage', 1)"
        :disabled="currentPage === 1"
        class="h-8 w-8 flex items-center justify-center rounded-lg border border-border text-[12px] font-black hover:bg-muted disabled:opacity-20 disabled:cursor-not-allowed transition-all"
      >«</button>

      <button
        @click="emit('update:currentPage', currentPage - 1)"
        :disabled="currentPage === 1"
        class="h-8 w-8 flex items-center justify-center rounded-lg border border-border text-[12px] font-black hover:bg-muted disabled:opacity-20 disabled:cursor-not-allowed transition-all"
      >‹</button>

      <template v-for="(page, idx) in visiblePages" :key="idx">
        <button
          v-if="page !== '...'"
          @click="emit('update:currentPage', page as number)"
          :class="currentPage === page
            ? 'bg-[var(--brand-green)] text-white border-[var(--brand-green)]'
            : 'hover:bg-muted border-border'"
          class="h-8 w-8 flex items-center justify-center rounded-lg border text-[12px] font-black transition-all"
        >{{ page }}</button>
        <span v-else class="text-[12px] font-black opacity-30 px-1">…</span>
      </template>

      <button
        @click="emit('update:currentPage', currentPage + 1)"
        :disabled="currentPage === totalPages || totalPages === 0"
        class="h-8 w-8 flex items-center justify-center rounded-lg border border-border text-[12px] font-black hover:bg-muted disabled:opacity-20 disabled:cursor-not-allowed transition-all"
      >›</button>

      <button
        @click="emit('update:currentPage', totalPages)"
        :disabled="currentPage === totalPages || totalPages === 0"
        class="h-8 w-8 flex items-center justify-center rounded-lg border border-border text-[12px] font-black hover:bg-muted disabled:opacity-20 disabled:cursor-not-allowed transition-all"
      >»</button>
    </div>

  </div>
</template>