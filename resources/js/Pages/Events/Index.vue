<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// ─── Props ──────────────────────────────────────────────────────────────────

const props = defineProps({
    events: Object, // Laravel paginator object
    organizations: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuperadmin = computed(() => {
    const roles = user.value?.roles ?? [];
    return roles.includes('Superadmin') || roles.includes('Admin');
});

// ─── RSVP Modal state ────────────────────────────────────────────────────────

const selectedEvent = ref(null);
const modalOpen     = ref(false);

function openModal(event) {
    selectedEvent.value = event;
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    // Small delay so the close animation finishes before clearing data
    setTimeout(() => { selectedEvent.value = null; }, 200);
}

// ─── RSVP action ─────────────────────────────────────────────────────────────

const submitting = ref(false);

function submitRsvp(status) {
    if (!selectedEvent.value || submitting.value) return;
    submitting.value = true;

    router.post(
        route('events.rsvp', { event: selectedEvent.value.id }),
        { status },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                submitting.value = false;
            },
            onError: () => { submitting.value = false; },
        }
    );
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const typeConfig = {
    physical: { label: 'Fizikal', classes: 'bg-emerald-100 text-emerald-700' },
    online:   { label: 'Dalam Talian', classes: 'bg-sky-100 text-sky-700' },
};

const rsvpConfig = {
    going:    { label: 'Hadir',   classes: 'bg-emerald-100 text-emerald-700' },
    maybe:    { label: 'Mungkin', classes: 'bg-amber-100 text-amber-700'    },
    declined: { label: 'Tidak',   classes: 'bg-red-100 text-red-600'         },
    attended: { label: 'Hadir ✓', classes: 'bg-emerald-100 text-emerald-700' },
};

// ─── Create Program Modal (Admin/Superadmin) ───────────────────────────────

const createModalOpen = ref(false);
const createProgramForm = useForm({
    organization_id: '',
    title: '',
    description: '',
    type: 'physical',
    location_or_link: '',
    start_time: '',
    end_time: '',
    featured_image: null,
});

function openCreateProgramModal() {
    createModalOpen.value = true;
}

function closeCreateProgramModal() {
    createModalOpen.value = false;
}

function submitCreateProgram() {
    createProgramForm.post(route('events.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createProgramForm.reset();
            closeCreateProgramModal();
        },
    });
}
</script>

