<template>
    <Modal :show="show">
        <div class="p-6">
            <h2 class="mb-2 font-semibold">{{ title }}</h2>
            <slot/>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="$emit('cancel')">
                    {{ cancelTitle }}
                </SecondaryButton>
                <PrimaryButton class="ml-3" @click="$emit('confirm')">
                    {{ confirmTitle }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: "Please Confirm"
    },
    cancelTitle: {
        type: String,
        default: "Cancel",
    },
    confirmTitle: {
        type: String,
        default: "Confirm",
    },
});

defineEmits([ 'cancel', 'confirm' ])
</script>
