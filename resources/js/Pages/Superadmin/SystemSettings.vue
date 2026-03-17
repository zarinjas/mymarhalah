<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    systemLogoPath: {
        type: String,
        default: null,
    },
    canManageSystemLogo: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    system_logo: null,
});

function uploadSystemLogo() {
    form.post(route('superadmin.settings.system-logo.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset('system_logo'),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="MyMarhalah Settings" />

        <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 md:px-6">
            <div>
                <h1 class="text-2xl font-black text-gray-900">MyMarhalah Settings</h1>
                <p class="mt-1 text-sm text-gray-500">Tetapan peringkat sistem seperti logo rasmi MyMarhalah.</p>
            </div>

            <div v-if="$page.props.flash?.success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ $page.props.flash.success }}
            </div>

            <div v-if="$page.props.flash?.error" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $page.props.flash.error }}
            </div>

            <div v-if="!canManageSystemLogo" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Tetapan ini memerlukan migration baru. Jalankan <strong>php artisan migrate</strong> dahulu.
            </div>

            <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-gray-800">Logo Sistem</h2>
                <p class="mt-1 text-xs text-gray-500">Cadangan saiz: <strong>512 × 512px</strong>, format PNG/SVG dengan latar transparen.</p>

                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-24 w-24 items-center justify-center rounded-2xl border border-gray-200 bg-gray-50">
                        <img v-if="systemLogoPath" :src="systemLogoPath" alt="System Logo" class="h-20 w-20 object-contain">
                        <span v-else class="text-xs font-semibold text-gray-400">No logo</span>
                    </div>

                    <form class="flex-1 space-y-2" @submit.prevent="uploadSystemLogo">
                        <input
                            type="file"
                            accept="image/*"
                            :disabled="!canManageSystemLogo"
                            @change="form.system_logo = $event.target.files[0]"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700"
                        >
                        <p v-if="form.errors.system_logo" class="text-xs text-red-500">{{ form.errors.system_logo }}</p>
                        <button
                            type="submit"
                            :disabled="form.processing || !canManageSystemLogo"
                            class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Memuat naik...' : 'Simpan Logo MyMarhalah' }}
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
