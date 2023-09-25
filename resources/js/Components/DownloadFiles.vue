<template>
    <SecondaryButton :disabled="isDisable || inProcess" @click="download">
        <SvgIcon :path="mdiFileDownload" class="mr-2 h-5 w-5 text-gray-600" type="mdi"/>
        <div>Download</div>
    </SecondaryButton>
</template>

<script setup>
import { mdiFileDownload } from "@mdi/js";
import SvgIcon from "vue3-icon";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { errorMessage } from "@/event-bus.js";

const props = defineProps({
    parentFolder: Number,
    fileIds: Array,
    allFiles: Boolean,
});

const inProcess = ref(false);

const form = useForm({
    ids: [],
    all: false,
});

const isDisable = computed(() => {
    return (!props.fileIds.length && !props.allFiles);
});

const emits = defineEmits(['downloadStart'])

const download = () => {
    form.all = props.allFiles;

    if (form.all) {
        form.ids = [];
    } else {
        form.ids = props.fileIds;
    }

    form.get(route('file.download', { parentFolder: props.parentFolder }), {
        onStart: () => {
            inProcess.value = true;
        },
        onFinish: () => {
            inProcess.value = false;
        },
        onSuccess: () => emits('downloadStart'),
        onError: (errors) => {
            const message = Object.keys(errors).length > 0
                ? Object.values(errors)
                : 'Download: something wrong 😞';

            errorMessage(message);
        }
    });
};
</script>
