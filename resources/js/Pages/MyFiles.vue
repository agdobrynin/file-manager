<template>
  <Head title="My files"/>

  <AuthenticatedLayout>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight pb-3">My files</h2>
    <div class="max-w-7xl overflow-auto">
      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        My files at {{ $page.url }}
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>
