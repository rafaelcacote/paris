<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { Toaster as Sonner } from "vue-sonner"

const props = defineProps<ToasterProps>()
</script>

<template>
  <Sonner
    position="top-right"
    rich-colors
    close-button
    :toast-options="{
      duration: 5000,
      classes: {
        toast: 'group toast group-[.toaster]:bg-card group-[.toaster]:text-card-foreground group-[.toaster]:border group-[.toaster]:border-border/50 group-[.toaster]:shadow-xl group-[.toaster]:shadow-black/5 group-[.toaster]:rounded-xl group-[.toaster]:px-5 group-[.toaster]:py-4 group-[.toaster]:min-w-[356px] group-[.toaster]:max-w-[480px] group-[.toaster]:backdrop-blur-sm group-[.toaster]:transition-all group-[.toaster]:duration-300',
        description: 'group-[.toast]:text-muted-foreground group-[.toast]:text-sm group-[.toast]:mt-2 group-[.toast]:leading-relaxed',
        actionButton:
          'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground group-[.toast]:rounded-lg group-[.toast]:px-4 group-[.toast]:py-2 group-[.toast]:text-sm group-[.toast]:font-medium group-[.toast]:hover:opacity-90 group-[.toast]:transition-opacity group-[.toast]:duration-200',
        cancelButton:
          'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground group-[.toast]:rounded-lg group-[.toast]:px-4 group-[.toast]:py-2 group-[.toast]:text-sm group-[.toast]:font-medium group-[.toast]:hover:opacity-90 group-[.toast]:transition-opacity group-[.toast]:duration-200',
        title: 'group-[.toast]:text-base group-[.toast]:font-semibold group-[.toast]:leading-tight group-[.toast]:mb-1',
        closeButton: 'group-[.toast]:text-muted-foreground group-[.toast]:hover:text-foreground group-[.toast]:opacity-60 group-[.toast]:hover:opacity-100 group-[.toast]:transition-opacity group-[.toast]:duration-200 group-[.toast]:rounded-md group-[.toast]:hover:bg-muted/50 group-[.toast]:p-1',
      },
    }"
    v-bind="props"
  />
</template>
