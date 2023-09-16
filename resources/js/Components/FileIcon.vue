<template>
  <SvgIcon type="mdi" :path="icon" :size="size" :title="mimeType"/>
</template>

<script setup>
import SvgIcon from "vue3-icon";
import { computed } from "vue";
import * as mimeTypes from "@/helpers/mimeTypes.js"
import {
  mdiFile,
  mdiFileExcel,
  mdiFileImage,
  mdiFileMusic,
  mdiFilePdfBox,
  mdiFileVideo,
  mdiFileWord,
  mdiFolderOutline,
  mdiTextBox,
  mdiZipBox
} from "@mdi/js"

const props = defineProps({
  mimeType: {
    type: [String, null],
    default: null,
  },
  size: {
    type: [Number, String],
    default: 24,
  },
})

const icon = computed(() => {
  if (null === props.mimeType) {
    return mdiFolderOutline;
  }

  if (mimeTypes.isDoc(props.mimeType)) {
    return mdiFileWord;
  }

  if (mimeTypes.isImage(props.mimeType)) {
    return mdiFileImage;
  }

  if (mimeTypes.isPdf(props.mimeType)) {
    return mdiFilePdfBox;
  }

  if (mimeTypes.isArchive(props.mimeType)) {
    return mdiZipBox;
  }

  if (mimeTypes.isCalc(props.mimeType)) {
    return mdiFileExcel;
  }

  if (mimeTypes.isAudio(props.mimeType)) {
    return mdiFileMusic;
  }

  if (mimeTypes.isText(props.mimeType)) {
    return mdiTextBox;
  }

  if (mimeTypes.isVideo(props.mimeType)) {
    return mdiFileVideo;
  }

  return mdiFile;
});
</script>
