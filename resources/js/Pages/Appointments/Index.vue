<script setup>
import { computed } from 'vue'
import { Link, router, setLayoutProps } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import {
  PopoverContent,
  PopoverPortal,
  PopoverRoot,
  PopoverTrigger,
} from 'reka-ui'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import MiniCalendar from '@/Components/ui/MiniCalendar.vue'
import SearchInput from '@/Components/SearchInput.vue'
import StaffFilter from '@/Components/StaffFilter.vue'
import { cn, formatDate, DATE_SHORT } from '@/lib/utils'

defineOptions ( { layout: DashboardLayout } )

setLayoutProps({
    breadcrumbs: computed(() => [
        { label: trans('nav.appointments') },
    ]),
})

const props = defineProps ( {
  appointments: {
    type: Array,
    default: () => [],
  },
  date: {
    type: String,
    required: true,
  },
  view: {
    type: String,
    default: 'week',
  },
  search: {
    type: String,
    default: '',
  },
  staff: {
    type: Array,
    default: () => [],
  },
  selected_staff: {
    type: Array,
    default: () => [],
  },
} )

const statusClasses = {
  Scheduled: 'bg-cerulean-100 text-cerulean-700',
  Confirmed: 'bg-tropical-teal-100 text-tropical-teal-700',
  Completed: 'bg-muted text-muted-foreground',
  Cancelled: 'bg-vibrant-coral-100 text-vibrant-coral-700',
  Rescheduled: 'bg-light-yellow-100 text-light-yellow-700',
  NoShow: 'bg-soft-apricot-100 text-soft-apricot-700',
}

const appointmentsByDate = computed ( () => {
  const groups = {}
  for ( const appointment of props.appointments ) {
    const key = appointment.date.substring ( 0, 10 )
    if ( ! groups[ key ] ) groups[ key ] = []
    groups[ key ].push ( appointment )
  }
  return Object.keys ( groups )
      .sort ()
      .map ( ( date ) => ( { date, items: groups[ date ] } ) )
} )

function formatTime ( time ) {
  return time?.slice ( 0, 5 ) ?? ''
}

function navigate ( overrides = {} ) {
  router.get (
      route ( 'appointments.index' ),
      {
        date: props.date,
        view: props.view,
        search: props.search || undefined,
        staff: props.staff.length ? props.staff : undefined,
        ...overrides,
      },
      { preserveState: true, replace: true },
  )
}

function onDateChange ( newDate ) {
  navigate ( { date: newDate } )
}

function setView ( newView ) {
  navigate ( { view: newView } )
}

function onStaffChange ( newStaff ) {
  navigate ( { staff: newStaff.length ? newStaff : undefined } )
}

const hasActiveFilters = computed ( () => Boolean ( props.search ) || props.staff.length > 0 )

function clearFilters () {
  navigate ( { search: undefined, staff: undefined } )
}

</script>

