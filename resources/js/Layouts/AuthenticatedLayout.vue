<template>
  <div class="h-screen bg-gray-50 flex w-full gap-4">
    <Navigation/>
    <main class="flex flex-col flex-1 overflow-hidden"
          @drop.prevent="handleDrop"
          @dragover.prevent="onDragOver"
          @dragleave.prevent="onDragLeave">
      <template v-if="!over">
        <div class="flex items-center justify-between w-full z-20 px-2">
          <SearchForm/>
          <UserSettingsDropdown/>
        </div>
        <div class="flex-1 flex flex-col overflow-hidden px-1.5">
          <slot/>
        </div>
      </template>
      <template v-else>
        <div class="flex h-full w-full border-4 border-gray-500 border-dashed items-center">
          <div class="text-center w-full text-2xl text-gray-500 uppercase">
            Drop files here to upload
          </div>
        </div>
      </template>
    </main>
    <UploadProgress
        v-if="progress"
        :percent="progress"
        :total-files="fileUploadForm.files.length"
        class="absolute right-0 p-4 bg-white border border-gray-300 rounded-md shadow-2xl m-4 text-center"
    />
    <Notify class="absolute right-0 top-0 pt-2 pe-4 z-30"/>
  </div>
</template>

<script setup>
import Navigation from "@/Components/Navigation.vue";
import UserSettingsDropdown from "@/Components/UserSettingsDropdown.vue";
import SearchForm from "@/Components/SearchForm.vue";
import {
    emitter,
    errorMessage,
    FILES_CHOOSE,
    FILES_UPLOADED_FAILED,
    FILES_UPLOADED_SUCCESS,
    infoMessage,
    successMessage,
    warningMessage
} from "@/event-bus.js";
import { computed, onMounted, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { fromEvent } from "file-selector";
import UploadProgress from "@/Components/UploadProgress.vue";
import { bytesToSize } from "@/helpers/helper.js";
import Notify from "@/Components/Notify.vue";


const page = usePage();
const over = ref(false);

const fileUploadForm = useForm({
  files: [],
  relativePaths: [],
})

const progress = computed(() => fileUploadForm.progress?.percentage || 0)

const onDragOver = () => over.value = true;

const onDragLeave = () => over.value = false;

/**
 *
 * @param {DragEvent} e
 */
const handleDrop = async (e) => {
  over.value = false;
  uploadFiles(await fromEvent(e));
};

/**
 * @param {FileList|File[]} uploadFiles
 */
const uploadFiles = (uploadFiles) => {
  if (uploadFiles.length) {
    const { maxUploadFiles, maxPostBytes } = page.props.upload;
    const files = Array.from(uploadFiles);

    const totalBytes = files.reduce((acc, file) => acc + file.size, 0);

    if (maxPostBytes < totalBytes) {
      errorMessage(`Too large upload ${bytesToSize(totalBytes)}. Max available ${bytesToSize(maxPostBytes)}.`);

      return;
    }

    if (maxUploadFiles < files.length) {
      errorMessage(`Available maximum upload ${maxUploadFiles} files`);

      return;
    }


    fileUploadForm.files = [...files];
    fileUploadForm.relativePaths = [...files].map((file) => file.path?.replace(/^\//, '') || file.webkitRelativePath || file.name);

    fileUploadForm.post(route('file.upload', { parentFolder: page.props.parentId || null }), {
      onSuccess: () => {
        successMessage(
            `Upload ${fileUploadForm.files.length} file${fileUploadForm.files.length > 1? 's' : ''}`
        );
        emitter.emit(FILES_UPLOADED_SUCCESS);
      },
      onError: errors => {
        /**
         * @type {string|string[]}
         */
        const message = Object.keys(errors).length > 0
            ? Object.values(errors)
            : 'Error during file upload. Please try again later.';

        errorMessage(message);
        emitter.emit(FILES_UPLOADED_FAILED);
        page.props.errors = null;
      },
      onFinish: () => {
        fileUploadForm.clearErrors()
        fileUploadForm.reset();
      }
    })
  }
};

onMounted(() => {
  emitter.on(FILES_CHOOSE, uploadFiles);
    const { info, success, error, warning } = page.props.flash;

  if (info) {
    infoMessage(info);
  }

  if (success) {
    successMessage(success);
  }

  if (error) {
    errorMessage(error, 5000);
  }

    if (warning) {
        warningMessage(warning);
    }
});
</script>
