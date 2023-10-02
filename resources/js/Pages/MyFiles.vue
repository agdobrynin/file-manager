<template>
    <Head title="My files"/>

    <AuthenticatedLayout class="relative">
        <div class="mb-4">
            <div class="overflow-x-auto flex">
                <NavMyFolders :ancestors="ancestors.data || []" @openFolder="fileItemAction"/>
            </div>
        </div>
        <div class="mb-4 border p-2 rounded-md z-10 flex flex-wrap justify-between items-center gap-4">
            <div class="flex flex-wrap gap-4 items-center">
                <CreateNewDropdown/>
                <DeleteFiles
                    :all-files="selectAll"
                    :file-ids="selectedFileIds"
                    :parent-folder="parentId"
                    @delete-finish="deleteFinish"/>
                <DownloadFiles
                    ref="downloadComponent"
                    :all-files="selectAll"
                    :file-ids="selectedFileIds"
                    :parent-folder="parentId"
                    @download-complete="downloadComplete"/>
                <OnlyFavorites
                    v-model="onlyFavoritesCurrentState"
                    @update:model-value="doChangeSearchFavorites"
                />
            </div>
            <div class="border rounded-md p-2 bg-gray-100">Total items: {{ filesTotal }}</div>
        </div>
        <FilesTable
            ref="tableEl"
            v-model:select-all="selectAll"
            v-model:selected-files="selectedFileIds"
            :display-last-modified="true"
            :display-owner="true"
            :fetch-files="filesFetching"
            :files="filesList"
            class="w-full overflow-auto"
            @item-double-click="fileItemAction"
            @can-load="emitter.emit(EVENT_LOAD_FILES_NEXT_PAGE)"
            @item-favorite-click="favoriteAction"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import NavMyFolders from "@/Components/NavMyFolders.vue";
import CreateNewDropdown from "@/Components/CreateNewDropdown.vue";
import { nextTick, onMounted, ref } from "vue";
import DeleteFiles from "@/Components/DeleteFiles.vue";
import DownloadFiles from "@/Components/DownloadFiles.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { emitter, errorMessage, FILES_UPLOADED_SUCCESS, FOLDER_CREATE_SUCCESS } from "@/event-bus.js";
import { EVENT_LOAD_FILES_NEXT_PAGE, useDoLoadFiles } from "@/composable/fetchNextPage.js";
import OnlyFavorites from "@/Components/OnlyFavorites.vue";

/**
 * Build params for function route('file.index').
 *
 * @typedef {{
 *      parentFolder: number,
 *      onlyFavorites?: boolean,
 * }} requestParams
 */

const props = defineProps({
    parentId: Number,
    files: Object,
    ancestors: Object,
});

const onlyFavoritesQueryStringKey = 'onlyFavorites';

const selectedFileIds = ref([]);
const selectAll = ref(false);
const downloadComponent = ref(null);
const tableEl = ref(null);
const onlyFavoritesCurrentState = ref(false);

/**
 * @param {number} parentFolderId
 * @return {requestParams}
 */
const indexRequestParams = (parentFolderId = props.parentId) => {
    const params = { parentFolder: parentFolderId };

    if (onlyFavoritesCurrentState.value) {
        params[onlyFavoritesQueryStringKey] = 1;
    }

    return params;
};

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
        router.visit(route('file.index', indexRequestParams(item.id)));
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
            id: item.id
        },
        {
            only: [ 'errors', 'flash' ],
            onSuccess: () => {
                item.isFavorite = ! item.isFavorite;
            },
            onError: (errors) => {
                const message = Object.keys(errors).length
                    ? Object.values(errors).flat()
                    : 'Something wrong 🧨';
                errorMessage(message);
            },
        });
};

const doChangeSearchFavorites = () => router.get(route('file.index', indexRequestParams()));

onMounted(() => {
    emitter.on(FILES_UPLOADED_SUCCESS, () => updateAllFiles());
    emitter.on(FOLDER_CREATE_SUCCESS, () => updateAllFiles());

    const urlParams = new URLSearchParams(window.location.search);
    onlyFavoritesCurrentState.value = urlParams.get(onlyFavoritesQueryStringKey) === '1';
});
</script>
