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
                    :all-files="selectAllFiles"
                    :file-ids="selectedFileIds"
                    :parent-folder="parentId"
                    @delete-finish="deleteFinish"
                />
                <DownloadFiles
                    ref="downloadComponent"
                    :all-files="selectAllFiles"
                    :file-ids="selectedFileIds"
                    :parent-folder="parentId"
                    @download-complete="clearSelected"
                />
                <ShareFiles
                    :all-files="selectAllFiles"
                    :file-ids="selectedFileIds"
                    :parent-folder="parentId"
                    @success="clearSelected"
                />
                <OnlyFavorites
                    v-model="onlyFavoritesCurrentState"
                    @update:model-value="doChangeSearchFavorites"
                />
            </div>
            <div class="border rounded-md p-2 bg-gray-100">Total items: {{ filesTotal }}</div>
        </div>
        <FilesTable
            ref="tableEl"
            v-model:select-all="selectAllFiles"
            v-model:selected-files="selectedFileIds"
            :disable-select-all="disableSelectAll"
            :display-last-modified="true"
            :display-owner="false"
            :display-path="!!searchString"
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
import ShareFiles from "@/Components/ShareFiles.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import NavMyFolders from "@/Components/NavMyFolders.vue";
import CreateNewDropdown from "@/Components/CreateNewDropdown.vue";
import { nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import DeleteFiles from "@/Components/DeleteFiles.vue";
import DownloadFiles from "@/Components/DownloadFiles.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { DO_SEARCH_FILE, emitter, errorMessage, FILES_UPLOADED_SUCCESS, FOLDER_CREATE_SUCCESS } from "@/event-bus.js";
import { EVENT_LOAD_FILES_NEXT_PAGE, useDoLoadFiles } from "@/composable/fetchNextPage.js";
import OnlyFavorites from "@/Components/OnlyFavorites.vue";

/**
 * Build params for function route('file.index').
 *
 * @typedef {{
 *      parentFolder: number,
 *      search?: string,
 *      onlyFavorites?: boolean,
 * }} requestParams
 */

const props = defineProps({
    parentId: Number,
    files: Object,
    ancestors: Object,
});

const onlyFavoritesQueryStringKey = 'onlyFavorites';
const searchQueryStringKey = 'search';
const parentFolderRouteKey = 'parentFolder';

const selectedFileIds = ref([]);
const disableSelectAll = ref(false);
const selectAllFiles = ref(false);
const downloadComponent = ref(null);
const tableEl = ref(null);
const onlyFavoritesCurrentState = ref(false);
const searchString = ref('');

watch(searchString, (value) => {
    disableSelectAll.value = !! value;
    clearSelected();
});

/**
 * @param {Number|null} parentFolderId
 * @return {requestParams}
 */
const indexRequestParams = (parentFolderId) => {
    const params = {};

    if (parentFolderId) {
        params[parentFolderRouteKey] = parentFolderId;
    }

    if ( !! searchString.value) {
        params[searchQueryStringKey] = searchString.value;
    }

    if (onlyFavoritesCurrentState.value) {
        params[onlyFavoritesQueryStringKey] = 1;
    }

    return params;
};

const { filesFetching, filesList, filesTotal, filesReset } = useDoLoadFiles();

const updateAllFiles = () => {
    filesReset(props.files);
    nextTick(() => tableEl.value?.scrollFilesTableTop());
};

const clearSelected = () => {
    selectedFileIds.value = [];
    selectAllFiles.value = false;
}

const deleteFinish = () => {
    clearSelected();
    updateAllFiles();
};

const fileItemAction = (item) => {
    if (item.isFolder) {
        searchString.value = '';
        onlyFavoritesCurrentState.value = false;

        router.visit(route('file.index', indexRequestParams(item.id)));
    } else {
        clearSelected();
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

                if (onlyFavoritesCurrentState.value && ! item.isFavorite) {
                    filesList.value = filesList.value.filter((file) => file.id !== item.id);
                    filesTotal.value--;
                }
            },
            onError: (errors) => {
                const message = Object.keys(errors).length
                    ? Object.values(errors).flat()
                    : 'Something wrong 🧨';
                errorMessage(message);
            },
        });
};

const doRequestWithFilters = (params) => {
    router.visit(route('file.index', params), {
        replace: true,
        preserveState: true,
        onSuccess: () => updateAllFiles(),
    });
};

const doSearch = () => doRequestWithFilters(indexRequestParams(null));

const doChangeSearchFavorites = () => {
    const parentId = !! searchString.value ? null : props.parentId;
    const params = indexRequestParams(parentId);

    doRequestWithFilters(params);
};

onMounted(() => {
    emitter.on(FILES_UPLOADED_SUCCESS, () => updateAllFiles());
    emitter.on(FOLDER_CREATE_SUCCESS, () => updateAllFiles());

    emitter.on(DO_SEARCH_FILE, (search) => {
        searchString.value = search;
        doSearch();
    });

    const urlParams = new URLSearchParams(window.location.search);
    onlyFavoritesCurrentState.value = urlParams.get(onlyFavoritesQueryStringKey) === '1';
});

onUnmounted(() => {
    emitter.off(DO_SEARCH_FILE);
});
</script>
