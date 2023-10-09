<template>
    <SwitchGroup>
        <div class="flex items-center border border-gray-300 rounded-md font-light px-2 py-1.5">
            <Switch
                v-model="value"
                :class="value ? 'bg-blue-600' : 'bg-gray-400'"
                class="relative inline-flex h-4 w-8 items-center rounded-full"
            >
                <span class="sr-only">Only favorites</span>
                <span :class="value ? 'translate-x-5' : 'translate-x-1'"
                      class="inline-block h-2 w-2 transform rounded-full bg-white transition"
                />
            </Switch>
            <SwitchLabel :class="value ? 'text-blue-600' : 'text-gray-400'" class="ms-2">
                Only favorites
            </SwitchLabel>
        </div>
    </SwitchGroup>
</template>

<script setup>
import { Switch, SwitchGroup, SwitchLabel } from "@headlessui/vue";
import { computed } from "vue";

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    }
})
const emit = defineEmits([ 'update:modelValue' ]);

const value = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value)
    }
})
</script>
