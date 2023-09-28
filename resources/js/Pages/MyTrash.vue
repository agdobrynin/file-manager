<template>
    <Head title="My files"/>

    <AuthenticatedLayout class="relative">
        {{ selectedFileIds }}
        {{ nextPage }}
        {{ totalFiles }}
        <FilesTable
            v-model="selectedFileIds"
            :display-deleted-at="true"
            :display-last-modified="true"
            :display-owner="false"
            :display-path="true"
            :fetch-files="fetchFiles"
            :files="allFiles"
            :select-all-files-symbol="SELECTED_ALL_FILES_SYMBOL"
            class="w-full overflow-auto"
            @item-double-click="doItemAction"
            @can-load="doLoadFiles"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import FilesTable from "@/Components/FilesTable.vue";
import { computed, ref, watchEffect } from "vue";

const SELECTED_ALL_FILES_SYMBOL = 'all';

const props = defineProps({
    files: Object,
});

const selectedFileIds = ref([]);
const fetchFiles = ref(false);
const allFiles = ref(props.files?.data || []);
const initUrl = usePage().url;

const nextPage = computed(() => props.files?.links?.next || null);
const totalFiles = computed(() => props.files?.meta?.total || 0);

watchEffect(() => {
    if (selectedFileIds.value.length > 1
        && selectedFileIds.value.indexOf(SELECTED_ALL_FILES_SYMBOL) >= 0) {
        selectedFileIds.value = [ SELECTED_ALL_FILES_SYMBOL ];
    }
})

const doLoadFiles = () => {
    if (nextPage.value && !fetchFiles.value) {
        router.visit(String(nextPage.value),
            {
                preserveState: true,
                onStart: function () {
                    fetchFiles.value = true;
                },
                onFinish: function () {
                    fetchFiles.value = false;
                },
                onSuccess: ({ props: { files: { data = [] } } }) => {
                    allFiles.value = [ ...allFiles.value, ...data ];
                    window.history.replaceState({}, usePage().url, initUrl);
                },
            })
    }
}


const doItemAction = (file) => {
    console.log(file);
};
</script>
