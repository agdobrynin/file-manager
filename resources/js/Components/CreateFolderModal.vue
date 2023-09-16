<template>
    <Modal :show="show" @close="closeModal" max-width="sm" @show="setFocus" :closeable="true">
        <div class="p-5">
            <h2 class="text-lg font-medium">Create new folder</h2>
            <div class="mt-5">
                <InputLabel for="name" class="mb-2">Folder name</InputLabel>
                <TextInput
                    id="name"
                    type="text"
                    v-model="form.name"
                    :class="form.errors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                    class="w-full mb-2"
                    placeholder="Enter new folder name"
                    @keyup.enter="createFolder"
                    ref="folderNameInput"
                />
                <InputError :message="form.errors.name"/>
            </div>
            <div class="mt-5 flex justify-between">
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton
                    @click="createFolder"
                    :disable="form.processing"
                    :class="{ 'opacity-25': form.processing }"
                >Create
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { nextTick, ref } from "vue";
import { emitter, FOLDER_CREATE_SUCCESS, successMessage } from "@/event-bus.js";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
        default: false,
    }
})

const form = useForm({
    name: '',
});

const page = usePage();

const folderNameInput = ref(null)

const emit = defineEmits(['close-modal']);

const setFocus = () => nextTick(() => folderNameInput.value?.focus());

const closeModal = () => {
    form.clearErrors();
    form.reset()
    emit('close-modal');
};

const createFolder = () => {
    const name = form.name;

    form.post(route('folder.create', { parentFolder: page.props.parentId || null }), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            successMessage(`The folder "${name}" was created`);
            emitter.emit(FOLDER_CREATE_SUCCESS);
        },
        onError: () => folderNameInput.value.focus()
    });
};
</script>
