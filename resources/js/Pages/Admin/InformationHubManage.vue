<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    isSuperadmin: Boolean,
    defaultOrganizationId: Number,
    organizations: {
        type: Array,
        default: () => [],
    },
    announcements: {
        type: Array,
        default: () => [],
    },
    libraryItems: {
        type: Array,
        default: () => [],
    },
});

const announcementForm = useForm({
    organization_id: props.defaultOrganizationId,
    title: '',
    content: '',
    is_pinned: false,
    published_at: '',
});

const libraryForm = useForm({
    organization_id: props.defaultOrganizationId,
    title: '',
    description: '',
    category: 'Umum',
    pdf_file: null,
});

const showMemberForm = ref(false);
const memberForm = useForm({
    name: '',
    email: '',
    phone: '',
    dob: '',
    password: '',
});

const editingAnnouncementId = ref(null);
const editingLibraryId = ref(null);

const announcementEditForm = useForm({
    title: '',
    content: '',
    is_pinned: false,
    published_at: '',
});

const libraryEditForm = useForm({
    title: '',
    description: '',
    category: 'Umum',
    pdf_file: null,
});

function submitAnnouncement() {
    announcementForm.post(route('admin.hub.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => announcementForm.reset('title', 'content', 'is_pinned', 'published_at'),
    });
}

function submitLibrary() {
    libraryForm.post(route('admin.hub.library.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => libraryForm.reset('title', 'description', 'category', 'pdf_file'),
    });
}

function onPdfSelected(event) {
    libraryForm.pdf_file = event.target.files[0];
}

function toDateTimeLocal(value) {
    if (!value) return '';
    return value.replace(' ', 'T').slice(0, 16);
}

function startEditAnnouncement(item) {
    editingAnnouncementId.value = item.id;
    announcementEditForm.title = item.title ?? '';
    announcementEditForm.content = item.content ?? '';
    announcementEditForm.is_pinned = !!item.is_pinned;
    announcementEditForm.published_at = toDateTimeLocal(item.published_at);
}

function cancelEditAnnouncement() {
    editingAnnouncementId.value = null;
    announcementEditForm.reset();
}

function submitEditAnnouncement(item) {
    announcementEditForm.put(route('admin.hub.announcements.update', item.id), {
        preserveScroll: true,
        onSuccess: () => cancelEditAnnouncement(),
    });
}

function startEditLibrary(item) {
    editingLibraryId.value = item.id;
    libraryEditForm.title = item.title ?? '';
    libraryEditForm.description = item.description ?? '';
    libraryEditForm.category = item.category ?? 'Umum';
    libraryEditForm.pdf_file = null;
}

function cancelEditLibrary() {
    editingLibraryId.value = null;
    libraryEditForm.reset();
}

function onEditPdfSelected(event) {
    libraryEditForm.pdf_file = event.target.files[0];
}

function submitEditLibrary(item) {
    libraryEditForm.put(route('admin.hub.library.update', item.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => cancelEditLibrary(),
    });
}

function removeAnnouncement(id) {
    if (!confirm('Padam pengumuman ini?')) return;
    useForm({}).delete(route('admin.hub.announcements.destroy', id), { preserveScroll: true });
}

function togglePinned(id) {
    useForm({}).patch(route('admin.hub.announcements.pin', id), { preserveScroll: true });
}

function removeLibraryItem(id) {
    if (!confirm('Padam dokumen PDF ini?')) return;
    useForm({}).delete(route('admin.hub.library.destroy', id), { preserveScroll: true });
}

