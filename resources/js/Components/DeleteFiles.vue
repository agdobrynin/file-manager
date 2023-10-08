<template>
    <div>
        <SecondaryButton :disabled="isDisabled || isProgress" @click="showConfirmDelete = true">
            <SvgIcon
                :class="{'animate-ping' : isProgress}"
                :path="mdiTrashCanOutline"
                class="mr-2 h-5 w-5 text-gray-600" type="mdi"/>
            <div>Delete</div>
        </SecondaryButton>
        <ConfirmationDialog
            :show="showConfirmDelete"
            @cancel="showConfirmDelete = false"
            @close="showConfirmDelete = false"
            @confirm="doDelete"
        >
            <div class="inline-flex items-center text-red-800 gap-4">
                <div>
                    <SvgIcon :path="mdiAlert" class="h-10 w-10"/>
                </div>
                <div>
                    <p>Move selected files to trash?</p>
                </div>
            </div>
        </ConfirmationDialog>
    </div>
</template>

<script setup>
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { errorMessage, successMessage } from "@/event-bus.js";
import { router, usePage } from "@inertiajs/vue3";
import { mdiAlert, mdiTrashCanOutline } from "@mdi/js";
import { computed, ref } from "vue";
import SvgIcon from "vue3-icon";

/**
 * @property {{
 *     params: {
 *         all?: Boolean,
 *         ids?: Number[],
 *     }
 * }} params
 */
const props = defineProps({
    parentFolder: Number,
    params: {
        type: Object,
        required: true,
    },
});

const isProgress = ref(false);
const showConfirmDelete = ref(false);

const page = usePage();

const emits = defineEmits([ 'deleteFinish' ]);

const isDisabled = computed(() => ! Object.keys(props.params).length);

const doDelete = () => {
    showConfirmDelete.value = false;

    if ( ! props.params.all && ! props.params.ids.length) {
        errorMessage('Please select files for deleting.');
        return;
    }

    router.delete(route('file.destroy', { parentFolder: props.parentFolder || null }), {
        data: props.params,
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
