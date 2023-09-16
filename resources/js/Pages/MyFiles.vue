<template>
  <Head title="My files"/>

  <AuthenticatedLayout class="relative">
    <div class="mb-4">
      <div class="overflow-x-auto flex">
        <NavMyFolders :ancestors="ancestors.data || []" @openFolder="fileItemAction"/>
      </div>
    </div>
    <div class="mb-4 border p-2 rounded-md z-10 flex justify-between items-center">
      <CreateNewDropdown/>
      <div class="border rounded-md p-2 font-light bg-gray-100">Total items: {{ allFiles.total }}</div>
    </div>
    <div class="w-full overflow-auto">
      <div class="bg-white shadow sm:rounded-lg">
        <table class="min-w-full">
          <thead class="bg-gray-100 border-b">
          <tr>
            <th class="px-3 text-left">#</th>
            <th class="my-files-table-head">Name</th>
            <th class="my-files-table-head">Owner</th>
            <th class="my-files-table-head">Last modified</th>
            <th class="my-files-table-head">Size</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, index) of allFiles.files"
              :key="item.id"
              class="cursor-pointer my-files-table-row"
              @click="fileItemAction(item)"
          >
            <td class="px-3 font-light text-sm text-slate-400">
              {{ index + 1 }}
            </td>
            <td class="my-files-table-cell flex items-center gap-2">
              <div>
                <FileIcon :mime-type="item.mime" size="30"/>
              </div>
              <div>{{ item.name }}</div>
            </td>
            <td class="my-files-table-cell">
              {{ item.owner }}
            </td>
            <td class="my-files-table-cell">
              {{ item.updatedAt }}
            </td>
            <td class="my-files-table-cell">
              {{ item.size }}
            </td>
          </tr>
          <tr v-if="fetchFiles">
            <td></td>
            <td colspan="4">
              <div class="p-4 flex items-center gap-2 animate-pulse text-indigo-600">
                <div><FileIcon mime-type="text" size="30" class="animate-spin"/></div>
                <div>Please wait. Loading files...</div>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
        <div v-if="allFiles.total === 0" class="py-8 text-center text-sm text-gray-400">
          There is no data in this folder
        </div>
      </div>
      <div ref="endOfFilesList"></div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import NavMyFolders from "@/Components/NavMyFolders.vue";
import CreateNewDropdown from "@/Components/CreateNewDropdown.vue";
import FileIcon from "@/Components/FileIcon.vue";
import { emitter, errorMessage, FILES_UPLOADED_SUCCESS, FOLDER_CREATE_SUCCESS } from "@/event-bus.js";
import { onMounted, onUpdated, reactive, ref } from "vue";


const props = defineProps({
  parentId: {
    type: [Number, null],
    default: null,
  },
  files: {
    type: Object,
    default: {},
  },
  ancestors: {
    type: Object,
    default: {}
  }
})

const endOfFilesList = ref(null);
const fetchFiles = ref(false)

const initUrl = usePage().url;

const allFiles = reactive({
  files: props.files?.data || [],
  next: props.files?.links?.next || null,
  total: props.files?.meta?.total || 0,
});

const updateAllFiles = (existList = []) => {
  allFiles.files = [...existList, ...(props.files?.data || [])];
  allFiles.next = props?.files?.links?.next || null;
  allFiles.total = props?.files?.meta?.total || 0;
};

const fileItemAction = (item) => {
  if (item.isFolder) {
    router.visit(route('my.files', { parentFolder: item.id }))
  } else {
    errorMessage('File action not implemented yet')
  }
};

const loadFiles = () => {
  if (allFiles.next) {
    router.visit(allFiles.next, {
      method: 'get',
      preserveState: true,
      preserveScroll: true,
      only: ['files'],
      onStart: () => fetchFiles.value = true,
      onFinish: () => fetchFiles.value = false,
      onSuccess: () => {
        updateAllFiles(allFiles.files)
        window.history.replaceState({}, usePage().url, initUrl);
      },
    })
  }
};

const observer = new IntersectionObserver(
    async (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          loadFiles();
        }
      })
    },
    {
      rootMargin: '-250px 0px 0px 0px'
    }
)

onUpdated(() => {
  observer.observe(endOfFilesList.value);
})

onMounted(() => {
  observer.observe(endOfFilesList.value);
  emitter.on(FILES_UPLOADED_SUCCESS, () => updateAllFiles());
  emitter.on(FOLDER_CREATE_SUCCESS, () => updateAllFiles());
});
</script>
