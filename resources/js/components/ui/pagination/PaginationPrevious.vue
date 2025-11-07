<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { ChevronLeft } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import PaginationLink from './PaginationLink.vue'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const classes = computed(() =>
  cn('gap-1 pl-2 pr-3 sm:pl-3 sm:pr-4', props.class),
)
</script>

<template>
  <PaginationLink
    v-bind="$attrs"
    :class="classes"
    aria-label="Ir para a página anterior"
  >
    <ChevronLeft class="h-4 w-4" />
    <span class="hidden sm:inline">Anterior</span>
    <span class="sr-only sm:hidden">Anterior</span>
  </PaginationLink>
</template>

