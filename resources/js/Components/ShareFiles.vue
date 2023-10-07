<template>
    <SecondaryButton :disabled="isDisable || inProcess" @click="show = true">
        <SvgIcon
            :class="{'animate-ping' : inProcess}"
            :path="mdiShare"
            class="mr-2 h-5 w-5 text-gray-600"
            type="mdi"/>
        <div>Share</div>
    </SecondaryButton>
    <ConfirmationDialog
        :show="show"
        confirm-title="Share"
        title="Share files"
        @cancel="closeWithReset"
        @close="closeWithReset"
        @confirm="doShare"
    >
        <InputLabel
            class="mb-2"
            for="email"
            value="User email for share files"
        />
        <TextInput
            ref="inputEmail"
            id="email"
            v-model="form.email"
            class="w-full"
            placeholder="Input user email for share selected files"
            @keyup.enter="doShare"
        />
        <InputError v-for="(error, index) in form.errors"
                    :key="index"
                    :message="error"
                    class="mt-2"
        />
    </ConfirmationDialog>
</template>

<script setup>
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/vue3";
import { mdiShare } from "@mdi/js";
import { computed, nextTick, ref, watch } from "vue";
import SvgIcon from "vue3-icon";

const props = defineProps({
    parentFolder: Number,
    fileIds: Array,
    allFiles: Boolean,
});

const form = useForm({
    ids: [],
    all: false,
    email: '',
});

const emit = defineEmits([ 'success' ])

const inProcess = ref(false);
const show = ref(false);
const inputEmail = ref(null);

watch(show, (val) => {
    if (val) {
        nextTick(() => inputEmail.value.focus());
    }
});

const isDisable = computed(() => ( ! props.fileIds.length && ! props.allFiles));

const closeWithReset = () => {
    form.reset();
    form.errors = {};
    show.value = false;
};

const doShare = () => {
    form.all = props.allFiles;
    form.ids = props.fileIds;

    form.post(route('file.share', { parentFolder: props.parentFolder || null }), {
        preserveState: true,
        only: [ 'flash', 'errors' ],
        onSuccess: () => {
            closeWithReset();
            emit('success');
        }
    });
};
</script>
