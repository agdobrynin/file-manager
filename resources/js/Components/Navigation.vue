<template>
    <nav class="min-w-[200px]">
        <div class="h-[80px] px-3 flex items-center gap-3">
            <Link href="/">
                <ApplicationLogo class="block h-9 w-auto fill-current text-indigo-400"/>
            </Link>
            <Link href="/">
                LaraStorage
            </Link>
        </div>
        <div class="px-3">
            <div class="px-3">
                <CreateNewDropdown/>
            </div>
            <div class="py-3">
                <NavLink href="">My files</NavLink>
                <NavLink href="">Shared with me</NavLink>
                <NavLink href="">Shared by me</NavLink>
                <NavLink href="">Trash</NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import {Link} from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import CreateNewDropdown from "@/Components/CreateNewDropdown.vue";
import NavLink from "@/Components/NavLink.vue";
</script>
