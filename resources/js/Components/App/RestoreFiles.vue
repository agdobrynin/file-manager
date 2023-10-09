<template>
    <SecondaryButton
        :disabled="idDisabled"
        class="!text-white !bg-green-600 hover:bg-green-500 border-none"
        @click="restore"
    >
        <SvgIcon
            :path="mdiRestoreAlert"
            class="mr-2 h-5 w-5"/>
        Restore files
    </SecondaryButton>
</template>

<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { errorMessage } from "@/event-bus.js";
import { router } from "@inertiajs/vue3";
import { mdiRestoreAlert } from "@mdi/js";
import { computed } from "vue";
import SvgIcon from "vue3-icon";

/**
 * @property {{
 *     all?: Boolean
 *     ids?: Number[]
 * }} params
 */
const props = defineProps({
    params: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits([ 'success' ]);

const idDisabled = computed(() => ! Object.keys(props.params).length);

const restore = () => {
    const url = route('trash.restore');

    router.visit(url,
        {
            method: 'post',
            data: props.params,
            onSuccess: () => {
                emit('success');
            },
            onError: (errors) => {
                const displayErrors = Object.keys(errors).length
                    ? Object.values(errors)
                    : 'Something wrong';
                errorMessage(displayErrors);
            },
        });
}
</script>
