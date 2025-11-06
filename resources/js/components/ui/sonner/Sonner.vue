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
        toast: 'group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border group-[.toaster]:border-border group-[.toaster]:shadow-lg group-[.toaster]:rounded-lg group-[.toaster]:px-4 group-[.toaster]:py-3 group-[.toaster]:min-w-[356px] group-[.toaster]:max-w-[420px] group-[.toaster]:transition-all group-[.toaster]:duration-300 group-[.toaster]:data-[swipe=move]:transition-none group-[.toaster]:data-[swipe=move]:translate-x-[var(--radix-toast-swipe-move-x)] group-[.toaster]:data-[swipe=cancel]:translate-x-0 group-[.toaster]:data-[swipe=end]:translate-x-[var(--radix-toast-swipe-end-x)] group-[.toaster]:data-[swipe=end]:animate-out group-[.toaster]:data-[state=open]:animate-in group-[.toaster]:data-[state=closed]:animate-out group-[.toaster]:data-[state=closed]:fade-out-80 group-[.toaster]:data-[state=closed]:slide-out-to-right-full group-[.toaster]:data-[state=open]:slide-in-from-top-full group-[.toaster]:data-[state=open]:sm:slide-in-from-bottom-full',
        description: 'group-[.toast]:text-muted-foreground group-[.toast]:text-sm group-[.toast]:opacity-90 group-[.toast]:mt-1.5 group-[.toast]:leading-relaxed',
        actionButton:
          'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground group-[.toast]:rounded-md group-[.toast]:px-3 group-[.toast]:py-1.5 group-[.toast]:text-sm group-[.toast]:font-medium group-[.toast]:ring-offset-background group-[.toast]:transition-colors group-[.toast]:focus:outline-none group-[.toast]:focus:ring-2 group-[.toast]:focus:ring-ring group-[.toast]:focus:ring-offset-2 group-[.toast]:disabled:pointer-events-none group-[.toast]:disabled:opacity-50 group-[.toast]:hover:bg-primary/90',
        cancelButton:
          'group-[.toast]:bg-secondary group-[.toast]:text-secondary-foreground group-[.toast]:rounded-md group-[.toast]:px-3 group-[.toast]:py-1.5 group-[.toast]:text-sm group-[.toast]:font-medium group-[.toast]:ring-offset-background group-[.toast]:transition-colors group-[.toast]:focus:outline-none group-[.toast]:focus:ring-2 group-[.toast]:focus:ring-ring group-[.toast]:focus:ring-offset-2 group-[.toast]:disabled:pointer-events-none group-[.toast]:disabled:opacity-50 group-[.toast]:hover:bg-secondary/80',
        title: 'group-[.toast]:text-sm group-[.toast]:font-semibold group-[.toast]:leading-none group-[.toast]:tracking-tight',
        closeButton: 'group-[.toast]:absolute group-[.toast]:right-2 group-[.toast]:top-2 group-[.toast]:rounded-md group-[.toast]:p-1 group-[.toast]:text-foreground/50 group-[.toast]:opacity-0 group-[.toast]:transition-opacity group-[.toast]:hover:text-foreground group-[.toast]:focus:opacity-100 group-[.toast]:focus:outline-none group-[.toast]:focus:ring-2 group-[.toast]:group-hover:opacity-100 group-[.toast]:group-[.toast]:opacity-100 group-[.toast]:group-[.toast]:text-muted-foreground group-[.toast]:group-[.toast]:hover:text-foreground',
      },
    }"
    v-bind="props"
  />
</template>
