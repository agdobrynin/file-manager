<template>
    <Head title="My files"/>

    <AuthenticatedLayout class="relative">
        <h1 class="text-3xl text-indigo-700">My trashed files here</h1>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
</script>
