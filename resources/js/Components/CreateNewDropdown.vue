<template>
    <Menu as="div" class="relative inline-block text-left">
        <div>
            <MenuButton
                class="inline-flex w-full justify-center border rounded-md px-4 py-2 text-sm font-medium text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75"
            >
                Create New
                <ChevronDownIcon
                    class="ml-2 -mr-1 h-5 w-5 text-gray-600 hover:text-gray-300"
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
                    <MenuItem v-slot="{ active }">
                        <ResponsiveNavLink
                            href="/"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">
                            New Folder
                        </ResponsiveNavLink>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <ResponsiveNavLink
                            href="/"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">
                            Upload files
                        </ResponsiveNavLink>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <ResponsiveNavLink
                            href="/"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">
                            Upload folder
                        </ResponsiveNavLink>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

<script setup>
import {ChevronDownIcon} from "@heroicons/vue/20/solid/index.js";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
</script>
