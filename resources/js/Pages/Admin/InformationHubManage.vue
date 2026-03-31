<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Simplified debouncer since we already removed lodash earlier
function customDebounce(fn, wait) {
    let timer;
    return function (...args) {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
}

const props = defineProps({
    isSuperadmin: Boolean,
    defaultOrganizationId: Number,
    organizations: {
        type: Array,
        default: () => [],
    },
    members: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', organization_id: '', role: '' }),
    },
});

// ─── Add Member Form ─────────────────────────────────────────────────────────

const showMemberForm = ref(false);
const memberForm = useForm({
    name: '',
    email: '',
    phone: '',
    dob: '',
    password: '',
});

const inferredOrganization = computed(() => {
    if (!memberForm.dob) return null;
    const dob = new Date(memberForm.dob);
    if (Number.isNaN(dob.getTime())) return null;

    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const hasBirthdayPassed = today.getMonth() > dob.getMonth() || (today.getMonth() === dob.getMonth() && today.getDate() >= dob.getDate());
    if (!hasBirthdayPassed) age -= 1;

    return props.organizations.find((organization) => {
        const minAge = Number(organization.min_age ?? 0);
        const maxAge = organization.max_age === null ? null : Number(organization.max_age);
        if (Number.isNaN(minAge)) return false;
        if (maxAge !== null && Number.isNaN(maxAge)) return false;
        return age >= minAge && (maxAge === null || age <= maxAge);
    }) ?? null;
});

function submitMember() {
    memberForm.post(route('superadmin.members.store'), {
        preserveScroll: true,
        onSuccess: () => {
            memberForm.reset();
            showMemberForm.value = false;
        },
    });
}

// ─── Filters & Search ────────────────────────────────────────────────────────

const searchQuery = ref(props.filters?.search ?? '');
const organizationIdFilter = ref(props.filters?.organization_id ?? '');
const roleFilter = ref(props.filters?.role ?? '');

