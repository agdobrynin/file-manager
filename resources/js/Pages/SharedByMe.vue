<template>
    <Head title="Shared by me"/>

    <AuthenticatedLayout class="relative">
        <div class="mb-4 border p-2 rounded-md z-10 flex flex-wrap justify-between items-center gap-4">
            <div class="flex flex-wrap gap-4">
                <UnsharedFiles
                    :all="selectAllFiles"
                    :ids="selectedFileIds"
                    @success="reset"/>
                <DownloadFiles
                    ref="downloadComponent"
                    :params="paramsAllAndIds"
                    :url="route('shared_by_me.download')"
                    @download-complete="clearSelectedFiles"
                />
            </div>
            <div class="border rounded-md p-2 bg-gray-100">Total items: {{ filesTotal }}</div>
        </div>
        <FilesTable
            v-model:select-all="selectAllFiles"
            v-model:selected-files="selectedFileIds"
            :disable-select-all="disableSelectAll"
            :display-deleted-at="false"
            :display-favorite="false"
            :display-last-modified="true"
            :display-owner="false"
            :display-path="true"
            :display-share-for-user="true"
            :fetch-files="filesFetching"
            :files="filesList"
            class="w-full overflow-auto"
            @can-load="emitter.emit(EVENT_LOAD_FILES_NEXT_PAGE)"
            @item-double-click="downloadFile"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import DownloadFiles from "@/Components/DownloadFiles.vue";
import UnsharedFiles from "@/Components/UnshareFiles.vue";
import { useSelectFiles } from "@/composable/selectFIles.js";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { onMounted, onUnmounted, ref, watch } from "vue";
import { EVENT_LOAD_FILES_NEXT_PAGE, useDoLoadFiles } from "@/composable/fetchNextPage.js";
import { DO_SEARCH_FILE, emitter } from "@/event-bus.js";

const props = defineProps({
    files: Object,
});

const searchString = ref('');
const disableSelectAll = ref(false);
const downloadComponent = ref(null);

watch(searchString, (value) => {
    disableSelectAll.value = !! value;
    clearSelectedFiles();
});

const { filesFetching, filesList, filesTotal, filesReset } = useDoLoadFiles();
const { selectAllFiles, selectedFileIds, paramsAllAndIds, clearSelectedFiles } = useSelectFiles()

const doSearch = () => {
    const params = { search: searchString.value };

    router.visit(route('shared_by_me.index', params), {
        replace: true,
        preserveState: true,
        onSuccess: () => filesReset(props.files),
    });
};

const reset = () => {
    filesReset(props.files);
    clearSelectedFiles();
};

const downloadFile = (item) => {
    clearSelectedFiles();
    (new Promise((resolve) => resolve()))
        .then(() => selectedFileIds.value.push(item.id))
        .then(() => downloadComponent.value.download());
};

onMounted(() => {
    emitter.on(DO_SEARCH_FILE, (search) => {
        searchString.value = search;
        doSearch();
    });
});

onUnmounted(() => {
    emitter.off(DO_SEARCH_FILE);
});
</script>
