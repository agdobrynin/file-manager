<template>
    <nav class="min-w-[200px]">
        <div class="h-[80px] px-3 flex items-center gap-3 hover:text-indigo-700 text-indigo-600">
            <Link :href="route('dashboard')" class="flex gap-3 items-center">
                <ApplicationLogo class="block h-9 w-auto"/>
                LaraStorage
            </Link>
        </div>
        <div class="px-3">
            <div class="py-3">
                <NavLink
                    :active="routeName === 'file.index'"
                    :href="route('file.index')"
                    class="flex gap-2">
                    <SvgIcon :path="mdiCloudOutline" size="30"/>
                    My files
                </NavLink>
                <NavLink
                    :active="routeName === 'share_for_me.index'"
                    :href="route('share_for_me.index')"
                    class="flex gap-2"
                >
                    <SvgIcon :path="mdiMonitorShare" size="30"/>
                    Shared for me
                </NavLink>
                <NavLink
                    :active="routeName === 'share_by_me.index'"
                    :href="route('share_by_me.index')"
                    class="flex gap-2"
                >
                    <SvgIcon :path="mdiShare" size="30"/>
                    Shared by me
                </NavLink>
                <NavLink
                    :active="routeName === 'trash.index'"
                    :href="route('trash.index')"
                    class="flex gap-2">
                    <SvgIcon :path="mdiTrashCanOutline" size="30"/>
                    Trash
                </NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import NavLink from "@/Components/NavLink.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { mdiCloudOutline, mdiMonitorShare, mdiShare, mdiTrashCanOutline } from "@mdi/js";
import { computed } from "vue";
import SvgIcon from "vue3-icon";

const page = usePage();

const routeName = computed(() => page.props.route_name);
</script>
