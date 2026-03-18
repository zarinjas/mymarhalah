<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AccernityCard from '@/Components/ui/AccernityCard.vue';
import AuroraBackground from '@/Components/ui/AuroraBackground.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuroraBackground>
        <Head title="Log in" />

        <div class="relative z-10 mx-auto flex min-h-screen w-full max-w-7xl flex-col px-3 py-5 sm:px-4 sm:py-6 md:px-8 md:py-10 lg:flex-row lg:items-center lg:gap-8">

            <section class="hidden flex-1 lg:block">
                <div class="max-w-xl">
                    <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200 backdrop-blur-sm">
                        MyMarhalah
                    </p>
                    <h1 class="mt-5 text-4xl font-black leading-tight text-white xl:text-5xl">
                        Komuniti Islamik,
                        <span class="bg-gradient-to-r from-emerald-300 to-cyan-200 bg-clip-text text-transparent">lebih teratur & berdaya.</span>
                    </h1>
                    <p class="mt-4 text-sm leading-relaxed text-slate-300">
                        Urus program, infaq, pustaka dan perkembangan ahli dalam satu platform moden.
                    </p>

                    <div class="mt-8 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs text-slate-300">Program</p>
                            <p class="mt-1 text-xl font-black text-white">Live</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs text-slate-300">Infaq</p>
                            <p class="mt-1 text-xl font-black text-white">Smart</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-xs text-slate-300">Members</p>
                            <p class="mt-1 text-xl font-black text-white">Connected</p>
                        </div>
                    </div>

                    <div class="mt-8 rounded-3xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between text-xs text-slate-300">
                            <span>Platform Focus</span>
                            <span class="rounded-full bg-cyan-400/15 px-2 py-1 text-cyan-200">Aurora Background</span>
                        </div>
                        <div class="mt-3 h-24 overflow-hidden rounded-2xl border border-white/10 bg-slate-950/60 p-3">
                            <div class="grid h-full grid-cols-3 gap-2">
                                <div class="rounded-xl bg-gradient-to-b from-emerald-400/20 to-transparent"></div>
                                <div class="rounded-xl bg-gradient-to-b from-indigo-400/20 to-transparent"></div>
                                <div class="rounded-xl bg-gradient-to-b from-cyan-400/20 to-transparent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="w-full lg:w-[460px]">
                <AccernityCard>
                    <div class="mb-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-100">Welcome Back</p>
                        <h2 class="mt-1 text-2xl font-black text-white sm:text-3xl">Sign in to continue</h2>
                        <p class="mt-1 text-sm text-slate-300">Masukkan butiran akaun anda untuk akses dashboard.</p>
                    </div>

                    <div v-if="status" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel for="email" value="Email" class="!text-slate-200" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full border-white/15 bg-white/10 text-white placeholder:text-slate-300/80 focus:border-cyan-300 focus:ring-cyan-300"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@domain.com"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Password" class="!text-slate-200" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full border-white/15 bg-white/10 text-white placeholder:text-slate-300/80 focus:border-cyan-300 focus:ring-cyan-300"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-2">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="text-sm text-slate-300">Remember me</span>
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-cyan-200 hover:text-cyan-100"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <PrimaryButton
                            class="w-full justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-black/35 hover:bg-slate-900"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Signing in...' : 'Sign in' }}
                        </PrimaryButton>

                        <p class="text-center text-sm text-slate-300">
                            New here?
                            <Link :href="route('register')" class="font-semibold text-cyan-200 hover:text-cyan-100">
                                Create account
                            </Link>
                        </p>
                    </form>
                </AccernityCard>
            </section>
        </div>
    </AuroraBackground>
</template>
