<template>
    <SecondaryButton :disabled="isDisable || inProcess"
                     @click="download"
    >
        <SvgIcon
            :class="{'animate-ping' : inProcess}"
            :path="mdiFileDownload"
            class="mr-2 h-5 w-5 text-gray-600"
            type="mdi"/>
        <div>Download</div>
    </SecondaryButton>
</template>

<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { errorMessage } from "@/event-bus.js";
import { mdiFileDownload } from "@mdi/js";
import axios from "axios";
import { parse } from "content-disposition-attachment";
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
    url: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'get',
        validator(value) {
            return [ 'get', 'post' ].includes(value)
        }
    }
});

const inProcess = ref(false);
const link = ref();

const isDisable = computed(() => ! Object.keys(props.params).length);

const emits = defineEmits([ 'downloadComplete' ])

const download = async () => {
    inProcess.value = true;

    try {
        const response = await axios[props.method](
            props.url,
            {
                params: props.params,
                responseType: 'blob',
            });

        const { filename = 'file.zip' } = parse(response.headers['content-disposition'])

        const data = window.URL.createObjectURL(new Blob([ response.data ]));

        const link = document.createElement('a');
        link.download = filename;
        link.href = data;
        link.click();

        link.remove();
        emits('downloadComplete')
    } catch (reason) {
        const text = await reason?.response?.data?.text()
        let responseErrors;

        try {
            const { errors = {}, message } = JSON.parse(text);

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
    }

    inProcess.value = false;
};

defineExpose({
    download,
});
</script>
