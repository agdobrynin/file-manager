<template>
    <SecondaryButton :disabled="!fileIds.length && !allFiles">
        <SvgIcon :path="mdiFileDownload" class="mr-2 h-5 w-5 text-gray-600" type="mdi"/>
        <div>Download</div>
    </SecondaryButton>
</template>

<script setup>
import { mdiFileDownload } from "@mdi/js";
import SvgIcon from "vue3-icon";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    parentFolder: Number,
    fileIds: Array,
    allFiles: Boolean,
});
</script>
