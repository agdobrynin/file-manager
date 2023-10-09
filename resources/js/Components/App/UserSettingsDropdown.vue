<template>
    <Menu as="div" class="relative inline-block text-left">
        <div class="whitespace-nowrap">
            <MenuButton
                class="inline-flex w-full justify-center rounded-md px-4 py-2 text-sm font-medium text-gray-800     focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75"
            >
                {{ $page.props.auth.user.name }}
                <SvgIcon :path="mdiChevronDown" class="ml-2 -mr-1 h-5 w-5 text-gray-600 hover:text-gray-300"/>
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
                class="absolute right-0 mt-2 w-auto origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            >
                <div class="px-1 py-1">
                    <MenuItem v-slot="{ active }" class="flex gap-4">
                        <ResponsiveNavLink
                            class="whitespace-nowrap"
                            :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']"
                            :href="route('profile.edit')">
                            <SvgIcon :path="mdiFaceManProfile" size="20"/>
                            Profile
                        </ResponsiveNavLink>
                    </MenuItem>
                    <Link :href="route('logout')"
                          as="button"
                          class="whitespace-nowrap flex gap-4 text-gray-700', 'block px-4 py-2 text-sm hover:text-indigo-600 hover:border-l-4 hover:border-amber-300 border-l-4 border-transparent"
                          method="post">
                        <SvgIcon :path="mdiLogout" size="20"/>
                        Log out
                    </Link>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>

<script setup>
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue"
import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue"
import { Link } from "@inertiajs/vue3"
import { mdiChevronDown, mdiFaceManProfile, mdiLogout } from "@mdi/js";
import SvgIcon from "vue3-icon";
</script>
