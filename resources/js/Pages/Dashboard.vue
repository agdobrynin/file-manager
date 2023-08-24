<template>
  <Head title="Dashboard"></Head>

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto w-full">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">You're logged in!</div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>
