<template>
    <div class="bg-white shadow sm:rounded-lg" ref="topEl">
        <table class="table table-fixed min-w-full">
            <thead class="bg-gray-100 border-b">
            <tr>
                <th class="px-3">
                    <div class="block w-[30px] text-right">#</div>
                </th>
                <th>
                    <div class="block w-[50px]"></div>
                    <Checkbox
                        v-model="value"
                        :checked="value"
                        :disabled="!files.length"
                        :value="selectAllFilesSymbol"/>
                </th>
                <th v-if="displayFavorite">
                    <div class="block w-[30px]"></div>
                </th>
                <th class="my-files-table-head">Name</th>
                <th v-if="displayOwner" class="my-files-table-head">Owner</th>
                <th v-if="displayPath" class="my-files-table-head">Path</th>
                <th v-if="displayDeletedAt" class="my-files-table-head">Deleted</th>
                <th v-if="displayLastModified" class="my-files-table-head whitespace-nowrap">Last modified</th>
                <th class="my-files-table-head">Size</th>
                <th class="my-files-table-head">Disk</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(item, index) of files"
                :key="item.id"
                :class="[all || value.includes(item.id) ? '!bg-amber-100 hover:!bg-amber-200': '']"
                class="cursor-pointer my-files-table-row"
                @click.stop="clickItem(item)"
                @dblclick.prevent="$emit('itemDoubleClick', item)"
            >
                <td class="px-3 font-light text-sm text-slate-400 text-right">
                    {{ index + 1 }}
                </td>
                <td class="text-center">
                    <Checkbox
                        v-model="value"
                        :checked="!!all || value"
                        :disabled="all"
                        :value="item.id"/>
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
                <td class="ps-5">
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
import { bytesToSize } from "@/helpers/helper.js";
import SvgIcon from "vue3-icon";
import FileIcon from "@/Components/FileIcon.vue";
import Checkbox from "@/Components/Checkbox.vue";
import { mdiCloudOutline, mdiHarddisk, mdiStar, mdiStarOutline } from "@mdi/js";
import { computed, onMounted, onUpdated, ref } from "vue";

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
 */
/**
 * @var {Prettify<Readonly<ExtractPropTypes<{
 *      modelValue: ArrayConstructor|file[],
 *      fetchFile: boolean,
 *      selectAllFilesSymbol: string,
 *      modelValue: string[]|number[]
 *  }>>>} props
 */
const props = defineProps({
    modelValue: Array,
    files: Array,
    fetchFiles: Boolean,
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
    selectAllFilesSymbol: {
        type: String,
        default: '*',
        validator(value) {
            return value.length > 1;
        }
    }
});

const emit = defineEmits([
    'update:modelValue',
    'itemDoubleClick',
    'canLoad',
    'itemFavoriteClick'
]);

const endOfFilesList = ref(null);
const topEl = ref(null);

/**
 * @var {WritableComputedRef<string[]|number[]>} value
 */
const value = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const all = computed(() => !!value.value.find((v) => v === props.selectAllFilesSymbol));

const diskIcon = (item) => item.disk === 'cloud' ? mdiCloudOutline : mdiHarddisk;

const clickItem = (item) => {
    if (!all.value) {
        const index = value.value.indexOf(item.id);

        if (index >= 0) {
            value.value = value.value.filter((id) => id !== item.id);
        } else {
            value.value.push(item.id);
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

const scrollTop = () => topEl.value?.scrollIntoView({ behavior: 'smooth' });

onUpdated(() => {
    observer.observe(endOfFilesList.value);
})

onMounted(() => {
    observer.observe(endOfFilesList.value);
});

defineExpose({
    scrollTop,
})
</script>