watch([searchQuery, organizationIdFilter, roleFilter], customDebounce(([newSearch, newOrg, newRole]) => {
    router.get(
        route('admin.hub.manage'),
        { search: newSearch, organization_id: newOrg, role: newRole },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));

// ─── Update Role ─────────────────────────────────────────────────────────────

const updatingUserId = ref(null);

function updateRole(userId, newRole) {
    if (!confirm(`Sahkan penukaran peranan kepada ${newRole}?`)) return;
    updatingUserId.value = userId;

    router.patch(route('admin.hub.members.role.update', userId), { role: newRole }, {
        preserveScroll: true,
        onFinish: () => { updatingUserId.value = null; }
    });
}
</script>

<template>
    <Head title="Pengurusan Ahli" />

    <AppLayout>
        <template #header>Pengurusan Ahli</template>

        <div class="mx-auto max-w-7xl px-4 py-6 md:px-6 space-y-8">
            <div v-if="$page.props.flash?.success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm transition-all duration-300">
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $page.props.flash.success }}
                </span>
            </div>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-gray-900">Senarai Ahli & Pengguna</h2>
                    <p class="text-sm font-medium text-gray-500 mt-1">Urus keahlian, organisasi, dan peranan sistem.</p>
                </div>
                <button
                    v-if="isSuperadmin"
                    @click="showMemberForm = !showMemberForm"
                    class="inline-flex items-center gap-2 rounded-2xl bg-gray-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-gray-800 transition-all hover:-translate-y-0.5"
                >
                    <svg v-if="!showMemberForm" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    {{ showMemberForm ? 'Tutup Borang' : 'Tambah Ahli Baharu' }}
                </button>
            </div>

            <!-- Add Member Form -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <section v-if="showMemberForm && isSuperadmin" class="relative rounded-3xl border border-gray-100 bg-white p-6 shadow-xl shadow-gray-200/40">
                    <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitMember">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Nama Penuh</label>
                            <input v-model="memberForm.name" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-gray-900 focus:ring-gray-900 transition-all" required>
                            <p v-if="memberForm.errors.name" class="mt-1 text-xs text-red-600">{{ memberForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Email</label>
                            <input v-model="memberForm.email" type="email" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-gray-900 focus:ring-gray-900 transition-all" required>
                            <p v-if="memberForm.errors.email" class="mt-1 text-xs text-red-600">{{ memberForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">No. Telefon</label>
                            <input v-model="memberForm.phone" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-gray-900 focus:ring-gray-900 transition-all">
                            <p v-if="memberForm.errors.phone" class="mt-1 text-xs text-red-600">{{ memberForm.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Tarikh Lahir</label>
                            <input v-model="memberForm.dob" type="date" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-gray-900 focus:ring-gray-900 transition-all" required>
                            <p v-if="memberForm.errors.dob" class="mt-1 text-xs text-red-600">{{ memberForm.errors.dob }}</p>
                            <p v-if="inferredOrganization" class="mt-1.5 text-xs font-semibold text-emerald-600 flex items-center gap-1">
                                <svg class="w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Disusun ke: {{ inferredOrganization.name }}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Password (Opsyenal)</label>
                            <input v-model="memberForm.password" type="text" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-gray-900 focus:ring-gray-900 transition-all" placeholder="Kosongkan untuk katalaluan lalai: password123">
                            <p v-if="memberForm.errors.password" class="mt-1 text-xs text-red-600">{{ memberForm.errors.password }}</p>
                        </div>
                        <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                            <button type="button" @click="showMemberForm = false" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">Batal</button>
                            <button type="submit" :disabled="memberForm.processing" class="rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 shadow-sm transition-all disabled:opacity-60">
                                {{ memberForm.processing ? 'Menyimpan...' : 'Simpan Ahli' }}
                            </button>
                        </div>
                    </form>
                </section>
            </transition>

            <!-- Filters Section -->
            <div class="flex flex-col md:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input v-model="searchQuery" type="text" placeholder="Cari nama, email, no telefon..." class="pl-10 w-full rounded-2xl border-gray-200 text-sm focus:border-gray-900 focus:ring-gray-900 shadow-sm transition-colors">
                </div>
                
                <div v-if="isSuperadmin" class="relative md:w-48 shrink-0">
                    <select v-model="organizationIdFilter" class="w-full rounded-2xl border-gray-200 text-sm focus:border-gray-900 focus:ring-gray-900 shadow-sm transition-colors">
                        <option value="">Semua Organisasi</option>
                        <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                    </select>
                </div>
                
                <div class="relative md:w-48 shrink-0">
                    <select v-model="roleFilter" class="w-full rounded-2xl border-gray-200 text-sm focus:border-gray-900 focus:ring-gray-900 shadow-sm transition-colors">
                        <option value="">Semua Peranan</option>
                        <option value="Admin">Admin</option>
                        <option value="Member">Member / Ahli</option>
                    </select>
                </div>
            </div>

            <!-- Members Table -->
            <div class="rounded-3xl border border-gray-100 bg-white overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/70 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-bold">Pengguna</th>
                                <th scope="col" class="px-6 py-4 font-bold">Cawangan & Organisasi</th>
                                <th scope="col" class="px-6 py-4 font-bold">Peranan</th>
                                <th scope="col" class="px-6 py-4 font-bold">Status</th>
                                <th scope="col" class="px-6 py-4 font-bold text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-if="members.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">Tiada ahli dijumpai.</td>
                            </tr>
                            <tr v-for="member in members.data" :key="member.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 shrink-0 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 font-bold border border-gray-200">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ member.name }}</div>
                                            <div class="text-xs text-gray-500">{{ member.email }} • {{ member.phone || 'Tiada No.' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        {{ member.organization_name }}
                                    </span>
                                    <div class="text-[11px] text-gray-400 mt-1 uppercase font-semibold">Caw: {{ member.branch_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select 
                                        :value="member.role" 
                                        @change="updateRole(member.id, $event.target.value)"
                                        :disabled="updatingUserId === member.id || member.role === 'Superadmin'"
                                        class="h-8 py-0 pl-3 pr-8 rounded-lg text-xs font-bold border-gray-200 focus:border-gray-900 focus:ring-gray-900 transition-colors cursor-pointer disabled:opacity-50"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': member.role === 'Admin',
                                            'bg-blue-50 text-blue-700 border-blue-200': member.role === 'Member' || member.role === 'User',
                                            'bg-yellow-50 text-yellow-700 border-yellow-200': member.role === 'Superadmin'
                                        }"
                                    >
                                        <option value="Member" class="text-blue-700">Member</option>
                                        <option value="Admin" class="text-emerald-700">Admin</option>
                                        <option v-if="member.role === 'Superadmin'" value="Superadmin" disabled>Superadmin</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 hover:bg-gray-100 transition-colors" title="Lihat Profil">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="members.last_page > 1" class="flex justify-center mt-6 pb-6 gap-2">
                <a
                    v-for="link in members.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="[
                        'inline-flex items-center justify-center h-10 min-w-10 px-3 rounded-2xl text-sm font-bold transition-all',
                        link.active
                            ? 'bg-gray-900 text-white shadow-md'
                            : link.url
                                ? 'bg-white border text-gray-600 hover:border-gray-900 shadow-sm'
                                : 'bg-transparent text-gray-300 pointer-events-none',
                    ]"
                />
            </div>

            <!-- Back navigation (If admin) -->
            <div>
                <Link :href="route('admin.dashboard')" class="text-sm font-bold text-gray-500 hover:text-gray-900 transition-colors inline-block pb-6">
                    ← Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
