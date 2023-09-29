<template>
    <SecondaryButton
        :disabled="!ids.length && !all"
        @click="restore">
        <SvgIcon
            :path="mdiRestoreAlert"
            class="mr-2 h-5 w-5"/>
        Restore files
    </SecondaryButton>
</template>

<script setup>
import { mdiRestoreAlert } from "@mdi/js";
import SvgIcon from "vue3-icon";
import SecondaryButton from "@/Components/SecondaryButton.vue";
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
})

const restore = () => {
    router.get(route('trash.restore'),
        {
            all: props.all,
            ids: !props.all ? props.ids : [],
        },
        {
            onError: (errors) => {
                const displayErrors = Object.keys(errors).length
                    ? Object.values(errors)
                    : 'Something wrong';
                errorMessage(displayErrors);
            },
        });
}
</script>
