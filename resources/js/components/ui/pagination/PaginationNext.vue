<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { ChevronRight } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import PaginationLink from './PaginationLink.vue'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const classes = computed(() =>
  cn('gap-1 pl-3 pr-2 sm:pl-4 sm:pr-3', props.class),
)
</script>

<template>
  <PaginationLink
    v-bind="$attrs"
    :class="classes"
    aria-label="Ir para a próxima página"
  >
    <span class="hidden sm:inline">Próximo</span>
    <span class="sr-only sm:hidden">Próximo</span>
    <ChevronRight class="h-4 w-4" />
  </PaginationLink>
</template>

