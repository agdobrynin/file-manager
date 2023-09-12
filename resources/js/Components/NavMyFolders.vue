<template>
    <nav class="flex items-center justify-between">
        <ol class="inline-flex items-center space-x-1">
            <li v-for="ancestor of ancestors" :key="ancestor.id" class="inline-flex items-center whitespace-nowrap mb-2">
                <div @click="$emit('openFolder', ancestor)" class="cursor-pointer hover:text-indigo-600 inline-flex items-center">
                    <div v-if="!ancestor.parentId" class="flex space-x-2 items-center">
                        <HomeIcon class="w-6 h-6"/>
                        <div>My files</div>
                    </div>
                    <div v-else class="inline-flex items-center space-x-0.5">
                        <ChevronRightIcon class="w-6 h-6 text-slate-300"/>
                        <div>{{ ancestor.name }}</div>
                    </div>
                </div>
            </li>
        </ol>
    </nav>
</template>

<script setup>
import { ChevronRightIcon, HomeIcon } from "@heroicons/vue/24/outline/index.js";

const  props = defineProps({
    ancestors: {
        type: Array,
        default: [],
    }
})

defineEmits(['openFolder'])
</script>
