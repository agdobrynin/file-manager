<template>
    <Head title="My files"/>

    <AuthenticatedLayout class="relative">
        <div class="mb-4 border p-2 rounded-md z-10 flex flex-wrap justify-between items-center gap-4">
            <div class="flex flex-wrap gap-4">
                <DeleteFromTrash :all="selectAllFiles" :ids="selectedFileIds" @success="reset"/>
                <RestoreFiles :all="selectAllFiles" :ids="selectedFileIds" @success="reset"/>
            </div>
            <div class="border rounded-md p-2 bg-gray-100">Total items: {{ filesTotal }}</div>
        </div>
        <FilesTable
            v-model:select-all="selectAllFiles"
            v-model:selected-files="selectedFileIds"
            :disable-select-all="disableSelectAll"
            :display-deleted-at="true"
            :display-favorite="false"
            :display-last-modified="false"
            :display-owner="false"
            :display-path="true"
            :fetch-files="filesFetching"
            :files="filesList"
            class="w-full overflow-auto"
            @can-load="emitter.emit(EVENT_LOAD_FILES_NEXT_PAGE)"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { onMounted, onUnmounted, ref, watch } from "vue";
import { EVENT_LOAD_FILES_NEXT_PAGE, useDoLoadFiles } from "@/composable/fetchNextPage.js";
import { DO_SEARCH_FILE, emitter } from "@/event-bus.js";
import RestoreFiles from "@/Components/RestoreFiles.vue";
import DeleteFromTrash from "@/Components/DeleteFromTrash.vue";

const props = defineProps({
    files: Object,
});

const selectedFileIds = ref([]);
const selectAllFiles = ref(false);
const searchString = ref('');
const disableSelectAll = ref(false);

watch(searchString, (value) => {
    disableSelectAll.value = !! value;
    selectAllFiles.value = false;
    selectedFileIds.value = [];
});

const { filesFetching, filesList, filesTotal, filesReset } = useDoLoadFiles(props.files);

const doSearch = () => {
    const params = { search: searchString.value };

    router.visit(route('trash.index', params), {
        replace: true,
        preserveState: true,
        onSuccess: () => filesReset(props.files),
    });
};

const reset = () => {
    filesReset(props.files);
    selectedFileIds.value = [];
    selectAllFiles.value = false;
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
