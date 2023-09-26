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
import { computed, reactive, ref } from "vue";
import axios from "axios";
import { errorMessage } from "@/event-bus.js";

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

  const url = route('file.download', { parentFolder: props.parentFolder, ...form });

  inProcess.value = true;

  axios.get(url, { responseType: "blob" })
      .then((response) => {
        const fileName = response.headers['content-disposition']?.match(/filename=(.+)/)[1] || 'file.zip';
        const data = window.URL.createObjectURL(new Blob([response.data]));

        const link = document.createElement('a');
        link.download = fileName;
        link.href = data;
        link.click();

        link.remove();
        emits('downloadComplete')
      })
      .catch(async (reason) => {
        const text = await reason.response.data.text()
        let message = reason.message;

        try {
          const { errors = {} } = JSON.parse(text);

          message = Object.keys(errors).length > 0
              ? Object.values(errors).flat()
              : reason.message;
        } catch (e) {
        }

        errorMessage(message);
      })
      .finally(() => inProcess.value = false)
};
</script>