<template>
    <Head title="Program & Acara" />

    <AppLayout>
        <template #header>Program &amp; Acara</template>

        <div class="max-w-7xl mx-auto px-4 md:px-6 py-6">

            <!-- Page header row -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Program Akan Datang</h2>
                    <p class="text-sm text-gray-400 mt-0.5">{{ events.total }} program dijumpai</p>
                </div>
                <button
                    v-if="isSuperadmin"
                    @click="openCreateProgramModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add New Program
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="events.data.length === 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="font-semibold text-gray-600">Tiada program dijadualkan</p>
                <p class="text-sm text-gray-400 mt-1">Program baharu akan muncul di sini apabila dijadualkan.</p>
            </div>

            <!-- Event Grid: 1 col → 2 col (sm) → 3 col (lg) -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <article
                    v-for="event in events.data"
                    :key="event.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden
                           hover:-translate-y-1 hover:shadow-md transition-all duration-200 cursor-pointer group"
                    @click="openModal(event)"
                >
                    <!-- Featured Image -->
                    <div class="relative aspect-video overflow-hidden bg-gray-100">
                        <img
                            :src="event.featured_image_url"
                            :alt="event.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />

                        <!-- Type badge (top-left) -->
                        <span
                            :class="['absolute top-3 left-3 inline-flex items-center text-[11px] font-bold px-2.5 py-1 rounded-full backdrop-blur-sm',
                                     typeConfig[event.type]?.classes ?? 'bg-gray-100 text-gray-600']"
                        >
                            {{ typeConfig[event.type]?.label ?? event.type }}
                        </span>

                        <!-- My RSVP badge (top-right) — only shown if user has RSVPed -->
                        <span
                            v-if="event.my_rsvp"
                            :class="['absolute top-3 right-3 inline-flex items-center text-[11px] font-bold px-2.5 py-1 rounded-full backdrop-blur-sm',
                                     rsvpConfig[event.my_rsvp]?.classes]"
                        >
                            {{ rsvpConfig[event.my_rsvp]?.label }}
                        </span>
                    </div>

                    <!-- Card body -->
                    <div class="p-5 space-y-2">
                        <!-- Org pill -->
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full"
                            :style="{ backgroundColor: event.organization.color_theme + '22', color: event.organization.color_theme }"
                        >
                            {{ event.organization.name }}
                        </span>

                        <h3 class="font-bold text-gray-800 text-sm leading-snug line-clamp-2">
                            {{ event.title }}
                        </h3>

                        <!-- Date and location row -->
                        <div class="space-y-1 pt-1">
                            <p class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ event.start_formatted }}
                            </p>
                            <p v-if="event.location_or_link" class="flex items-center gap-1.5 text-xs text-gray-400 truncate">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ event.location_or_link }}
                            </p>
                        </div>

                        <!-- Footer: RSVP count + CTA -->
                        <div class="flex items-center justify-between pt-2 border-t border-gray-50 mt-3">
                            <span class="text-xs text-gray-400">{{ event.rsvp_count }} akan hadir</span>
                            <button
                                class="text-xs font-semibold px-3 py-1.5 rounded-xl text-white transition-opacity hover:opacity-90"
                                :style="{ backgroundColor: event.organization.color_theme }"
                            >
                                {{ isSuperadmin ? 'Lihat Butiran' : (event.my_rsvp ? 'Kemaskini RSVP' : 'Daftar Hadir') }}
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Pagination -->
            <div v-if="events.last_page > 1" class="flex justify-center mt-8 gap-2">
                <a
                    v-for="link in events.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="[
                        'inline-flex items-center justify-center h-9 min-w-9 px-3 rounded-xl text-sm font-medium transition-colors',
                        link.active
                            ? 'bg-gray-900 text-white'
                            : link.url
                                ? 'bg-white border border-gray-100 text-gray-600 hover:border-gray-300'
                                : 'bg-white border border-gray-100 text-gray-300 pointer-events-none',
                    ]"
                />
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!--  CREATE PROGRAM MODAL                                            -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="createModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm"
                    @click.self="closeCreateProgramModal"
                >
                    <div class="w-full max-w-2xl rounded-3xl border border-white/50 bg-white/95 shadow-2xl overflow-hidden">
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <h3 class="text-base font-black text-gray-800">Add New Program</h3>
                            <button @click="closeCreateProgramModal" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form class="space-y-4 p-5" @submit.prevent="submitCreateProgram">
                            <div v-if="isSuperadmin && organizations.length" class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500">Organization *</label>
                                <select v-model="createProgramForm.organization_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    <option disabled value="">Pilih organisasi</option>
                                    <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                                </select>
                                <p v-if="createProgramForm.errors.organization_id" class="text-xs text-red-500">{{ createProgramForm.errors.organization_id }}</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500">Title *</label>
                                <input v-model="createProgramForm.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" required>
                                <p v-if="createProgramForm.errors.title" class="text-xs text-red-500">{{ createProgramForm.errors.title }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-gray-500">Type *</label>
                                    <select v-model="createProgramForm.type" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                        <option value="physical">Fizikal</option>
                                        <option value="online">Dalam Talian</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-gray-500">Lokasi / Link</label>
                                    <input v-model="createProgramForm.location_or_link" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-gray-500">Start Time *</label>
                                    <input v-model="createProgramForm.start_time" type="datetime-local" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" required>
                                    <p v-if="createProgramForm.errors.start_time" class="text-xs text-red-500">{{ createProgramForm.errors.start_time }}</p>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-gray-500">End Time *</label>
                                    <input v-model="createProgramForm.end_time" type="datetime-local" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" required>
                                    <p v-if="createProgramForm.errors.end_time" class="text-xs text-red-500">{{ createProgramForm.errors.end_time }}</p>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500">Description</label>
                                <textarea v-model="createProgramForm.description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"></textarea>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500">Featured Image</label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
                                    @change="createProgramForm.featured_image = $event.target.files?.[0] ?? null"
                                >
                                <p v-if="createProgramForm.errors.featured_image" class="text-xs text-red-500">{{ createProgramForm.errors.featured_image }}</p>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeCreateProgramModal" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="createProgramForm.processing" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                                    {{ createProgramForm.processing ? 'Saving...' : 'Save Program' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!--  RSVP MODAL (glassmorphism)                                     -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="modalOpen && selectedEvent"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4
                           bg-gray-900/40 backdrop-blur-sm"
                    @click.self="closeModal"
                >
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div
                            v-if="modalOpen"
                            class="w-full max-w-md bg-white/90 backdrop-blur-md rounded-3xl shadow-2xl
                                   border border-white/50 overflow-hidden"
                        >
                            <!-- Modal header image -->
                            <div class="relative h-36 bg-gray-100 overflow-hidden">
                                <img
                                    :src="selectedEvent.featured_image_url"
                                    :alt="selectedEvent.title"
                                    class="w-full h-full object-cover"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

                                <!-- Close button -->
                                <button
                                    @click="closeModal"
                                    class="absolute top-3 right-3 flex h-8 w-8 items-center justify-center rounded-full bg-black/30 text-white hover:bg-black/50 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>

                                <span
                                    class="absolute bottom-3 left-3 inline-flex text-[11px] font-bold px-2.5 py-1 rounded-full"
                                    :class="typeConfig[selectedEvent.type]?.classes"
                                >
                                    {{ typeConfig[selectedEvent.type]?.label }}
                                </span>
                            </div>

                            <!-- Modal body -->
                            <div class="p-5 space-y-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 mb-1">
                                        {{ selectedEvent.organization.name }}
                                    </p>
                                    <h3 class="text-lg font-bold text-gray-800 leading-snug">{{ selectedEvent.title }}</h3>
                                </div>

                                <!-- Event meta -->
                                <div class="space-y-1.5 text-xs text-gray-500">
                                    <p class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ selectedEvent.start_formatted }}
                                    </p>
                                    <p v-if="selectedEvent.location_or_link" class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ selectedEvent.location_or_link }}
                                    </p>
                                    <p class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ selectedEvent.rsvp_count }} ahli akan hadir
                                    </p>
                                </div>

                                <!-- Current RSVP status -->
                                <div v-if="selectedEvent.my_rsvp" class="text-xs text-gray-500">
                                    Status semasa:
                                    <span :class="['ml-1 font-semibold px-2 py-0.5 rounded-full', rsvpConfig[selectedEvent.my_rsvp]?.classes]">
                                        {{ rsvpConfig[selectedEvent.my_rsvp]?.label }}
                                    </span>
                                </div>

                                <!-- RSVP Pill Buttons -->
                                <div v-if="!isSuperadmin" class="pt-1">
                                    <p class="text-xs font-semibold text-gray-500 mb-2">Pilih Status Kehadiran:</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            @click="submitRsvp('going')"
                                            :disabled="submitting"
                                            :class="[
                                                'flex flex-col items-center gap-1 p-3 rounded-2xl border-2 text-xs font-semibold transition-all',
                                                selectedEvent.my_rsvp === 'going'
                                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                                    : 'border-gray-100 hover:border-emerald-300 hover:bg-emerald-50 text-gray-600'
                                            ]"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Hadir
                                        </button>
                                        <button
                                            @click="submitRsvp('maybe')"
                                            :disabled="submitting"
                                            :class="[
                                                'flex flex-col items-center gap-1 p-3 rounded-2xl border-2 text-xs font-semibold transition-all',
                                                selectedEvent.my_rsvp === 'maybe'
                                                    ? 'border-amber-400 bg-amber-50 text-amber-700'
                                                    : 'border-gray-100 hover:border-amber-300 hover:bg-amber-50 text-gray-600'
                                            ]"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Mungkin
                                        </button>
                                        <button
                                            @click="submitRsvp('declined')"
                                            :disabled="submitting"
                                            :class="[
                                                'flex flex-col items-center gap-1 p-3 rounded-2xl border-2 text-xs font-semibold transition-all',
                                                selectedEvent.my_rsvp === 'declined'
                                                    ? 'border-red-400 bg-red-50 text-red-700'
                                                    : 'border-gray-100 hover:border-red-300 hover:bg-red-50 text-gray-600'
                                            ]"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Tidak
                                        </button>
                                    </div>
                                </div>

                                <div v-else class="rounded-2xl bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-700">
                                    Akaun Superadmin adalah untuk pengurusan program sahaja dan tidak merekod RSVP/kehadiran.
                                </div>

                                <!-- Google Calendar button -->
                                <a
                                    :href="selectedEvent.google_calendar_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl
                                           border border-gray-200 text-sm font-medium text-gray-600
                                           hover:bg-gray-50 transition-colors"
                                >
                                    <!-- Google Calendar icon (simplified) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                                    </svg>
                                    Tambah ke Google Calendar
                                </a>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
