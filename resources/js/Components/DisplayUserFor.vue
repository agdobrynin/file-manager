<template>
    <Modal :show="show" :closeable="true" @close="$emit('close-modal')">
        <div class="p-5">
            <h2 class="mb-5 text-xl font-semibold text-indigo-700">{{ title }}</h2>
            <div class="flex gap-2.5">
                <div v-for="({name}, index) in users" :key="`for-user-${index}`">
                    <div class="p-2 rounded-md bg-gray-300">{{ name }}</div>
                </div>
            </div>
            <div class="mt-5 pt-5 flex justify-end">
                <PrimaryButton @click.prevent="$emit('close-modal')">Close</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

/**
 * @typedef {Array<{ name: String }>} users
 */
defineProps({
    title: {
        type: String,
        default: 'List of users'
    },
    show: {
        type: Boolean,
        required: true,
        default: false,
    },
    users: Object,
});

defineEmits(['close-modal']);
</script>
