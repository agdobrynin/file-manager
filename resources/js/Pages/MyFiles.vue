<template>
    <Head title="My files"/>

    <AuthenticatedLayout class="relative">
        <div class="mb-4">
            <div class="overflow-x-auto flex">
                <NavMyFolders :ancestors="ancestors.data || []" @openFolder="fileItemAction"/>
            </div>
        </div>
        <div class="mb-4 border p-2 rounded-md z-10 flex flex-wrap justify-between items-center gap-4">
            <div class="flex flex-wrap gap-4">
                <CreateNewDropdown/>
                <DeleteFiles
                    :all-files="selectedAllFiles"
                    :file-ids="selectedFileIds"
                    :parent-folder="parentId"
                    @delete-finish="deleteFinish"/>
                <DownloadFiles
                    ref="downloadComponent"
                    :all-files="selectedAllFiles"
                    :file-ids="selectedFileIds"
                    :parent-folder="parentId"
                    @download-complete="downloadComplete"/>
            </div>
            <div class="border rounded-md p-2 bg-gray-100">Total items: {{ filesTotal }}</div>
        </div>
        <FilesTable
            ref="tableEl"
            v-model="selectedFileIds"
            :display-last-modified="true"
            :display-owner="true"
            :fetch-files="filesFetching"
            :files="filesList"
            :select-all-files-symbol="SELECTED_ALL_FILES_SYMBOL"
            class="w-full overflow-auto"
            @item-double-click="fileItemAction"
            @can-load="emitter.emit(EVENT_LOAD_FILES_NEXT_PAGE)"
            @item-favorite-click="favoriteAction"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import NavMyFolders from "@/Components/NavMyFolders.vue";
import CreateNewDropdown from "@/Components/CreateNewDropdown.vue";
import { computed, nextTick, onMounted, ref, watchEffect } from "vue";
import DeleteFiles from "@/Components/DeleteFiles.vue";
import DownloadFiles from "@/Components/DownloadFiles.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { emitter, errorMessage, FILES_UPLOADED_SUCCESS, FOLDER_CREATE_SUCCESS } from "@/event-bus.js";
import { EVENT_LOAD_FILES_NEXT_PAGE, useDoLoadFiles } from "@/composable/fetchNextPage.js";

const SELECTED_ALL_FILES_SYMBOL = 'all';

const props = defineProps({
    parentId: Number,
    files: Object,
    ancestors: Object,
})

const selectedFileIds = ref([]);
const downloadComponent = ref(null);
const tableEl = ref(null);

const selectedAllFiles = computed(() => selectedFileIds.value.indexOf(SELECTED_ALL_FILES_SYMBOL) >= 0);

watchEffect(() => {
    if (selectedFileIds.value.length > 1
        && selectedFileIds.value.indexOf(SELECTED_ALL_FILES_SYMBOL) >= 0) {
        selectedFileIds.value = [ SELECTED_ALL_FILES_SYMBOL ];
    }
})

const { filesFetching, filesList, filesTotal, filesReset } = useDoLoadFiles(props.files);

const updateAllFiles = () => {
    filesReset(props.files);
    nextTick(() => tableEl.value?.scrollTop());
};

const downloadComplete = () => selectedFileIds.value = [];

const deleteFinish = () => {
    selectedFileIds.value = [];
    updateAllFiles();
};

const fileItemAction = (item) => {
    if (item.isFolder) {
        router.visit(route('file.index', { parentFolder: item.id }));
    } else {
        selectedFileIds.value = [];
        nextTick(() => {
            selectedFileIds.value.push(item.id);
            downloadComponent.value.download();
        });
    }
};

const favoriteAction = (item) => {
    router.patch(route('file.favorite'),
        {
            ids: [ item.id ]
        },
        {
            only: [ 'errors' ],
            onSuccess: () => {
                item.isFavorite = !item.isFavorite;
            },
            onError: (errors) => {
                const message = Object.keys(errors).length
                    ? Object.values(errors).flat()
                    : 'Something wrong 🧨';
                errorMessage(message);
            },
        });
}

onMounted(() => {
    emitter.on(FILES_UPLOADED_SUCCESS, () => updateAllFiles());
    emitter.on(FOLDER_CREATE_SUCCESS, () => updateAllFiles());
});
</script>
