<!--suppress JSValidateTypes, JSUnresolvedReference -->
<script setup>
import { usePage, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Dialog, Message } from "primevue";
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'

const page = usePage ();
const user = page.props.auth.user.data;
const header = computed ( () => page.props.header );

/** Dialog Stuff **/
const dialogContent = ref ( null );
const showDialog = ref ( false );
const dialogs = [];

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
      { href: route ( 'patients.index' ), label: 'View Patients', click: "patients.index" },
    ]
  }, {
    label: 'Users', items: [
      { href: route ( 'users.index' ), label: 'Manage Users', click: "users.index" },
    ]
  },
];

const logout = () => {
  const form = useForm ();
  form.post ( route ( 'logout' ) )
}

</script>

<template>

  <nav class="bg-darker-800 border-b-2 border-accent-500  font-bold text-white shadow-darker-500/25 shadow-2xl">
    <div class="mx-12 flex h-16 items-center justify-between">
      <div class="flex justify-start items-center gap-x-4">

        <!-- the header text -->
        <h1>
          <a
              href="/"
              class="hover:text-primary-400"
          >{{ header }}
          </a>
        </h1>

        <i
            class="pi pi-circle-fill text-primary-400"
            style="font-size: .2rem"
        ></i>

        <!-- do up the navigation -->
        <Menu
            as="div"
            class="relative inline-block"
            v-for="nav in navigation"
            :key="nav.label"
        >
          <MenuButton class="inline-flex w-full justify-center gap-x-1.5 rounded-md py-2 text-sm font-semibold hover:text-primary-400 cursor-pointer">
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
                      class="block px-4 py-2 text-sm hover:text-primary-400"
                  >{{ link.label }}</a>
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>
      </div>

      <Menu
          as="div"
          class="relative ml-auto"
      >
        <MenuButton class="inline-flex w-full justify-center gap-x-1.5 rounded-md py-2 text-sm font-semibold hover:text-primary-400 cursor-pointer">
          {{ user.attributes.first_name }} {{ user.attributes.last_name }}
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
          <MenuItems class="absolute right-0 z-10 mt-2 min-w-48 origin-top-right bg-darker-800 rounded-b-lg">
            <div class="py-1">
              <MenuItem v-slot="{ active }">
                <a
                    :href="route('users.profile', {user: user.attributes.id})"
                    class="block px-4 py-2 text-sm hover:text-primary-400"
                >
                  Profile
                </a>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <a
                    href="#"
                    @click.prevent="logout"
                    class="block px-4 py-2 text-sm hover:text-primary-400"
                >
                  Log Out
                </a>
              </MenuItem>
            </div>
          </MenuItems>
        </transition>
      </Menu>
    </div>
  </nav>

  <!-- here are our dialogs -->
  <Dialog
      v-model:visible="showDialog"
      modal
  >

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