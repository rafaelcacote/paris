<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority'
import type { HTMLAttributes } from 'vue'
import { computed, useAttrs } from 'vue'
import { Primitive, type PrimitiveProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const paginationLinkVariants = cva(
  'inline-flex items-center justify-center whitespace-nowrap rounded-md border border-input bg-background text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground',
  {
    variants: {
      size: {
        default: 'h-9 w-9 px-3',
        sm: 'h-8 w-8 px-2 text-xs',
        lg: 'h-10 w-10 px-4 text-base',
      },
      active: {
        true: 'bg-accent text-accent-foreground',
      },
    },
    defaultVariants: {
      size: 'default',
    },
  },
)

type PaginationLinkVariants = VariantProps<typeof paginationLinkVariants>

interface Props extends PrimitiveProps {
  class?: HTMLAttributes['class']
  size?: PaginationLinkVariants['size']
  active?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
  size: 'default',
  active: false,
})

const attrs = useAttrs()

const classes = computed(() =>
  cn(
    paginationLinkVariants({
      size: props.size,
      active: props.active ? 'true' : undefined,
    }),
    props.class,
  ),
)
</script>

<template>
  <Primitive
    data-slot="pagination-link"
    :as="as"
    :as-child="asChild"
    v-bind="attrs"
    :class="classes"
  >
    <slot />
  </Primitive>
</template>

