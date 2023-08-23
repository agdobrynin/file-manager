<template>
    <form class="w-[600px] h-[80px] flex items-center">
        <TextInput
            v-model="form.search"
            type="text"
            class="block w-full mr-2"
            placeholder="Serach for files and filders"
            autocomplete
        />
    </form>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import {useForm} from "@inertiajs/vue3";

const form = useForm({
    search: ''
});
</script>
