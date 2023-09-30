<template>
    <DangerButton
        :disabled="!ids.length && !all"
        @click="showConfirmDialog = true">
        <SvgIcon
            :path="mdiTrashCanOutline"
            class="mr-2 h-5 w-5"/>
        Delete forever
    </DangerButton>
    <ConfirmationDialog
        :show="showConfirmDialog"
        @cancel="showConfirmDialog = false"
        @confirm="deleteForever">
        <div class="inline-flex items-center text-red-800 gap-4 mt-5">
            <div>
                <SvgIcon :path="mdiAlert" class="h-10 w-10"/>
            </div>
            <div>
                <p class="mb-4">Do you confirm the deletion of files?</p>
                <p class="inline-flex items-center">
                    It will be impossible to recover the files.
                </p>
            </div>
        </div>
    </ConfirmationDialog>
</template>

<script setup>

import { mdiAlert, mdiTrashCanOutline } from "@mdi/js";
import SvgIcon from "vue3-icon";
import DangerButton from "@/Components/DangerButton.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { errorMessage } from "@/event-bus.js";

const props = defineProps({
    ids: {
        type: Array,
        required: true,
    },
    all: {
        type: Boolean,
        required: true,
    }
});

const showConfirmDialog = ref(false);

const emit = defineEmits([ 'success' ]);

const deleteForever = () => {
    showConfirmDialog.value = false;

    router.delete(route('trash.destroy'),
        {
            data: {
                all: props.all ? '1' : '0',
                ids: props.all ? [] : props.ids
            },
            onError: (errors) => {
                const displayErrors = Object.keys(errors).length
                    ? Object.values(errors)
                    : 'Something wrong';
                errorMessage(displayErrors);
            },
            onSuccess: () => emit('success'),
        });
};
</script>
