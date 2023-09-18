<template>
  <div>
    <SecondaryButton :disabled="!fileIds.length && !allFiles" @click="showConfirmDelete = true">
      <SvgIcon :path="mdiTrashCanOutline" class="mr-2 h-5 w-5 text-gray-600" type="mdi"/>
      <div>Delete</div>
    </SecondaryButton>
    <ConfirmationDialog
        :show="showConfirmDelete" message="Delete selected files?"
        @cancel="showConfirmDelete = false"
        @confirm="doDelete"
    />
  </div>
</template>

<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SvgIcon from "vue3-icon";
import { mdiTrashCanOutline } from "@mdi/js";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { ref } from "vue";
import { errorMessage, successMessage } from "@/event-bus.js";

const props = defineProps({
  parentFolder: Number,
  fileIds: Array,
  allFiles: Boolean,
});

const emit = defineEmits(['deleteFinish']);

const page = usePage();

const form = useForm({
  fileIds: [],
  allFiles: null,
});

const showConfirmDelete = ref(false);

const doDelete = () => {
  showConfirmDelete.value = false;
  form.fileIds = !props.allFiles ? props.fileIds : [];
  form.allFiles = props.allFiles;

  if (!props.fileIds.length && !props.allFiles) {
    errorMessage('Please select files for deleting.');
    return;
  }

  form.delete(route('file.destroy', { parentFolder: props.parentFolder || null }), {
    onSuccess: () => successMessage('Selected files have been deleted.'),
    onError: errors => {
      const message = Object.keys(errors).length > 0
          ? Object.values(errors)
          : 'Delete file: something wrong 😞';

      errorMessage(message);
    },
    onFinish: () => emit('deleteFinish'),
  })
};
</script>
