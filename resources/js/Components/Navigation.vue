<template>
    <nav class="min-w-[200px]">
        <div class="h-[80px] px-3 flex items-center gap-3 hover:text-indigo-700 text-indigo-600">
            <Link :href="route('my.files')" class="flex gap-3 items-center">
                <ApplicationLogo class="block h-9 w-auto"/>
                LaraStorage
            </Link>
        </div>
        <div class="px-3">
            <div class="px-3">
                <CreateNewDropdown/>
            </div>
            <div class="py-3">
                <NavLink :href="route('my.files')" class="flex gap-2">
                    <CloudIcon class="w-7 h-auto"/>
                    My files
                </NavLink>
                <NavLink href="" class="flex gap-2">
                    <ShareIconSolid class="w-7 h-auto"/>
                    Shared with me
                </NavLink>
                <NavLink href="" class="flex gap-2">
                    <ShareIcon class="w-7 h-auto"/>
                    Shared by me
                </NavLink>
                <NavLink href="" class="flex gap-2">
                    <TrashIcon class="w-7 h-auto"/>
                    Trash
                </NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import CreateNewDropdown from "@/Components/CreateNewDropdown.vue";
import NavLink from "@/Components/NavLink.vue";
import { TrashIcon, ShareIcon } from '@heroicons/vue/24/outline'
import { ShareIcon as ShareIconSolid, CloudIcon } from '@heroicons/vue/24/solid'
</script>
