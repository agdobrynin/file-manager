<template>
    <SecondaryButton :disabled="isDisable || inProcess" @click="download">
        <SvgIcon
            :path="mdiFileDownload"
            :class="{'animate-ping' : inProcess}"
            class="mr-2 h-5 w-5 text-gray-600"
            type="mdi"/>
        <div>Download</div>
    </SecondaryButton>
</template>

<script setup>
import { mdiFileDownload } from "@mdi/js";
import SvgIcon from "vue3-icon";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { computed, reactive, ref } from "vue";
import axios from "axios";
import { errorMessage } from "@/event-bus.js";
import { parse } from "content-disposition-attachment";

const props = defineProps({
    parentFolder: Number,
    fileIds: Array,
    allFiles: Boolean,
});

const inProcess = ref(false);
const link = ref();

const form = reactive({
    ids: [],
    all: false,
});

const isDisable = computed(() => (!props.fileIds.length && !props.allFiles));

const emits = defineEmits(['downloadComplete'])

const download = () => {
    form.all = props.allFiles;

    if (form.all) {
        form.ids = [];
    } else {
        form.ids = props.fileIds;
    }

    const url = route('file.download', {parentFolder: props.parentFolder, ...form});

    inProcess.value = true;

    axios.get(url, {responseType: 'blob'})
        .then((response) => {
            const {filename = 'file.zip'} = parse(response.headers['content-disposition'])

            const data = window.URL.createObjectURL(new Blob([response.data]));

            const link = document.createElement('a');
            link.download = filename;
            link.href = data;
            link.click();

            link.remove();
            emits('downloadComplete')
        })
        .catch(async (reason) => {
            const text = await reason.response.data.text()
            let responseErrors;

            try {
                const {errors = {}, message} = JSON.parse(text);

                if (Object.keys(errors).length > 0) {
                    responseErrors = Object.values(errors).flat();
                }

                if (message) {
                    if (Array.isArray(responseErrors)) {
                        responseErrors.push(message);
                    } else {
                        responseErrors = message;
                    }
                }
            } catch (e) {
                responseErrors = reason.message;
            }

            errorMessage(responseErrors);
        })
        .finally(() => inProcess.value = false)
};

defineExpose({
    download,
});
</script>
