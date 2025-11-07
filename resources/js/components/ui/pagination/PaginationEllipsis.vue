<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { MoreHorizontal } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const classes = computed(() =>
  cn('flex h-9 w-9 items-center justify-center text-sm text-muted-foreground', props.class),
)
</script>

<template>
  <span :class="classes">
    <span class="sr-only">Mais páginas</span>
    <MoreHorizontal class="h-4 w-4" />
  </span>
</template>

