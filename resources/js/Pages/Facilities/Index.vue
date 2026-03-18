<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    facilities: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Tempahan Ruang" />

    <AppLayout>
        <template #header>Tempahan Ruang</template>

        <div class="mx-auto max-w-7xl px-4 py-6 md:px-6">
            <div class="mb-4">
                <h1 class="text-2xl font-black text-gray-900">Senarai Ruang</h1>
                <p class="mt-1 text-sm text-gray-500">Pilih ruang dan buat tempahan mengikut slot masa yang tersedia.</p>
            </div>

            <div v-if="facilities.length === 0" class="rounded-2xl border border-gray-100 bg-white p-5 text-sm text-gray-500">
                Tiada ruang aktif tersedia untuk organisasi anda sekarang.
            </div>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="facility in facilities" :key="facility.id" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ facility.organization_name }}</p>
                    <h2 class="mt-1 text-lg font-black text-gray-900">{{ facility.name }}</h2>
                    <p class="mt-2 text-sm text-gray-600">{{ facility.description || '—' }}</p>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-500">
                        <p>Lokasi: <span class="font-semibold text-gray-700">{{ facility.location || '—' }}</span></p>
                        <p>Kapasiti: <span class="font-semibold text-gray-700">{{ facility.capacity || '—' }}</span></p>
                        <p>Jenis: <span class="font-semibold text-gray-700">{{ facility.type }}</span></p>
                        <p>Harga: <span class="font-semibold text-gray-700">RM {{ Number(facility.price_per_unit).toFixed(2) }}</span></p>
                    </div>

                    <Link
                        :href="route('member.facilities.show', facility.id)"
                        class="mt-4 inline-flex rounded-xl bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                    >
                        Lihat & Tempah
                    </Link>
                </article>
            </div>
        </div>
    </AppLayout>
</template>
