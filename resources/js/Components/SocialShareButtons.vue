<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    url: {
        type: String,
        default: '',
    },
    text: {
        type: String,
        default: '',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const copied = ref(false);

const resolvedUrl = computed(() => {
    if (props.url) {
        return props.url;
    }

    if (typeof window !== 'undefined') {
        return window.location.href;
    }

    return '';
});

const shareText = computed(() => {
    const extra = props.text ? `\n${props.text}` : '';
    return `${props.title}${extra}\n${resolvedUrl.value}`.trim();
});

const whatsappUrl = computed(() =>
    `https://wa.me/?text=${encodeURIComponent(shareText.value)}`
);

const telegramUrl = computed(() =>
    `https://t.me/share/url?url=${encodeURIComponent(resolvedUrl.value)}&text=${encodeURIComponent(props.title)}`
);

const facebookUrl = computed(() =>
    `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(resolvedUrl.value)}`
);

async function shareNative() {
    if (typeof navigator === 'undefined' || !navigator.share) {
        return;
    }

    try {
        await navigator.share({
            title: props.title,
            text: props.text || undefined,
            url: resolvedUrl.value,
        });
    } catch {
        // User cancel or platform rejection is safe to ignore.
    }
}

async function copyLink() {
    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(resolvedUrl.value);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = resolvedUrl.value;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        copied.value = true;
        window.setTimeout(() => {
            copied.value = false;
        }, 1400);
    } catch {
        // no-op
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2" :class="compact ? 'text-[11px]' : ''">
        <button
            type="button"
            class="rounded-xl border border-gray-200 bg-white px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50"
            @click="shareNative"
        >
            Share
        </button>

        <a
            :href="whatsappUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 font-semibold text-emerald-700 hover:bg-emerald-100"
        >
            WhatsApp
        </a>

        <a
            :href="telegramUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 font-semibold text-sky-700 hover:bg-sky-100"
        >
            Telegram
        </a>

        <a
            :href="facebookUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 font-semibold text-indigo-700 hover:bg-indigo-100"
        >
            Facebook
        </a>

        <button
            type="button"
            class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-100"
            @click="copyLink"
        >
            {{ copied ? 'Copied!' : 'Copy Link' }}
        </button>
    </div>
</template>
