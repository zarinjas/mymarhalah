<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ExportCsvButton from '@/Components/ExportCsvButton.vue';

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    overview: {
        type: Object,
        required: true,
    },
    managementLinks: {
        type: Object,
        required: true,
    },
});

const isManagementView = computed(() => props.organization?.slug === 'management');
const chartMax = computed(() => {
    const values = (props.overview?.program_chart ?? []).map((item) => Number(item.value ?? 0));
    return Math.max(1, ...values);
});

const createCampaignForm = useForm({
    title: '',
    description: '',
    target_amount: '',
    status: 'active',
});

function formatCurrency(value) {
    return new Intl.NumberFormat('ms-MY', {
        style: 'currency',
        currency: 'MYR',
        maximumFractionDigits: 0,
    }).format(value ?? 0);
}

function submitCampaign() {
    createCampaignForm.post(route('admin.campaigns.store'), {
        preserveScroll: true,
        onSuccess: () => createCampaignForm.reset(),
    });
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout>
        <template #header>Dashboard Pentadbir</template>

        <div class="relative mx-auto max-w-7xl px-4 py-4 md:px-6 md:py-6">
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden rounded-3xl">
                <div class="absolute -top-20 -left-20 h-56 w-56 rounded-full bg-indigo-200/40 blur-3xl"></div>
                <div class="absolute top-1/2 -right-10 h-56 w-56 rounded-full bg-emerald-200/40 blur-3xl"></div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">

                <section class="md:col-span-2 rounded-3xl border border-white/25 bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 p-4 text-white shadow-lg sm:p-5 md:p-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-indigo-200">Overview</p>
                    <h2 class="mt-2 text-xl font-black text-white sm:text-2xl">{{ organization?.name }}</h2>
                    <p class="mt-1 text-sm text-indigo-100/90">Pengurusan ahli dan program organisasi.</p>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-3 backdrop-blur-sm sm:p-4">
                            <p class="text-xs text-indigo-100/80">Jumlah Ahli</p>
                            <p class="mt-1 text-xl font-black text-white sm:text-2xl">{{ overview.total_members }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-3 backdrop-blur-sm sm:p-4">
                            <p class="text-xs text-indigo-100/80">Yuran Bulan Ini</p>
                            <p class="mt-1 text-xl font-black text-emerald-200">{{ formatCurrency(overview.fees_collected_month) }}</p>
                        </div>
                        <div class="col-span-2 rounded-2xl border border-white/15 bg-white/10 p-3 backdrop-blur-sm sm:p-4 md:col-span-1">
                            <p class="text-xs text-indigo-100/80">Jumlah Program</p>
                            <p class="mt-1 text-xl font-black text-cyan-200 sm:text-2xl">{{ overview.total_programs }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-gray-100 bg-white/90 p-4 shadow-sm backdrop-blur-sm sm:p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-400">Pengurusan</p>
                    <div class="mt-3 space-y-2">
                        <Link :href="managementLinks.create_program_url || managementLinks.create_event_url" class="block rounded-xl bg-gray-900 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-gray-800">
                            Add New Program
                        </Link>
                        <Link :href="managementLinks.create_program_url || managementLinks.create_event_url" class="block rounded-xl border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            Manage Program
                        </Link>
                        <Link v-if="isManagementView" :href="managementLinks.infaq_url" class="block rounded-xl border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            Manage Infaq
                        </Link>
                        <Link :href="managementLinks.information_hub_manage_url" class="block rounded-xl border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            Manage Info Hub
                        </Link>
                        <Link :href="managementLinks.usrah_manage_url" class="block rounded-xl border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            Manage Usrah
                        </Link>
                        <Link :href="managementLinks.broadcasts_url" class="block rounded-xl border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            Send Broadcast
                        </Link>
                        <Link :href="managementLinks.directory_url" class="block rounded-xl border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            View Directory
                        </Link>
                        <button
                            v-if="!isManagementView"
                            @click="document.getElementById('create-campaign')?.scrollIntoView({ behavior: 'smooth' })"
                            class="block w-full rounded-xl border border-gray-200 px-3 py-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Create Program
                        </button>
                    </div>
                </section>

                <section class="rounded-3xl border border-gray-100 bg-white/90 p-4 shadow-sm backdrop-blur-sm sm:p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-400">Program Breakdown</p>
                    <div class="mt-3 space-y-2">
                        <div
                            v-for="item in overview.program_chart"
                            :key="item.label"
                            class="rounded-2xl bg-gray-50 p-3"
                        >
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-700">
                                <span>{{ item.label }}</span>
                                <span>{{ item.value }}</span>
                            </div>
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                <div
                                    class="h-full rounded-full bg-indigo-500"
                                    :style="{ width: ((Number(item.value || 0) / chartMax) * 100) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-gray-100 bg-white/90 p-4 shadow-sm backdrop-blur-sm sm:p-5 md:col-span-3 lg:col-span-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-400">Members</p>
                            <p class="mt-1 text-sm text-gray-500">Eksport senarai ahli untuk analisis dan pelaporan.</p>
                        </div>
                        <ExportCsvButton :href="route('admin.members.export')" />
                    </div>
                </section>

                <section v-if="!isManagementView" id="create-campaign" class="rounded-3xl border border-gray-100 bg-white/90 p-4 shadow-sm backdrop-blur-sm sm:p-5 md:col-span-3 lg:col-span-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-400">Create Campaign</p>
                    <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitCampaign">
                        <div class="md:col-span-1">
                            <label class="mb-1 block text-xs font-semibold text-gray-500">Title</label>
                            <input v-model="createCampaignForm.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                            <p v-if="createCampaignForm.errors.title" class="mt-1 text-xs text-red-500">{{ createCampaignForm.errors.title }}</p>
                        </div>

                        <div class="md:col-span-1">
                            <label class="mb-1 block text-xs font-semibold text-gray-500">Target Amount</label>
                            <input v-model="createCampaignForm.target_amount" type="number" min="1" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                            <p v-if="createCampaignForm.errors.target_amount" class="mt-1 text-xs text-red-500">{{ createCampaignForm.errors.target_amount }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-gray-500">Description</label>
                            <textarea v-model="createCampaignForm.description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0"></textarea>
                        </div>

                        <div class="md:col-span-1">
                            <label class="mb-1 block text-xs font-semibold text-gray-500">Status</label>
                            <select v-model="createCampaignForm.status" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <div class="md:col-span-1 flex items-end">
                            <button
                                type="submit"
                                :disabled="createCampaignForm.processing"
                                class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                            >
                                {{ createCampaignForm.processing ? 'Menyimpan...' : 'Simpan Kempen' }}
                            </button>
                        </div>
                    </form>
                </section>

            </div>
        </div>
    </AppLayout>
</template>
