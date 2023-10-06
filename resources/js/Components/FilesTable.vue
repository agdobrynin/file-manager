<template>
    <div class="bg-white shadow sm:rounded-lg">
        <table id="files-table-main-table" ref="topEl" class="table table-fixed min-w-full">
            <thead class="bg-gray-100 border-b">
            <tr>
                <th class="px-3 w-[30px]">
                    #
                </th>
                <th class="px-3 w-[40px]">
                    <Checkbox
                        v-model="selectAllValue"
                        :checked="selectAllValue"
                        :disabled="!files.length || disableSelectAll"
                        name="select_all_files"
                    />
                </th>
                <th v-if="displayFavorite" class="w-[40px]">&nbsp;</th>
                <th class="my-files-table-head">Name</th>
                <th v-if="displayOwner" class="my-files-table-head">Owner</th>
                <th v-if="displayForShortUser" class="my-files-table-head">For user</th>
                <th v-if="displayPath" class="my-files-table-head">Path</th>
                <th v-if="displayDeletedAt" class="my-files-table-head">Deleted</th>
                <th v-if="displayLastModified" class="my-files-table-head whitespace-nowrap">Last modified</th>
                <th class="my-files-table-head">Size</th>
                <th v-if="displayDisk" class="my-files-table-head">Disk</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item, index) of files"
                :key="item.id"
                :class="[selectAllValue || selectedFilesValue.includes(item.id) ? '!bg-amber-100 hover:!bg-amber-200': '']"
                class="cursor-pointer my-files-table-row"
                @click.stop="clickItem(item)"
                @dblclick.prevent="$emit('itemDoubleClick', item)"
            >
                <td class="ps-2 font-light text-sm text-slate-400 text-right">
                    {{ index + 1 }}
                </td>
                <td class="text-center">
                    <Checkbox
                        v-model="selectedFilesValue"
                        :checked="!!selectAllValue || selectedFilesValue"
                        :disabled="selectAllValue"
                        :value="item.id"
                        name="file_id[]"
                    />
                </td>
                <td v-if="displayFavorite"
                    class="text-yellow-500 text-center"
                    @click.prevent.stop="$emit('itemFavoriteClick', item)">
                    <SvgIcon v-if="item.isFavorite" :path="mdiStar" class="mx-auto w-7 h-7 hover:animate-pulse"/>
                    <SvgIcon v-else :path="mdiStarOutline" class="mx-auto w-7 h-7 hover:animate-pulse"/>
                </td>
                <td class="my-files-table-cell flex items-center gap-2 ps-2">
                    <div>
                        <FileIcon :mime-type="item.mime" size="30"/>
                    </div>
                    <div>{{ item.name }}</div>
                </td>
                <td v-if="displayOwner" class="my-files-table-cell">
                    {{ item.owner }}
                </td>
                <td v-if="displayForShortUser" class="my-files-table-cell whitespace-normal">
                    <div class="grid gap-2.5 min-w-[100px] max-w-[150px]">
                        <div v-for="(user, index) in item.shareForUser"
                             :key="`user_for_${index}`"
                             class="font-extralight truncate hover:overflow-visible"
                        >
                            {{ user.name }}
                        </div>
                    </div>
                </td>
                <td v-if="displayPath" class="my-files-table-cell">
                    {{ item.path }}
                </td>
                <td v-if="displayDeletedAt" class="my-files-table-cell">
                    {{ item.deletedAt }}
                </td>
                <td v-if="displayLastModified" class="my-files-table-cell">
                    {{ item.updatedAt }}
                </td>
                <td class="my-files-table-cell">
                    {{ item.size ? bytesToSize(item.size) : '' }}
                </td>
                <td v-if="displayDisk" class="ps-5">
                    <SvgIcon v-if="!item.isFolder" :path="diskIcon(item)"/>
                </td>
            </tr>
            </tbody>
        </table>
        <div v-if="fetchFiles">
            <div class="ms-16 p-4 flex items-center gap-2 text-indigo-600">
                <div>
                    <FileIcon class="animate-ping" mime-type="text" size="30"/>
                </div>
                <div>Please wait. Loading files...</div>
            </div>
        </div>
        <div v-if="files.length === 0" class="py-8 text-center text-sm text-gray-400">
            There is no data in this folder
        </div>
        <div ref="endOfFilesList"></div>
    </div>
</template>

<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import FileIcon from "@/Components/FileIcon.vue";
import { bytesToSize } from "@/helpers/helper.js";
import { mdiCloudOutline, mdiHarddisk, mdiStar, mdiStarOutline } from "@mdi/js";
import { computed, onMounted, onUpdated, ref } from "vue";
import SvgIcon from "vue3-icon";

/**
 * @typedef {{ name: String }} shortUser
 * */

/**
 * @typedef file
 * @type {Object}
 * @property {number} id
 * @property {string} createdAt
 * @property {number} createdBy
 * @property {string} disk
 * @property {boolean} isFolder
 * @property {boolean} isFavorite
 * @property {string} mime Mime type
 * @property {string} name File name
 * @property {string} owner Name if owner
 * @property {number} parentId Parent file
 * @property {string} path Full path
 * @property {number} size Bytes of file
 * @property {string} updatedAt
 * @property {string} updatedBy
 * @property {string|null} deletedAt
 * @property {shortUser[]} shareForUser
 */
const props = defineProps({
    // model for list of selected items
    selectedFiles: Array,
    // model for top checkbox select all
    selectAll: Boolean,

    files: Array,
    fetchFiles: Boolean,
    disableSelectAll: {
        type: Boolean,
        default: false,
    },
    displayFavorite: {
        type: Boolean,
        default: true,
    },
    displayOwner: {
        type: Boolean,
        default: true,
    },
    displayLastModified: {
        type: Boolean,
        default: true,
    },
    displayDeletedAt: {
        type: Boolean,
        default: false,
    },
    displayPath: {
        type: Boolean,
        default: false,
    },
    displayDisk: {
        type: Boolean,
        default: true,
    },
    displayForShortUser: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits([
    'itemDoubleClick',
    'canLoad',
    'itemFavoriteClick',
    'update:selectedFiles',
    'update:selectAll',
]);

const endOfFilesList = ref(null);
const topEl = ref(null);

const selectedFilesValue = computed({
    get: () => props.selectedFiles,
    set: (value) => emit('update:selectedFiles', value),
});

const selectAllValue = computed({
    get: () => props.selectAll,
    set: (val) => {
        if (val) {
            selectedFilesValue.value = [];
        }

        emit('update:selectAll', val);
    }
});

const diskIcon = (item) => item.disk === 'cloud' ? mdiCloudOutline : mdiHarddisk;

const clickItem = (item) => {
    if ( ! selectAllValue.value) {
        const index = selectedFilesValue.value.indexOf(item.id);

        if (index >= 0) {
            selectedFilesValue.value = selectedFilesValue.value.filter((id) => id !== item.id);
        } else {
            selectedFilesValue.value.push(item.id);
        }
    }
};

const observer = new IntersectionObserver(
    async (entries) => {
        entries.forEach(entry => entry.isIntersecting && emit('canLoad'))
    },
    {
        rootMargin: '-250px 0px 0px 0px'
    }
)

const scrollFilesTableTop = () => topEl.value?.scrollIntoView({ behavior: 'smooth' });

onUpdated(() => {
    observer.observe(endOfFilesList.value);
})

onMounted(() => {
    observer.observe(endOfFilesList.value);
});

defineExpose({
    scrollFilesTableTop,
})
</script>
