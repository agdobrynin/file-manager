<template>
    <SecondaryButton
        :disabled="isDisabled"
        class="flex items-center gap-2"
        @click.prevent="doUnshareFiles"
    >
        <SvgIcon :class="{ 'animate-ping' : inProcess }" :path="mdiShareOffOutline"/>
        Unshare
    </SecondaryButton>
</template>

<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { errorMessage } from "@/event-bus.js";
import { router } from "@inertiajs/vue3";
import { mdiShareOffOutline } from "@mdi/js";
import { computed, ref } from "vue";
import SvgIcon from "vue3-icon";

/**
 * @property {{
 *     all?: Boolean,
 *     ids?: Number[],
 * }} params
 */
const props = defineProps({
    params: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits([ 'success' ]);

const inProcess = ref(false);

const isDisabled = computed(() => ! Object.keys(props.params).length);

const doUnshareFiles = () => {
    router.delete(route('share_by_me.unshare'), {
        data: props.params,
        onSuccess: () => emit('success'),
        onError: (errors) => {
            const displayErrors = Object.keys(errors).length
                ? Object.values(errors)
                : 'Something wrong';
            errorMessage(displayErrors);
        },
    })
};
</script>
