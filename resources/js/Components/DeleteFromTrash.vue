<template>
    <DangerButton
        :disabled="isDisabled"
        @click="showConfirmDialog = true">
        <SvgIcon
            :path="mdiTrashCanOutline"
            class="mr-2 h-5 w-5"/>
        Delete forever
    </DangerButton>
    <ConfirmationDialog
        :show="showConfirmDialog"
        @cancel="showConfirmDialog = false"
        @close="showConfirmDialog = false"
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
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import DangerButton from "@/Components/DangerButton.vue";
import { errorMessage } from "@/event-bus.js";
import { router } from "@inertiajs/vue3";
import { mdiAlert, mdiTrashCanOutline } from "@mdi/js";
import { computed, ref } from "vue";
import SvgIcon from "vue3-icon";

/**
 * @property {{
 *     all?: Boolean,
 *     ids?: Number[]
 * }} params
 */
const props = defineProps({
    params: {
        type: Object,
        required: true,
    },
});

const showConfirmDialog = ref(false);

const emit = defineEmits([ 'success' ]);

const isDisabled = computed(() => ! Object.keys(props.params).length);

const deleteForever = () => {
    showConfirmDialog.value = false;

    router.delete(route('trash.destroy'),
        {
            data: props.params,
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
