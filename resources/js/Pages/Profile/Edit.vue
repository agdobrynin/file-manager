<template>
  <Head title="Profile"/>

  <AuthenticatedLayout>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight pb-3">Profile</h2>
    <div class="max-w-7xl overflow-auto">
      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <UpdateProfileInformationForm
            :must-verify-email="mustVerifyEmail"
            :status="status"
            class="max-w-xl"
        />
      </div>

      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <UpdatePasswordForm class="max-w-xl"/>
      </div>

      <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <DeleteUserForm class="max-w-xl"/>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
  mustVerifyEmail: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});
</script>
