<script setup>
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppShell from '../Layouts/AppShell.vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const updating = ref({});
const actionError = ref(null);
let pollTimer = null;

const runningCount = computed(() => props.projects.filter((project) => project.source_update?.status === 'running').length);
const pendingCount = computed(() => props.projects.filter((project) => project.source_update?.status === 'pending').length);
const hasActiveUpdates = computed(() => runningCount.value > 0 || pendingCount.value > 0);

const isProjectBusy = (project) => ['pending', 'running'].includes(project.source_update?.status);

const statusClass = (status) => {
    if (status === 'running') return 'border-blue-200 bg-blue-50 text-blue-700';
    if (status === 'pending') return 'border-amber-200 bg-amber-50 text-amber-700';
    if (status === 'success') return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (status === 'fail') return 'border-red-200 bg-red-50 text-red-700';
    return 'border-zinc-200 bg-zinc-50 text-zinc-600';
};

const statusLabel = (project) => project.source_update?.status || 'not requested';

const dateLabel = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const shortError = (error) => {
    if (!error) return null;

    const cleaned = error.split('\n').filter(Boolean).slice(-4).join(' ');
    return cleaned.length > 180 ? `${cleaned.slice(0, 180)}...` : cleaned;
};

const refresh = () => {
    router.reload({
        only: ['projects'],
        preserveScroll: true,
        preserveState: true,
    });
};

const requestSourceUpdate = async (project) => {
    actionError.value = null;
    updating.value = { ...updating.value, [project.slug]: true };

    try {
        await axios.post(project.source_update_url);
        refresh();
    } catch (error) {
        actionError.value = error.response?.data?.message || 'Unable to request source update.';
    } finally {
        updating.value = { ...updating.value, [project.slug]: false };
    }
};

onMounted(() => {
    pollTimer = window.setInterval(() => {
        if (hasActiveUpdates.value) {
            refresh();
        }
    }, 5000);
});

onBeforeUnmount(() => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
    }
});
</script>

<template>
    <AppShell>
        <div class="rounded border border-zinc-200 bg-white">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold">Source Updates</h2>
                    <p class="mt-1 text-sm text-zinc-500">Refresh Git source caches and monitor the latest run.</p>
                </div>
                <button
                    type="button"
                    class="rounded border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50"
                    @click="refresh"
                >
                    Refresh
                </button>
            </div>

            <div v-if="actionError" class="m-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ actionError }}
            </div>

            <div v-if="projects.length === 0" class="m-5 rounded border border-dashed border-zinc-300 p-6 text-sm text-zinc-500">
                No projects are configured yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[980px] border-collapse text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Project</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Attempts</th>
                            <th class="px-4 py-3 font-semibold">Requested</th>
                            <th class="px-4 py-3 font-semibold">Finished</th>
                            <th class="px-4 py-3 font-semibold">Last Result</th>
                            <th class="px-4 py-3 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        <tr v-for="project in projects" :key="project.id" class="align-top">
                            <td class="px-4 py-4">
                                <div class="font-medium">{{ project.name }}</div>
                                <div class="mt-1 text-xs text-zinc-500">{{ project.slug }}</div>
                                <div v-if="isProjectBusy(project)" class="mt-2 text-xs font-medium text-blue-700">
                                    Project busy
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="inline-flex rounded border px-2 py-1 text-xs font-medium"
                                    :class="statusClass(project.source_update?.status)"
                                >
                                    {{ statusLabel(project) }}
                                </span>
                                <div v-if="project.source_update?.rerun" class="mt-2 text-xs text-amber-700">
                                    Rerun queued
                                </div>
                            </td>
                            <td class="px-4 py-4 text-zinc-600">{{ project.source_update?.attempts || 0 }}</td>
                            <td class="px-4 py-4 text-zinc-600">{{ dateLabel(project.source_update?.requested_at) }}</td>
                            <td class="px-4 py-4 text-zinc-600">{{ dateLabel(project.source_update?.finished_at) }}</td>
                            <td class="max-w-md px-4 py-4">
                                <p v-if="shortError(project.source_update?.last_error)" class="text-sm leading-5 text-red-700">
                                    {{ shortError(project.source_update.last_error) }}
                                </p>
                                <p v-else class="text-sm text-zinc-500">No error recorded.</p>
                                <a
                                    v-if="project.source_update?.last_log_path"
                                    :href="project.source_update_log_url"
                                    target="_blank"
                                    class="mt-2 inline-flex text-xs font-medium text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:text-zinc-950"
                                >
                                    View latest log
                                </a>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button
                                    type="button"
                                    class="rounded bg-zinc-950 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-300"
                                    :disabled="updating[project.slug] || isProjectBusy(project)"
                                    @click="requestSourceUpdate(project)"
                                >
                                    {{ updating[project.slug] ? 'Requesting...' : isProjectBusy(project) ? 'Busy' : 'Update Source' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppShell>
</template>
