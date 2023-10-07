<template>
    <SecondaryButton
        :disabled="!ids.length && !all || inProcess"
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
import { ref } from "vue";
import SvgIcon from "vue3-icon";

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

const emit = defineEmits([ 'success' ]);

const inProcess = ref(false);

const doUnshareFiles = () => {
    router.delete(route('share_by_me.unshare'), {
        data: {
            all: Number(props.all),
            ids: props.all ? [] : props.ids
        },
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
