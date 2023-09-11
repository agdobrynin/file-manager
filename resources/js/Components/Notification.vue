<template>
    <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="show"
             @click="close"
             class="cursor-pointer fixed top-2.5 right-2.5 text-white py-2 px-4 rounded-lg shadow-md w-[200px]"
             :class="{
                'bg-emerald-500': typeOfMessage === 'success',
                'bg-red-500': typeOfMessage === 'error'
            }">
            <ul v-if="displayMessages && displayMessages.length" :class="[displayMessages.length > 1 ? 'list-disc' : '', 'p-2']">
                <li v-for="(msg, index) in displayMessages" :key="index">
                    {{ msg }}
                </li>
            </ul>
            <div v-else>
                Not found messages...
            </div>
        </div>
    </transition>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { emitter, SHOW_NOTIFICATION } from "@/event-bus.js";

const show = ref(false);

const typeOfMessage = ref('success');

const displayMessages = ref(null);

const close = () => {
    show.value = false;
    typeOfMessage.value = null;
    displayMessages.value = null
}


onMounted(() => {
    let timeoutPointer;

    emitter.on(SHOW_NOTIFICATION, ({ type, message, timeout }) => {
        show.value = true;
        typeOfMessage.value = type || 'success';

        if (message) {
            displayMessages.value = !Array.isArray(message) ? [message] : message;
        } else {
            console.warn('Payload without key "messages"');
        }

        if (timeoutPointer) {
            clearTimeout(timeoutPointer);
        }

        timeoutPointer = setTimeout(() => close(), timeout || 5000);
    })
})
</script>
