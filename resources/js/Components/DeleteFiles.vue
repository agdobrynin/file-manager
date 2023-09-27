<template>
    <div>
        <SecondaryButton :disabled="isDisabled || isProgress" @click="showConfirmDelete = true">
            <SvgIcon
                :path="mdiTrashCanOutline"
                :class="{'animate-ping' : isProgress}"
                class="mr-2 h-5 w-5 text-gray-600" type="mdi"/>
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
import { computed, ref } from "vue";
import { errorMessage, successMessage } from "@/event-bus.js";

const props = defineProps({
    parentFolder: Number,
    fileIds: Array,
    allFiles: Boolean,
});

const isProgress = ref(false);
const showConfirmDelete = ref(false);

const page = usePage();

const emits = defineEmits(['deleteFinish']);

const form = useForm({
    ids: [],
    all: null,
});

const isDisabled = computed(() => !props.fileIds.length && !props.allFiles)

const doDelete = () => {
    showConfirmDelete.value = false;
    form.ids = !props.allFiles ? props.fileIds : [];
    form.all = props.allFiles;

    if (!props.fileIds.length && !props.allFiles) {
        errorMessage('Please select files for deleting.');
        return;
    }

    form.delete(route('file.destroy', {parentFolder: props.parentFolder || null}), {
        onStart: () => {
            isProgress.value = true;
        },
        onSuccess: () => {
            successMessage('Selected files have been deleted.');
        },
        onError: errors => {
            const message = Object.keys(errors).length > 0
                ? Object.values(errors)
                : 'Delete file: something wrong 😞';

            errorMessage(message);
        },
        onFinish: () => {
            isProgress.value = false;
            emits('deleteFinish');
        },
    })
};
</script>
