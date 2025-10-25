<!--suppress JSValidateTypes, JSUnresolvedReference -->
<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Dialog, Message } from "primevue";
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'
import UserController from '../actions/App/Http/Controllers/UserController';

import UserProfile from "./Users/Partials/Profile.vue";
import UserCreate from "./Users//Create.vue";


const page = usePage ();
const user = computed ( () => page.props.auth.user );
const header = computed ( () => page.props.header );

/** Dialog Stuff **/
const dialogContent = ref ( null );
const showDialog = ref ( false );
const dialogs = [  ];

const handleDialogOpen = ( link ) => {

  if ( dialogs.includes ( link.click ) ) {
    dialogContent.value = link.click;
    showDialog.value = true;
  } else {
    window.location = link.href;
  }
};

const handleDialogClose = () => {
  showDialog.value = false;
  dialogContent.value = null;
};

const navigation = [
  {
    label: 'Patients', items: [
      { href: '#', label: 'View Patients', click: "patients.index" },
    ]
  },
];

</script>

<template>

  <nav class="bg-darker-800 border-b-2 border-primary-600  font-bold text-white shadow-darker-500/25 shadow-2xl">
    <div class="mx-12 flex h-16 items-center justify-between">
      <div class="flex justify-start items-center gap-x-4">

        <!-- the header text -->
        <h1>
          <a
              href="/"
              class="hover:text-primary-600"
          >{{ header }}
          </a>
        </h1>

        <i
            class="pi pi-circle-fill text-primary-600"
            style="font-size: .2rem"
        ></i>

        <!-- do up the navigation -->
        <Menu
            as="div"
            class="relative inline-block"
            v-for="nav in navigation"
            :key="nav.label"
        >
          <MenuButton class="inline-flex w-full justify-center gap-x-1.5 rounded-md py-2 text-sm font-semibold hover:text-primary-600 cursor-pointer">
            {{ nav.label }}
            <ChevronDownIcon
                class="-mr-1 size-5 text-darker-400"
                aria-hidden="true"
            />
          </MenuButton>

          <transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform scale-100"
              leave-to-class="transform opacity-0 scale-95"
          >
            <MenuItems class="absolute left-0 z-10 mt-2 min-w-48 origin-top-right bg-darker-800 rounded-b-lg">
              <div class="py-1">
                <MenuItem
                    v-for="link in nav.items"
                    :key="link.label"
                >
                  <a
                      href="#"
                      @click.prevent="handleDialogOpen(link)"
                      class="block px-4 py-2 text-sm hover:text-primary-600"
                  >{{ link.label }}</a>
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>
      </div>

      <!-- show the user -->
      <div class="flex justify-end items-center gap-x-4">

        <Menu
            as="div"
            class="relative inline-block"
        >
          <MenuButton class="inline-flex w-full justify-center gap-x-1.5 rounded-md py-2 text-sm font-semibold hover:text-primary-600 cursor-pointer">
            <UserProfile :user="user.data" />
            <ChevronDownIcon
                class="-mr-1 size-5 text-darker-200 mt-2"
                aria-hidden="true"
            />
          </MenuButton>

          <transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform scale-100"
              leave-to-class="transform opacity-0 scale-95"
          >
            <MenuItems class="absolute left-0 z-10 min-w-48 origin-top-right bg-darker-800 rounded-b-lg">
              <div class="py-1">
                <MenuItem>
                  <a
                      :href="UserController.profile(user.data.id).url"
                      class="block px-4 py-2 text-sm hover:text-primary-600"
                  >My Profile</a>
                </MenuItem>
                <MenuItem>
                  <a
                      :href="UserController.index().url"
                      class="block px-4 py-2 text-sm hover:text-primary-600"
                  >View Users</a>
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>
      </div>
    </div>
  </nav>

  <!-- here are our dialogs -->
  <Dialog
      v-model:visible="showDialog"
      modal
  >

    <UserCreate
        v-if="dialogContent === 'users.create'"
        v-on:close-dialog="handleDialogClose"
    />
  </Dialog>

  <!-- main div -->
  <main class="mx-12 mt-6 text-darker-800">

    <Message
        :life="5000"
        :closable="true"
        class="mb-4"
        severity="success"
        v-if="$page.props.flash.message"
    >{{ $page.props.flash.message }}
    </Message>

    <slot />
  </main>
</template>

<style scoped>

</style>