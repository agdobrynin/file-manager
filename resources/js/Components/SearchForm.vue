<template>
    <div class="w-full h-[80px] flex items-center">
        <div class="relative w-full">
            <SvgIcon
                v-if="value.length"
                :path="mdiClose"
                class="absolute top-0 right-3 flex h-full cursor-pointer text-gray-400 hover:text-gray-700"
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
    </div>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import { debounce } from "@/helpers/helper.js";
import { mdiClose } from "@mdi/js";
import { computed, onMounted } from "vue";
import SvgIcon from "vue3-icon";

/**
 * @type {Function | undefined}
 */
let debounceFunc;

const props = defineProps({
    modelValue: String,
    placeholder: String,
    delay: Number,
});

const emit = defineEmits([ 'update:modelValue', 'onClear' ]);

const delayValue = computed(() => parseInt(props.delay) || null);

const value = computed({
    get() {
        return props.modelValue;
    },
    set(value) {
        if (debounceFunc) {
            debounceFunc(value);
        } else {
            emit('update:modelValue', value);
        }
    }
});

const onClear = () => {
    emit('update:modelValue', '');
    emit('onClear');
};

onMounted(() => {
    if (delayValue.value > 0) {
        debounceFunc = debounce(function (val) {
            emit('update:modelValue', val);
        }, delayValue.value)
    }
})
</script>
