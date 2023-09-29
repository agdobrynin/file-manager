<template>
  <div v-if="displayMessages.length" class="flex flex-col gap-2.5 w-[200px] md:w-auto">
    <transition-group
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
      <div v-for="item in displayMessages"
           :key="item.id"
           :class="{
                'border-indigo-800 bg-indigo-500': item.type === notificationTypes.INFO,
                'border-red-700 bg-red-500': item.type === notificationTypes.ERROR,
                'border-green-700 bg-green-500': item.type === notificationTypes.SUCCESS,
                'border-orange-700 bg-orange-500': item.type === notificationTypes.WARNING,
                'border-slate-700 bg-slate-500': item.type === notificationTypes.DEFAULT,
            }"
           class="cursor-pointer border rounded-md text-white drop-shadow-lg p-2"
           @click.stop="close(item.id)"
      >
        <ul :class="[item.message.length > 1 ? 'list-disc px-5' : '']">
          <li v-for="(msg, index) in item.message" :key="index">
            {{ msg }}
          </li>
        </ul>
      </div>
    </transition-group>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { emitter, notificationTypes, SHOW_NOTIFICATION } from "@/event-bus.js";

const messages = ref([]);

const displayMessages = computed(() => messages.value.reverse());

const close = (id) => messages.value = messages.value.filter((item) => item.id !== id);

onMounted(() => {
  emitter.on(SHOW_NOTIFICATION, ({ type, message, timeout = 5000 }) => {
    if (!Object.values(notificationTypes).includes(type)) {
      console.warn(`Unsupported message type ${type}`);

      type = notificationTypes.DEFAULT;
    }

    let id = (Math.random() + 1).toString(36).substring(7);
    const msg = !Array.isArray(message)? [message] : message;

    messages.value = [...messages.value, ...[{ id, type, message: msg }]];

    setTimeout(() => close(id), timeout);
  });
})

onUnmounted(() => {
  emitter.off(SHOW_NOTIFICATION)
})
</script>
