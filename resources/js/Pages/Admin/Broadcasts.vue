<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    recentMessages: { type: Array, default: () => [] },
    usrahGroups: { type: Array, default: () => [] },
});

const form = useForm({
    title: '',
    content: '',
    target_criteria: 'all',
    usrah_group_id: '',
});

function submitBroadcast() {
    form.post(route('admin.broadcasts.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'content', 'target_criteria', 'usrah_group_id'),
    });
}
</script>

<template>
    <Head title="Broadcast Center" />

    <AppLayout>
        <template #header>Broadcast & Notification Engine</template>

        <div class="mx-auto max-w-7xl px-4 py-6 md:px-6 space-y-6">
            <div v-if="$page.props.flash?.success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ $page.props.flash.success }}
            </div>

            <section class="rounded-3xl border border-gray-100 bg-white/90 p-5 shadow-sm">
                <h2 class="text-lg font-black text-gray-800">Draft Broadcast</h2>
                <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitBroadcast">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Title</label>
                        <input v-model="form.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-0 focus:border-gray-500" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Content</label>
                        <textarea v-model="form.content" rows="4" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-0 focus:border-gray-500" required></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Target Audience</label>
                        <select v-model="form.target_criteria" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-0 focus:border-gray-500">
                            <option value="all">All Members</option>
                            <option value="unpaid_fees">Members with Unpaid Fees</option>
                            <option value="specific_usrah">Specific Usrah Group</option>
                        </select>
                    </div>

                    <div v-if="form.target_criteria === 'specific_usrah'">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Usrah Group</label>
                        <select v-model="form.usrah_group_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-0 focus:border-gray-500">
                            <option value="">Select group</option>
                            <option v-for="group in usrahGroups" :key="group.id" :value="group.id">{{ group.name }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                            {{ form.processing ? 'Sending...' : 'Send Broadcast' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-gray-100 bg-white/90 p-5 shadow-sm">
                <h2 class="text-lg font-black text-gray-800">Recent Broadcasts</h2>
                <div class="mt-3 space-y-2">
                    <article v-for="item in recentMessages" :key="item.id" class="rounded-2xl border border-gray-100 bg-white p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ item.title }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ item.organization_name }} • {{ item.target_criteria }}<span v-if="item.usrah_group_name"> • {{ item.usrah_group_name }}</span></p>
                            </div>
                            <span class="text-xs font-semibold" :class="item.sent_at ? 'text-emerald-700' : 'text-amber-700'">
                                {{ item.sent_at ? 'Sent' : 'Queued' }}
                            </span>
                        </div>
                    </article>

                    <div v-if="!recentMessages.length" class="rounded-2xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        Tiada broadcast lagi.
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
