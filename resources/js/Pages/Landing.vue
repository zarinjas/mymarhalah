<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuroraBackground from '@/Components/ui/AuroraBackground.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'));
};
</script>

<template>
    <Head title="MyMarhalah" />

    <AuroraBackground>
        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-3 py-6 sm:px-4 sm:py-10 md:px-8">
            <div class="grid w-full grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-stretch">
                <section class="hidden rounded-[32px] border border-white/10 bg-white/5 p-10 text-white backdrop-blur-sm lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <p class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-100">
                            Lifecycle Membership
                        </p>
                        <h1 class="mt-5 text-5xl font-black tracking-tight">MyMarhalah</h1>
                        <p class="mt-4 max-w-lg text-sm leading-relaxed text-slate-200">
                            Platform pengurusan keahlian berfasa untuk PKPIM, ABIM dan WADAH dengan automasi transisi umur.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-2xl border border-indigo-300/15 bg-indigo-400/10 p-3 backdrop-blur-sm">
                                <p class="text-[10px] font-semibold uppercase text-indigo-200">PKPIM</p>
                                <p class="mt-1 text-xs font-bold text-white">&lt; 20</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-300/15 bg-emerald-400/10 p-3 backdrop-blur-sm">
                                <p class="text-[10px] font-semibold uppercase text-emerald-200">ABIM</p>
                                <p class="mt-1 text-xs font-bold text-white">20 - 29</p>
                            </div>
                            <div class="rounded-2xl border border-amber-300/15 bg-amber-400/10 p-3 backdrop-blur-sm">
                                <p class="text-[10px] font-semibold uppercase text-amber-200">WADAH</p>
                                <p class="mt-1 text-xs font-bold text-white">30+</p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-white/10 bg-slate-950/30 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Unified access</p>
                            <div class="mt-3 grid grid-cols-3 gap-2">
                                <div class="h-16 rounded-2xl bg-gradient-to-b from-cyan-400/20 to-transparent"></div>
                                <div class="h-16 rounded-2xl bg-gradient-to-b from-emerald-400/20 to-transparent"></div>
                                <div class="h-16 rounded-2xl bg-gradient-to-b from-violet-400/20 to-transparent"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="relative overflow-hidden rounded-[30px] border border-white/55 bg-white/90 p-5 shadow-[0_22px_60px_rgba(2,6,23,0.35)] backdrop-blur-md sm:p-7 lg:p-8">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-white/40 via-white/25 to-white/15"></div>
                    <div class="relative z-10 mb-6 flex gap-2">
                        <span class="h-3 w-3 rounded-full bg-rose-300/90"></span>
                        <span class="h-3 w-3 rounded-full bg-amber-300/90"></span>
                        <span class="h-3 w-3 rounded-full bg-emerald-300/90"></span>
                    </div>

                    <div class="relative z-10 mb-5 lg:hidden">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500">Lifecycle Membership</p>
                        <h1 class="mt-1 text-3xl font-black text-slate-900">MyMarhalah</h1>
                    </div>

                    <div class="relative z-10">
                        <h2 class="text-3xl font-black text-slate-900">Log Masuk</h2>
                        <p class="mt-1 text-sm text-slate-500">Akses papan pemuka mengikut peranan anda.</p>
                    </div>

                    <form class="relative z-10 mt-6 space-y-4" @submit.prevent="submit">
                        <div class="relative">
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                class="peer w-full rounded-3xl border border-slate-200/90 bg-slate-100/85 px-5 pt-5 pb-2.5 text-base text-slate-900 placeholder-transparent focus:border-cyan-400 focus:bg-white focus:ring-0"
                                placeholder="Email"
                                required
                            >
                            <label
                                for="email"
                                class="absolute left-5 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-placeholder-shown:font-medium peer-focus:top-2 peer-focus:text-[11px] peer-focus:font-semibold peer-focus:text-cyan-600"
                            >
                                Email
                            </label>
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                class="peer w-full rounded-3xl border border-slate-200/90 bg-slate-100/85 px-5 pt-5 pb-2.5 text-base text-slate-900 placeholder-transparent focus:border-cyan-400 focus:bg-white focus:ring-0"
                                placeholder="Password"
                                required
                            >
                            <label
                                for="password"
                                class="absolute left-5 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-placeholder-shown:font-medium peer-focus:top-2 peer-focus:text-[11px] peer-focus:font-semibold peer-focus:text-cyan-600"
                            >
                                Password
                            </label>
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-slate-500">
                            <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-400">
                            Ingat saya
                        </label>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-3xl bg-slate-950 px-4 py-3.5 text-lg font-semibold text-white shadow-lg shadow-slate-900/25 transition hover:bg-slate-900 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Memproses...' : 'Log Masuk' }}
                        </button>

                        <p class="text-center text-sm text-slate-500">
                            Pengguna baru?
                            <Link :href="route('register')" class="font-semibold text-cyan-700 hover:text-cyan-600">
                                Daftar akaun
                            </Link>
                        </p>
                    </form>
                </section>
            </div>
        </div>
    </AuroraBackground>
</template>