<template>
  <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
    <!-- Left: mini calendar + view toggle -->
    <aside class="w-full shrink-0 lg:w-1/3">
      <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
        <MiniCalendar
            :model-value="date"
            :view="view"
            @update:model-value="onDateChange"
        />

        <div class="mt-4 flex rounded-lg border border-border p-1 gap-1">
          <button
              type="button"
              :class="cn(
                            'flex-1 rounded-md py-1.5 text-sm font-bold transition-colors focus:outline-none',
                            view === 'day' ? 'bg-primary text-white' : 'text-muted-foreground hover:text-foreground',
                        )"
              @click="setView('day')"
          >
            {{ $t('appointments.index.view_day') }}
          </button>
          <button
              type="button"
              :class="cn(
                            'flex-1 rounded-md py-1.5 text-sm font-bold transition-colors focus:outline-none',
                            view === 'week' ? 'bg-primary text-white' : 'text-muted-foreground hover:text-foreground',
                        )"
              @click="setView('week')"
          >
            {{ $t('appointments.index.view_week') }}
          </button>
        </div>
      </div>
    </aside>

    <!-- Right: filters + appointment list -->
    <div class="min-w-0 flex-1">

      <!-- Filters -->
      <div class="mb-2 flex justify-end">
        <button
            type="button"
            :disabled="!hasActiveFilters"
            class="text-sm font-bold text-primary hover:underline disabled:cursor-not-allowed disabled:text-muted-foreground disabled:no-underline"
            @click="clearFilters"
        >
          {{ $t('appointments.index.clear_filters') }}
        </button>
      </div>
      <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div
            class="w-1/2"
        >
          <SearchInput
              :model-value="search"
              :placeholder="$t('appointments.index.search_placeholder')"
              route-name="appointments.index"
              :params="{
                        date,
                        view,
                        staff: staff.length ? staff : undefined,
                    }"
          />
        </div>

        <div class="w-1/2">
          <StaffFilter
              :model-value="staff"
              :selected="selected_staff"
              :placeholder="$t('appointments.index.filter_staff')"
              @update:model-value="onStaffChange"
          />
        </div>

      </div>

      <!-- Empty state -->
      <div
          v-if="appointmentsByDate.length === 0"
          class="rounded-xl border border-border bg-card px-6 py-14 text-center shadow-sm"
      >
        <p class="text-sm text-muted-foreground">{{ $t('appointments.index.empty') }}</p>
      </div>

      <!-- Grouped appointment list -->
      <div
          v-else
          class="flex flex-col gap-6"
      >
        <section
            v-for="group in appointmentsByDate"
            :key="group.date"
        >
          <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">
            {{ formatDate ( group.date, DATE_SHORT ) }}
          </h2>

          <div class="flex flex-col gap-2">
            <PopoverRoot
                v-for="appointment in group.items"
                :key="appointment.id"
            >
              <PopoverTrigger as-child>
                <button
                    type="button"
                    class="w-full rounded-xl border border-border bg-card px-5 py-4 text-left shadow-sm transition-colors hover:border-primary/40 hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary/50"
                >
                  <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                      <img
                          :src="appointment.patient.avatar_url"
                          :alt="`${appointment.patient.first_name} ${appointment.patient.last_name}`"
                          class="size-9 shrink-0 rounded-full object-cover"
                      />
                      <div class="min-w-0">
                        <p class="font-bold text-foreground">
                          {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                        </p>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                          {{ formatTime ( appointment.start_time ) }}–{{ formatTime ( appointment.end_time ) }}
                        </p>
                        <p
                            v-if="appointment.users?.length"
                            class="mt-0.5 text-sm text-muted-foreground"
                        >
                          {{ appointment.users.map ( ( u ) => `${ u.first_name } ${ u.last_name }` ).join ( ', ' ) }}
                        </p>
                      </div>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold"
                        :class="statusClasses[appointment.status] ?? 'bg-muted text-muted-foreground'"
                    >
                                            {{ $t('enums.appointment_status.' + appointment.status) }}
                                        </span>
                  </div>
                </button>
              </PopoverTrigger>

              <PopoverPortal>
                <PopoverContent
                    side="bottom"
                    align="start"
                    :side-offset="8"
                    :collision-padding="16"
                    class="z-50 w-80 rounded-xl border border-border bg-popover p-5 shadow-xl focus:outline-none"
                >
                  <div class="mb-4 flex items-center gap-3 border-b border-border pb-4">
                    <img
                        :src="appointment.patient.avatar_url"
                        :alt="`${appointment.patient.first_name} ${appointment.patient.last_name}`"
                        class="size-10 rounded-full object-cover"
                    />
                    <Link
                        :href="route('patients.show', appointment.patient.id)"
                        class="text-base font-bold text-primary hover:underline"
                    >
                      {{ appointment.patient.first_name }} {{ appointment.patient.last_name }}
                    </Link>
                  </div>

                  <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                      <dt class="font-bold text-muted-foreground">{{ $t('appointments.index.detail_date') }}</dt>
                      <dd class="text-foreground">{{ formatDate ( appointment.date, DATE_SHORT ) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <dt class="font-bold text-muted-foreground">{{ $t('appointments.index.detail_time') }}</dt>
                      <dd class="text-foreground">
                        {{ formatTime ( appointment.start_time ) }}–{{ formatTime ( appointment.end_time ) }}
                      </dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <dt class="font-bold text-muted-foreground">{{ $t('appointments.index.detail_status') }}</dt>
                      <dd>
                                                <span
                                                    class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                    :class="statusClasses[appointment.status] ?? 'bg-muted text-muted-foreground'"
                                                >
                                                    {{ $t('enums.appointment_status.' + appointment.status) }}
                                                </span>
                      </dd>
                    </div>
                    <div
                        v-if="appointment.reason"
                        class="flex items-start justify-between gap-2"
                    >
                      <dt class="font-bold text-muted-foreground">{{ $t('appointments.index.detail_reason') }}</dt>
                      <dd class="text-right text-foreground">{{ appointment.reason }}</dd>
                    </div>
                  </dl>

                  <div
                      v-if="appointment.users?.length"
                      class="mt-4 border-t border-border pt-4"
                  >
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ $t('appointments.index.detail_staff') }}</p>
                    <ul class="space-y-1.5">
                      <li
                          v-for="user in appointment.users"
                          :key="user.id"
                          class="flex items-center gap-2 text-sm"
                      >
                        <img
                            :src="user.avatar_url"
                            :alt="`${user.first_name} ${user.last_name}`"
                            class="size-7 rounded-full object-cover"
                        />
                        <span class="flex-1 text-foreground">
                                                    {{ user.first_name }} {{ user.last_name }}
                                                </span>
                        <span class="rounded-md bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                    {{ $t('enums.appointment_role.' + user.pivot.role) }}
                                                </span>
                      </li>
                    </ul>
                  </div>

                  <div class="mt-4 border-t border-border pt-4">
                    <Link
                        as="button"
                        type="button"
                        :href="route('patients.appointments.edit', [appointment.patient.id, appointment.id])"
                        class="text-sm font-bold text-primary hover:underline"
                    >
                      {{ $t('appointments.index.edit_appointment') }}
                    </Link>
                  </div>
                </PopoverContent>
              </PopoverPortal>
            </PopoverRoot>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>
