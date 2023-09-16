<template>
  <div class="h-screen bg-gray-50 flex w-full gap-4">
    <Navigation/>
    <main @drop.prevent="handleDrop"
          @dragover.prevent="onDragOver"
          @dragleave.prevent="onDragLeave"
          class="flex flex-col flex-1 overflow-hidden">
      <template v-if="!over">
        <div class="flex items-center justify-between w-full z-20">
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
        class="absolute right-0 p-4 bg-white border border-gray-300 rounded-md shadow-2xl m-4 text-center"
        :percent="progress"
        :total-files="fileUploadForm.files.length"
    />
    <Notification class="z-30"/>
  </div>
</template>

<script setup>
import Navigation from "@/Components/Navigation.vue";
import UserSettingsDropdown from "@/Components/UserSettingsDropdown.vue";
import SearchForm from "@/Components/SearchForm.vue";
import Notification from "@/Components/Notification.vue";
import {
  emitter,
  errorMessage,
  FILES_CHOOSE,
  FILES_UPLOADED_FAILED,
  FILES_UPLOADED_SUCCESS,
  successMessage
} from "@/event-bus.js";
import { computed, onMounted, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { fromEvent } from "file-selector";
import UploadProgress from "@/Components/UploadProgress.vue";


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
 * @param {FileList|File[]} files
 */
const uploadFiles = (files) => {
  if (files.length) {
    const maxUpload = page.props.upload.maxUploadFiles;

    if (maxUpload < files.length) {
      errorMessage(`Available maximum upload ${maxUpload} files`);

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

onMounted(() => emitter.on(FILES_CHOOSE, uploadFiles));
</script>
