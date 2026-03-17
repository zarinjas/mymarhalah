<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    isSuperadmin: Boolean,
    defaultOrganizationId: Number,
    organizations: {
        type: Array,
        default: () => [],
    },
    libraryItems: {
        type: Array,
        default: () => [],
    },
});

const libraryForm = useForm({
    organization_id: props.defaultOrganizationId,
    title: '',
    description: '',
    category: 'Umum',
    pdf_file: null,
    cover_image: null,
});

const editingLibraryId = ref(null);
const libraryEditForm = useForm({
    title: '',
    description: '',
    category: 'Umum',
    pdf_file: null,
    cover_image: null,
});

function submitLibrary() {
    libraryForm.post(route('admin.hub.library.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => libraryForm.reset('title', 'description', 'category', 'pdf_file', 'cover_image'),
    });
}

function onPdfSelected(event) {
    libraryForm.pdf_file = event.target.files[0];
}

function onCoverSelected(event) {
    libraryForm.cover_image = event.target.files[0];
}

function startEditLibrary(item) {
    editingLibraryId.value = item.id;
    libraryEditForm.title = item.title ?? '';
    libraryEditForm.description = item.description ?? '';
    libraryEditForm.category = item.category ?? 'Umum';
    libraryEditForm.pdf_file = null;
    libraryEditForm.cover_image = null;
}

function cancelEditLibrary() {
    editingLibraryId.value = null;
    libraryEditForm.reset();
}

function onEditPdfSelected(event) {
    libraryEditForm.pdf_file = event.target.files[0];
}

function onEditCoverSelected(event) {
    libraryEditForm.cover_image = event.target.files[0];
}

function submitEditLibrary(item) {
    libraryEditForm.put(route('admin.hub.library.update', item.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => cancelEditLibrary(),
    });
}

function removeLibraryItem(id) {
    if (!confirm('Padam dokumen PDF ini?')) return;
    useForm({}).delete(route('admin.hub.library.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Pustaka Digital Management" />

    <AppLayout>
        <template #header>Pustaka Digital Management</template>

        <div class="mx-auto max-w-7xl px-4 py-6 md:px-6 space-y-6">
            <div v-if="$page.props.flash?.success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ $page.props.flash.success }}
            </div>

            <section class="rounded-3xl border border-gray-100 bg-white/90 p-5 shadow-sm">
                <h2 class="text-lg font-black text-gray-800">Muat Naik Buku Pustaka</h2>

                <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitLibrary">
                    <div v-if="isSuperadmin" class="md:col-span-1">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Organisasi</label>
                        <select v-model="libraryForm.organization_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                            <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Tajuk Buku</label>
                        <input v-model="libraryForm.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Kategori</label>
                        <input v-model="libraryForm.category" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" placeholder="Umum / Tarbiah / Modul">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Deskripsi</label>
                        <textarea v-model="libraryForm.description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Fail PDF</label>
                        <input type="file" accept="application/pdf" @change="onPdfSelected" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700">
                        <p v-if="libraryForm.errors.pdf_file" class="mt-1 text-xs text-red-500">{{ libraryForm.errors.pdf_file }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Cover Image</label>
                        <input type="file" accept="image/*" @change="onCoverSelected" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700">
                        <p v-if="libraryForm.errors.cover_image" class="mt-1 text-xs text-red-500">{{ libraryForm.errors.cover_image }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" :disabled="libraryForm.processing" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                            {{ libraryForm.processing ? 'Memuat naik...' : 'Muat Naik Buku' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-gray-100 bg-white/90 p-5 shadow-sm">
                <h2 class="text-lg font-black text-gray-800">Senarai Buku</h2>

                <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <article v-for="item in libraryItems" :key="item.id" class="rounded-2xl border border-gray-100 bg-white p-4">
                        <template v-if="editingLibraryId === item.id">
                            <form class="space-y-3" @submit.prevent="submitEditLibrary(item)">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Tajuk PDF</label>
                                    <input v-model="libraryEditForm.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Kategori</label>
                                    <input v-model="libraryEditForm.category" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Deskripsi</label>
                                    <textarea v-model="libraryEditForm.description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0"></textarea>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Ganti PDF (opsyenal)</label>
                                    <input type="file" accept="application/pdf" @change="onEditPdfSelected" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Ganti Cover (opsyenal)</label>
                                    <input type="file" accept="image/*" @change="onEditCoverSelected" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700">
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" :disabled="libraryEditForm.processing" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        {{ libraryEditForm.processing ? 'Updating...' : 'Update' }}
                                    </button>
                                    <button type="button" @click="cancelEditLibrary" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </template>
                        <template v-else>
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex gap-3">
                                    <img v-if="item.cover_image_path" :src="item.cover_image_path" alt="cover" class="h-20 w-14 rounded-md object-cover border border-gray-200">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ item.title }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ item.organization_name }} • {{ item.category || 'Umum' }}</p>
                                        <p class="mt-2 text-sm text-gray-600">{{ item.description || '—' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="startEditLibrary(item)" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        Edit
                                    </button>
                                    <button @click="removeLibraryItem(item.id)" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        Delete
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <a :href="item.file_path" target="_blank" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    Read PDF
                                </a>
                            </div>
                        </template>
                    </article>
                    <div v-if="!libraryItems.length" class="col-span-full rounded-2xl bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        Tiada buku dalam pustaka digital.
                    </div>
                </div>
            </section>

            <div>
                <Link :href="route('admin.dashboard')" class="text-sm font-semibold text-gray-500 hover:text-gray-700">← Kembali ke Dashboard Admin</Link>
            </div>
        </div>
    </AppLayout>
</template>
