<template>
  <Modal :show="show">
    <div class="p-6">
      <h2 class="text-2xl mb-2 font-semibold">{{ title }}</h2>
      <p>{{ message }}</p>
      <div class="mt-6 flex justify-end">
        <SecondaryButton @click="$emit('cancel')">
          {{ cancelTitle }}
        </SecondaryButton>
        <DangerButton class="ml-3" @click="$emit('confirm')">
          {{ confirmTitle }}
        </DangerButton>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: "Please Confirm"
  },
  message: {
    type: String,
    required: true,
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

defineEmits(['cancel', 'confirm'])
</script>