const inferredOrganization = computed(() => {
    if (!memberForm.dob) return null;

    const dob = new Date(memberForm.dob);
    if (Number.isNaN(dob.getTime())) return null;

    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const hasBirthdayPassed =
        today.getMonth() > dob.getMonth()
        || (today.getMonth() === dob.getMonth() && today.getDate() >= dob.getDate());

    if (!hasBirthdayPassed) {
        age -= 1;
    }

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
</script>

<template>
    <Head title="Information Hub Management" />

    <AppLayout>
        <template #header>Information Hub Management</template>

        <div class="mx-auto max-w-7xl px-4 py-6 md:px-6 space-y-6">
            <div v-if="$page.props.flash?.success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ $page.props.flash.success }}
            </div>

            <section v-if="isSuperadmin" class="rounded-3xl border border-gray-100 bg-white/90 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-gray-800">Members</h2>
                        <p class="mt-1 text-sm text-gray-500">Tambah ahli baharu secara manual.</p>
                    </div>
                    <button
                        type="button"
                        @click="showMemberForm = !showMemberForm"
                        class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800"
                    >
                        {{ showMemberForm ? 'Tutup Borang' : 'Add Member' }}
                    </button>
                </div>

                <form v-if="showMemberForm" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitMember">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Nama Penuh</label>
                        <input v-model="memberForm.name" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                        <p v-if="memberForm.errors.name" class="mt-1 text-xs text-red-600">{{ memberForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Email</label>
                        <input v-model="memberForm.email" type="email" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                        <p v-if="memberForm.errors.email" class="mt-1 text-xs text-red-600">{{ memberForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">No. Telefon</label>
                        <input v-model="memberForm.phone" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                        <p v-if="memberForm.errors.phone" class="mt-1 text-xs text-red-600">{{ memberForm.errors.phone }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Tarikh Lahir</label>
                        <input v-model="memberForm.dob" type="date" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                        <p v-if="memberForm.errors.dob" class="mt-1 text-xs text-red-600">{{ memberForm.errors.dob }}</p>
                        <p v-if="inferredOrganization" class="mt-1 text-xs font-semibold text-emerald-600">
                            Organisasi automatik: {{ inferredOrganization.name }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Password (Opsyenal)</label>
                        <input v-model="memberForm.password" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" placeholder="Kosongkan untuk guna default: password123">
                        <p v-if="memberForm.errors.password" class="mt-1 text-xs text-red-600">{{ memberForm.errors.password }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" :disabled="memberForm.processing" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                            {{ memberForm.processing ? 'Menyimpan...' : 'Simpan Ahli' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-gray-100 bg-white/90 p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-black text-gray-800">Pengumuman</h2>
                </div>

                <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitAnnouncement">
                    <div v-if="isSuperadmin" class="md:col-span-1">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Organisasi</label>
                        <select v-model="announcementForm.organization_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                            <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-1">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Tajuk</label>
                        <input v-model="announcementForm.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Kandungan</label>
                        <textarea v-model="announcementForm.content" rows="4" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Tarikh Terbit</label>
                        <input v-model="announcementForm.published_at" type="datetime-local" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_pinned" v-model="announcementForm.is_pinned" type="checkbox" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                        <label for="is_pinned" class="text-sm text-gray-600">Pin pengumuman ini</label>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" :disabled="announcementForm.processing" class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-60">
                            {{ announcementForm.processing ? 'Menyimpan...' : 'Terbitkan Pengumuman' }}
                        </button>
                    </div>
                </form>

                <div class="mt-5 space-y-3">
                    <article
                        v-for="item in announcements"
                        :key="item.id"
                        class="rounded-2xl border border-gray-100 bg-white p-4"
                        :class="item.is_pinned ? 'border-l-4 border-amber-400 bg-amber-50/40' : ''"
                    >
                        <template v-if="editingAnnouncementId === item.id">
                            <form class="space-y-3" @submit.prevent="submitEditAnnouncement(item)">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Tajuk</label>
                                    <input v-model="announcementEditForm.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Kandungan</label>
                                    <textarea v-model="announcementEditForm.content" rows="4" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required></textarea>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-gray-500">Tarikh Terbit</label>
                                        <input v-model="announcementEditForm.published_at" type="datetime-local" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input id="edit_is_pinned" v-model="announcementEditForm.is_pinned" type="checkbox" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                                        <label for="edit_is_pinned" class="text-sm text-gray-600">Pin pengumuman ini</label>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" :disabled="announcementEditForm.processing" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        {{ announcementEditForm.processing ? 'Updating...' : 'Update' }}
                                    </button>
                                    <button type="button" @click="cancelEditAnnouncement" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </template>
                        <div v-else class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ item.title }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ item.organization_name }} • {{ item.published_human || item.published_at || 'Draft' }}</p>
                                <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ item.content }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button @click="startEditAnnouncement(item)" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700">
                                    Edit
                                </button>
                                <button @click="togglePinned(item.id)" class="rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    {{ item.is_pinned ? 'Unpin' : 'Pin' }}
                                </button>
                                <button @click="removeAnnouncement(item.id)" class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="rounded-3xl border border-gray-100 bg-white/90 p-5 shadow-sm">
                <h2 class="text-lg font-black text-gray-800">Pustaka PDF</h2>

                <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitLibrary">
                    <div v-if="isSuperadmin" class="md:col-span-1">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Organisasi</label>
                        <select v-model="libraryForm.organization_id" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                            <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Tajuk PDF</label>
                        <input v-model="libraryForm.title" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" required>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Kategori</label>
                        <input v-model="libraryForm.category" type="text" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0" placeholder="Umum / SOP / Modul">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Deskripsi</label>
                        <textarea v-model="libraryForm.description" rows="3" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-gray-500">Fail PDF</label>
                        <input type="file" accept="application/pdf" @change="onPdfSelected" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-gray-700">
                        <p class="mt-1 text-xs text-gray-400">Maks 10MB. Simpanan dummy di storage tempatan untuk Phase 1.</p>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" :disabled="libraryForm.processing" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                            {{ libraryForm.processing ? 'Memuat naik...' : 'Muat Naik PDF' }}
                        </button>
                    </div>
                </form>

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
                                <div>
                                    <p class="text-sm font-bold text-gray-800">{{ item.title }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ item.organization_name }} • {{ item.category || 'Umum' }}</p>
                                    <p class="mt-2 text-sm text-gray-600">{{ item.description || '—' }}</p>
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
                </div>
            </section>

            <div>
                <Link :href="route('admin.dashboard')" class="text-sm font-semibold text-gray-500 hover:text-gray-700">← Kembali ke Dashboard Admin</Link>
            </div>
        </div>
    </AppLayout>
</template>
