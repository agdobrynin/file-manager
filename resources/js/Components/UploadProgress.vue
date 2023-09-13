<template>
  <div>
    <div class="mb-4 font-medium whitespace-nowrap">Upload {{ totalFiles }} files - {{ percent }}%</div>
    <progress max="100" :value="percent"/>
  </div>
</template>

<script setup>

const props = defineProps({
  percent: {
    type: Number,
    default: 0,
    validator(value) {
      return value >= 0 && value <= 100;
    }
  },
  totalFiles: {
    type: Number,
    validator(value) {
      return value > 0;
    },
    default: 1,
  }
})
</script>
