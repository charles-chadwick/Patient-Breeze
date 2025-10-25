<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import {InputText, Password, Button} from 'primevue';
import AuthController from '../../actions/App/Http/Controllers/AuthController';

const form = useForm ( {
  email: '',
  password: '',
} );
const page = usePage ();
const header = computed ( () => page.props.header );
const loading = ref ( false );

const submit = () => {
  loading.value = true;
  form.post ( AuthController.login().url, {
    onFinish: () => loading.value = false
  } );
};
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-darker-100">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-md">
      <h2 class="mb-6 text-center text-2xl font-bold text-primary-600">{{ header }}</h2>
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="mb-2 block text-sm font-medium text-darker-700">Email</label>
          <InputText
              v-model="form.email"
              type="email"
              class="w-full"
              :class="{ 'p-invalid': form.errors.email }"
              placeholder="Enter your email"
          />
          <small class="text-red-500">{{ form.errors.email }}</small>
        </div>
        <div class="mb-6">
          <label class="mb-2 block text-sm font-medium text-darker-700">Password</label>
          <Password
              v-model="form.password"
              class="w-full"
              :class="{ 'p-invalid': form.errors.password }"
              :feedback="false"
              toggleMask
          />
          <small class="text-red-500">{{ form.errors.password }}</small>
        </div>
        <Button
            type="submit"
            :loading="loading"
            class="w-full"
            label="Login"
        />
      </form>
    </div>
  </div>
</template>

<style scoped>
:deep(.p-password input) {
  width: 100%;
}
</style>