<template>
    <Head title="My files"/>

    <AuthenticatedLayout class="relative">
        <div class="mb-4 border p-2 rounded-md z-10 flex flex-wrap justify-between items-center gap-4">
            <div class="flex flex-wrap gap-4">
                <DangerButton
                    :disabled="!selectedFileIds.length"
                    @click="showConfirmDelete = true">
                    <SvgIcon
                        :path="mdiTrashCanOutline"
                        class="mr-2 h-5 w-5"/>
                    Delete forever
                </DangerButton>
                <SecondaryButton :disabled="!selectedFileIds.length">
                    <SvgIcon
                        :path="mdiRestoreAlert"
                        class="mr-2 h-5 w-5"/>
                    Restore files
                </SecondaryButton>
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
            @item-double-click="doItemAction"
            @can-load="emitter.emit(EVENT_LOAD_FILES_NEXT_PAGE)"
        />
        <ConfirmationDialog
            :show="showConfirmDelete"
            @confirm="deleteForever"
            @cancel="showConfirmDelete = false">
            <div class="inline-flex items-center text-red-800 gap-4 mt-5">
                <div><SvgIcon :path="mdiAlert" class="h-10 w-10"/></div>
                <div>
                    <p class="mb-4">Do you confirm the deletion of files?</p>
                    <p class="inline-flex items-center">
                        It will be impossible to recover the files.
                    </p>
                </div>
            </div>
        </ConfirmationDialog>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { ref, watchEffect } from "vue";
import DangerButton from "@/Components/DangerButton.vue";
import SvgIcon from "vue3-icon";
import { mdiTrashCanOutline, mdiRestoreAlert, mdiAlert } from "@mdi/js";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import { EVENT_LOAD_FILES_NEXT_PAGE, useDoLoadFiles } from "@/composable/fetchNextPage.js";
import { emitter } from "@/event-bus.js";

const SELECTED_ALL_FILES_SYMBOL = 'all';

const props = defineProps({
    files: Object,
});

const selectedFileIds = ref([]);
const showConfirmDelete = ref(false);

const { filesFetching, filesList, filesTotal } = useDoLoadFiles(props.files);

watchEffect(() => {
    if (selectedFileIds.value.length > 1
        && selectedFileIds.value.indexOf(SELECTED_ALL_FILES_SYMBOL) >= 0) {
        selectedFileIds.value = [ SELECTED_ALL_FILES_SYMBOL ];
    }
})

const doItemAction = (file) => {
    console.log(file);
};

const deleteForever = () => {
    showConfirmDelete.value = false;
    console.log('Delete forever', selectedFileIds.value, selectedFileIds);
}
</script>
