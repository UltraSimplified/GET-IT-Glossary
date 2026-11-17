<script setup lang="ts">
import { Mic, Square, Trash2, X } from '@lucide/vue';
import { onBeforeUnmount, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import { postJson } from '@/lib/http';

/**
 * Records a short clip of the term said aloud with the computer's
 * microphone, uploads it straight away and hands back its address. The
 * term itself only changes when the form is saved.
 *
 * Recording starts after a count-in of four beats, shown and clicked, so
 * the speaker knows exactly when to begin.
 */
const {
    id,
    describedby,
    invalid = false,
} = defineProps<{
    id: string;
    describedby?: string;
    invalid?: boolean;
}>();

const url = defineModel<string>({ required: true });

const { t } = useTranslations();

const MAX_SECONDS = 10;

const BEATS = 4;
const BEAT_MS = 600;

// The first format the browser can record in: Opus in WebM (Chrome, Edge,
// Firefox) or AAC in MP4 (Safari).
const FORMATS = [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/mp4',
    'audio/ogg;codecs=opus',
];

const supported =
    typeof window !== 'undefined' &&
    'MediaRecorder' in window &&
    Boolean(navigator.mediaDevices?.getUserMedia);

const state = ref<'idle' | 'counting' | 'recording' | 'saving'>('idle');
const beat = ref(0);
const seconds = ref(0);
const problem = ref<string | null>(null);
const recordedNow = ref(false);

let recorder: MediaRecorder | null = null;
let stream: MediaStream | null = null;
let chunks: Blob[] = [];
let ticker: ReturnType<typeof setInterval> | undefined;
let countdown: ReturnType<typeof setTimeout> | undefined;
let clicks: AudioContext | null = null;
let discard = false;

function extension(type: string): string {
    if (type.includes('mp4')) {
        return 'm4a';
    }

    return type.includes('ogg') ? 'ogg' : 'webm';
}

async function start() {
    problem.value = null;

    // Made inside the click, so browsers let it sound the count-in.
    clicks = new AudioContext();

    // The microphone first, so a permission prompt can't break the count.
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch {
        problem.value = t('pronunciation-denied');
        closeClicks();

        return;
    }

    state.value = 'counting';
    beat.value = 0;
    countIn();
}

/** One beat at a time; recording begins when the fourth has passed. */
function countIn() {
    if (beat.value === BEATS) {
        closeClicks();
        record();

        return;
    }

    beat.value++;
    click(beat.value === 1);
    countdown = setTimeout(countIn, BEAT_MS);
}

/** A short tick, higher on the first beat, like a metronome. */
function click(accent: boolean) {
    if (!clicks) {
        return;
    }

    const now = clicks.currentTime;
    const tone = clicks.createOscillator();
    const volume = clicks.createGain();

    tone.frequency.value = accent ? 1320 : 880;
    volume.gain.setValueAtTime(0.3, now);
    volume.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
    tone.connect(volume).connect(clicks.destination);
    tone.start(now);
    tone.stop(now + 0.09);
}

function closeClicks() {
    void clicks?.close();
    clicks = null;
}

function record() {
    if (!stream) {
        return;
    }

    const format = FORMATS.find((type) => MediaRecorder.isTypeSupported(type));
    recorder = new MediaRecorder(
        stream,
        format ? { mimeType: format } : undefined,
    );
    chunks = [];
    discard = false;

    recorder.addEventListener('dataavailable', (event) => {
        if (event.data.size > 0) {
            chunks.push(event.data);
        }
    });
    recorder.addEventListener('stop', () => {
        if (!discard) {
            void upload(recorder?.mimeType || format || 'audio/webm');
        }
    });

    recorder.start();
    state.value = 'recording';
    seconds.value = 0;
    ticker = setInterval(() => {
        seconds.value++;

        if (seconds.value >= MAX_SECONDS) {
            stop();
        }
    }, 1000);
}

function stop() {
    clearInterval(ticker);
    clearTimeout(countdown);
    closeClicks();

    if (recorder && recorder.state !== 'inactive') {
        recorder.stop();
    }

    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
}

/** Stops the count-in before anything is recorded. */
function cancel() {
    stop();
    beat.value = 0;
    state.value = 'idle';
}

async function upload(type: string) {
    state.value = 'saving';

    const data = new FormData();
    data.append(
        'file',
        new Blob(chunks, { type }),
        `pronunciation.${extension(type)}`,
    );

    try {
        const result = await postJson<{ url: string }>(
            '/upload/pronunciation',
            data,
        );
        url.value = result.url;
        recordedNow.value = true;
    } catch (failure) {
        problem.value = t('pronunciation-failed', {
            message:
                failure instanceof Error ? failure.message : String(failure),
        });
    } finally {
        state.value = 'idle';
    }
}

function toggle() {
    if (state.value === 'recording') {
        stop();
    } else if (state.value === 'counting') {
        cancel();
    } else {
        void start();
    }
}

function remove() {
    url.value = '';
    recordedNow.value = false;
}

// Leaving the page mid-recording frees the microphone and keeps nothing.
onBeforeUnmount(() => {
    discard = true;
    stop();
});
</script>

<template>
    <div
        class="grid gap-3 rounded-lg border bg-card p-4"
        :class="{ 'border-destructive': invalid }"
    >
        <p v-if="!supported" class="text-sm text-muted-foreground">
            {{ t('pronunciation-unsupported') }}
        </p>

        <template v-else>
            <!-- An explicit height: Firefox otherwise gives the player a 150px box. -->
            <audio
                v-if="url && state === 'idle'"
                :src="url"
                controls
                preload="metadata"
                class="block h-10 w-full max-w-sm"
            ></audio>
            <p
                v-else-if="state === 'idle'"
                class="text-sm text-muted-foreground"
            >
                {{ t('pronunciation-none') }}
            </p>

            <div
                v-if="state === 'counting'"
                role="status"
                class="flex flex-wrap items-center gap-3"
            >
                <span class="text-sm font-semibold">{{
                    t('pronunciation-get-ready')
                }}</span>
                <ol class="flex gap-2" aria-hidden="true">
                    <li
                        v-for="n in BEATS"
                        :key="n"
                        class="grid size-9 place-items-center rounded-full border-2 text-base font-bold transition-colors"
                        :class="
                            n === beat
                                ? 'border-primary bg-primary text-primary-foreground'
                                : n < beat
                                  ? 'border-primary text-ink'
                                  : 'text-muted-foreground'
                        "
                    >
                        {{ n }}
                    </li>
                </ol>
                <span class="sr-only">{{
                    t('pronunciation-beat', { beat, beats: BEATS })
                }}</span>
            </div>
            <p
                v-else-if="state === 'recording'"
                role="status"
                class="flex items-center gap-2 text-sm font-semibold"
            >
                <span
                    class="size-2.5 animate-pulse rounded-full bg-destructive"
                    aria-hidden="true"
                ></span>
                {{
                    t('pronunciation-recording', {
                        seconds,
                        max: MAX_SECONDS,
                    })
                }}
            </p>
            <p
                v-else-if="state === 'saving'"
                role="status"
                class="flex items-center gap-2 text-sm"
            >
                <Spinner />
                {{ t('pronunciation-saving') }}
            </p>

            <div class="flex flex-wrap gap-2">
                <!-- One button that starts and stops, so focus stays put. -->
                <Button
                    :id="id"
                    type="button"
                    :variant="
                        state === 'recording' || state === 'counting'
                            ? 'default'
                            : 'outline'
                    "
                    :disabled="state === 'saving'"
                    :aria-describedby="describedby"
                    data-test="record-pronunciation"
                    @click="toggle"
                >
                    <Square
                        v-if="state === 'recording'"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <X
                        v-else-if="state === 'counting'"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <Mic v-else class="size-4" aria-hidden="true" />
                    {{
                        state === 'recording'
                            ? t('pronunciation-stop')
                            : state === 'counting'
                              ? t('pronunciation-cancel')
                              : url
                                ? t('pronunciation-again')
                                : t('pronunciation-record')
                    }}
                </Button>
                <Button
                    v-if="url && state === 'idle'"
                    type="button"
                    variant="ghost"
                    class="text-destructive hover:text-destructive"
                    @click="remove"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                    {{ t('pronunciation-remove') }}
                </Button>
            </div>

            <p
                v-if="recordedNow && url && state === 'idle'"
                class="text-sm text-muted-foreground"
            >
                {{ t('pronunciation-unsaved') }}
            </p>
            <p v-if="problem" role="alert" class="text-sm text-destructive">
                {{ problem }}
            </p>
        </template>
    </div>
</template>
