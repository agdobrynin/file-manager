<template>
    <Menu as="div" class="relative inline-block text-left">
        <div>
            <MenuButton
                class="inline-flex w-full justify-center border rounded-md px-4 py-2 text-sm font-medium text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75"
            >
                Create New
                <ChevronDownIcon
                    class="ml-2 mr-1 h-5 w-5 text-gray-600 hover:text-gray-300"
                    aria-hidden="true"
                />
            </MenuButton>
        </div>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems
                class="absolute left-0 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            >
                <div class="px-1 py-1">
                    <MenuItem v-slot="{ active }" class="flex gap-2.5 items-center">
                        <ResponsiveNavLink
                            :href="route('folder.create')"
                            @click.prevent="createFolderShow = true"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">
                            <FolderPlusIcon class="w-6 h-auto"/>
                            <span>New Folder</span>
                        </ResponsiveNavLink>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" class="flex gap-2.5 items-center">
                        <ResponsiveNavLink
                            href="/"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">
                            <DocumentPlusIcon class="w-6 h-6"/>
                            <span>Upload files</span>
                        </ResponsiveNavLink>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" class="flex gap-2.5 items-center">
                        <ResponsiveNavLink
                            href="/"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">
                            <FolderArrowDownIcon class="w-6 h-auto"/>
                            <span>Upload folder</span>
                        </ResponsiveNavLink>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
    <CreateFolderModal :show="createFolderShow" @close="createFolderShow = false"/>
</template>

<script setup>
import { ChevronDownIcon } from "@heroicons/vue/20/solid/index.js";
import { DocumentPlusIcon, FolderArrowDownIcon, FolderPlusIcon } from "@heroicons/vue/24/outline/index.js";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import CreateFolderModal from "@/Components/CreateFolderModal.vue";
import { ref } from "vue";

const createFolderShow = ref(false);
</script>
