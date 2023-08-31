<template>
    <Head title="My files"/>

    <AuthenticatedLayout>
        <div class="overflow-y-auto">
            <NavFolder :ancestors="ancestors.data || []" @openFolder="openFolder" class="whitespace-nowrap"/>
        </div>
        <div class="max-w-7xl overflow-auto">
            <div class="bg-white shadow sm:rounded-lg">
                <table class="min-w-full">
                    <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="my-files-table-head">Name</th>
                        <th class="my-files-table-head">Owner</th>
                        <th class="my-files-table-head">Last modified</th>
                        <th class="my-files-table-head">Size</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item of files.data"
                        :key="item.id"
                        :class="[item.isFolder ? 'cursor-pointer' : '', 'my-files-table-row']"
                        @click="openFolder(item)"
                    >
                        <td class="my-files-table-cell">
                            {{ item.name }}
                        </td>
                        <td class="my-files-table-cell">
                            {{ item.owner }}
                        </td>
                        <td class="my-files-table-cell">
                            {{ item.updatedAt }}
                        </td>
                        <td class="my-files-table-cell">
                            {{ item.size }}
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div v-if="!files.data.length" class="py-8 text-center text-sm text-gray-400">
                    There is no data in this folder
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import NavFolder from "@/Components/NavFolder.vue";


defineProps({
    parentId: {
        type: [Number, null],
        default: null,
    },
    files: {
        type: Object,
        default: {},
    },
    ancestors: {
        type: Object,
        default: {}
    }
})

const openFolder = (item) => {
    if (item.isFolder) {
        router.visit(route('my.files', { parentFolder: item.id }))
    }
};
</script>
