<template>
    <div>
        <Menu as="div" class="relative inline-block text-left">
            <div>
                <MenuButton
                    class="inline-flex w-full justify-center border rounded-md ps-4 py-2 uppercase text-sm font-medium text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75">
                    New
                    <SvgIcon
                        :path="mdiChevronDown"
                        class="ml-2 mr-1 h-5 w-5 text-gray-600 hover:text-gray-300"/>
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
                                :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']"
                                href="#"
                                @click="createFolderShow = true">
                                <SvgIcon :path="mdiFolderPlusOutline" class="w-6 h-auto"/>
                                <span>New Folder</span>
                            </ResponsiveNavLink>
                        </MenuItem>
                        <MenuItem v-slot="{ active }" class="flex gap-2.5 items-center">
                            <ResponsiveNavLink
                                :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm relative']"
                                href="#">
                                <input class="w-full absolute opacity-0 cursor-pointer" multiple
                                       type="file"
                                       @change="uploadFiles"/>
                                <SvgIcon :path="mdiFileDocumentPlusOutline" class="w-6 h-6"/>
                                <span>Upload files</span>
                            </ResponsiveNavLink>
                        </MenuItem>
                        <MenuItem v-slot="{ active }" class="flex gap-2.5 items-center">
                            <ResponsiveNavLink
                                :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm relative']"
                                href="/">
                                <input class="w-full absolute opacity-0 cursor-pointer" mozdirectory
                                       multiple
                                       type="file" webkitdirectory @change="uploadFiles"/>
                                <SvgIcon :path="mdiFolderMultiplePlusOutline" class="w-6 h-auto"/>
                                <span>Upload folder</span>
                            </ResponsiveNavLink>
                        </MenuItem>
                    </div>
                </MenuItems>
            </transition>
        </Menu>
        <CreateFolderModal :show="createFolderShow" @closeModal="createFolderShow = false"/>
    </div>
</template>

<script setup>
import CreateFolderModal from "@/Components/App/CreateFolderModal.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { emitter, FILES_CHOOSE } from "@/event-bus.js";
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
import {
    mdiChevronDown,
    mdiFileDocumentPlusOutline,
    mdiFolderMultiplePlusOutline,
    mdiFolderPlusOutline
} from "@mdi/js";
import { ref } from "vue";
import SvgIcon from "vue3-icon";

const createFolderShow = ref(false);
/**
 * @param {Event} e
 */
const uploadFiles = (e) => emitter.emit(FILES_CHOOSE, e.target.files);
</script>
