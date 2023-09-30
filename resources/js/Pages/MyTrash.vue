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
            v-model="selectedFileIds"
            :display-deleted-at="true"
            :display-last-modified="false"
            :display-owner="false"
            :display-path="true"
            :fetch-files="filesFetching"
            :files="filesList"
            :select-all-files-symbol="SELECTED_ALL_FILES_SYMBOL"
            class="w-full overflow-auto"
            @can-load="emitter.emit(EVENT_LOAD_FILES_NEXT_PAGE)"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { computed, ref, watchEffect } from "vue";
import { EVENT_LOAD_FILES_NEXT_PAGE, useDoLoadFiles } from "@/composable/fetchNextPage.js";
import { emitter } from "@/event-bus.js";
import RestoreFiles from "@/Components/RestoreFiles.vue";
import DeleteFromTrash from "@/Components/DeleteFromTrash.vue";

const SELECTED_ALL_FILES_SYMBOL = 'all';

const props = defineProps({
    files: Object,
});

const selectedFileIds = ref([]);

const { filesFetching, filesList, filesTotal, filesReset } = useDoLoadFiles(props.files);

const selectAllFiles = computed(() => selectedFileIds.value.indexOf(SELECTED_ALL_FILES_SYMBOL) >= 0);

watchEffect(() => {
    if (selectedFileIds.value.length > 1
        && selectedFileIds.value.indexOf(SELECTED_ALL_FILES_SYMBOL) >= 0) {
        selectedFileIds.value = [ SELECTED_ALL_FILES_SYMBOL ];
    }
});

const reset = () => {
    filesReset(props.files);
    selectedFileIds.value = [];
};
</script>
