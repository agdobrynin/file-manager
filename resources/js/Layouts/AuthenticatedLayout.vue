<template>
    <div class="h-screen bg-gray-50 flex w-full gap-4">
        <Navigation/>
        <main class="flex flex-col flex-1 px-4 overflow-hidden">
            <div class="flex items-center justify-between w-full">
                <SearchForm/>
                <UserSettingsDropdown/>
            </div>
            <div class="flex-1 flex flex-col overflow-hidden">
                <slot/>
            </div>
        </main>
    </div>
</template>

<script setup>
import Navigation from "@/Components/Navigation.vue";
import UserSettingsDropdown from "@/Components/UserSettingsDropdown.vue";
import SearchForm from "@/Components/SearchForm.vue";
</script>
