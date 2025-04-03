<template>
  <input :class="props.class" type="text" v-model="internalModel" :placeholder="props.placeholder" />
</template>

<script setup>
  import { watch, ref } from 'vue';
  import _debounce from 'lodash-es/debounce';

  const internalModel = ref('');
  const model = defineModel();
  const props = defineProps({
      delay: {
          type: Number,
          default: 500
      },
      placeholder: {
          type: String,
          default: ''
      },
      class: {
          type: String,
          default: ''
      },
  });

  watch(internalModel, (newVal) => {
      updateModel(newVal);
  });

  const updateModel = _debounce((newVal) => {
      model.value = newVal;
  }, props.delay);
</script>

