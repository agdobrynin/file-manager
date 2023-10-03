<template>
    <div class="w-full h-[80px] flex items-center relative">
        <SvgIcon
            v-if="value.length"
            :path="mdiClose"
            class="absolute top-0 right-3 flex h-full items-center cursor-pointer text-gray-600"
            @click="onClear"
        />
        <TextInput
            v-model="value"
            :placeholder="placeholder"
            autocomplete="off"
            class="block w-full mr-2 pr-7"
            type="text"
        />
    </div>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import { mdiClose } from "@mdi/js";
import { computed } from "vue";
import SvgIcon from "vue3-icon";

const props = defineProps({
    modelValue: String,
    placeholder: String,
});

const emit = defineEmits([ 'update:modelValue', 'onClear' ]);

const value = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit('update:modelValue', value);
    }
});

const onClear = () => {
    value.value = '';
    emit('onClear');
};
</script>
