<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import {
    Plus,
    Copy,
    Check,
    ExternalLink,
    Edit,
    Trash2,
    Search,
    ImageIcon,
    Layers,
    CheckCircle2,
    EyeOff
} from 'lucide-vue-next';

const props = defineProps({
    campaigns: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');
const copiedSlug = ref(null);
const deletingCampaign = ref(null);

const filteredCampaigns = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.campaigns;
    }
    const q = searchQuery.value.toLowerCase();
    return props.campaigns.filter((item) =>
        item.title.toLowerCase().includes(q) || item.slug.toLowerCase().includes(q)
    );
});

const stats = computed(() => {
    const total = props.campaigns.length;
    const active = props.campaigns.filter((c) => c.is_active).length;
    const inactive = total - active;
    return { total, active, inactive };
});

const copyPublicLink = (slug) => {
    const url = `${window.location.origin}/${slug}`;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(() => {
            copiedSlug.value = slug;
            setTimeout(() => {
                copiedSlug.value = null;
            }, 2000);
        });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = url;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            copiedSlug.value = slug;
            setTimeout(() => {
                copiedSlug.value = null;
            }, 2000);
        } catch (_) {
            alert(`Salin manual: ${url}`);
        }
        document.body.removeChild(textArea);
    }
};

const toggleStatus = (campaign) => {
    router.patch(route('admin.campaigns.toggle', campaign.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (campaign) => {
    deletingCampaign.value = campaign;
};

const closeModal = () => {
    deletingCampaign.value = null;
};

const deleteCampaign = () => {
    if (!deletingCampaign.value) return;
    router.delete(route('admin.campaigns.destroy', deletingCampaign.value.id), {
        onSuccess: () => closeModal(),
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
};
</script>

<template>
    <Head title="Daftar Twibbon" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-900">
                        Daftar Twibbon
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Kelola campaign twibbon, status publikasi, dan tautan publik.
                    </p>
                </div>
                <div>
                    <Link :href="route('admin.campaigns.create')">
                        <PrimaryButton class="flex items-center gap-2">
                            <Plus class="w-4 h-4 stroke-[2.5]" />
                            <span>Buat Twibbon Baru</span>
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Stat Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Twibbon</p>
                            <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.total }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center">
                            <Layers class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Twibbon Aktif</p>
                            <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.active }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                            <CheckCircle2 class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Nonaktif / Arsip</p>
                            <p class="text-2xl font-bold text-slate-900 mt-1">{{ stats.inactive }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center">
                            <EyeOff class="w-5 h-5" />
                        </div>
                    </div>
                </div>

                <!-- Main Table & Card Container -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                    <!-- Table Header Controls -->
                    <div class="border-b border-slate-200 p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="relative max-w-sm w-full">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari judul atau slug twibbon..."
                                class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm rounded-lg border border-slate-300 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition"
                            />
                        </div>
                        <div class="text-xs text-slate-500 font-medium">
                            Menampilkan {{ filteredCampaigns.length }} dari {{ campaigns.length }} twibbon
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredCampaigns.length === 0" class="p-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-slate-400 mb-3">
                            <ImageIcon class="h-7 w-7" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">
                            {{ searchQuery ? 'Tidak ada twibbon yang cocok' : 'Belum ada twibbon' }}
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 max-w-xs mx-auto">
                            {{ searchQuery ? 'Coba ubah kata kunci pencarian Anda.' : 'Mulai buat twibbon pertama dengan mengunggah file PNG berlatar transparan.' }}
                        </p>
                        <div v-if="!searchQuery" class="mt-5">
                            <Link :href="route('admin.campaigns.create')">
                                <PrimaryButton>+ Buat Twibbon Sekarang</PrimaryButton>
                            </Link>
                        </div>
                    </div>

                    <!-- Table List (Desktop & Tablet) -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">Bingkai</th>
                                    <th scope="col" class="px-6 py-3.5">Informasi Twibbon</th>
                                    <th scope="col" class="px-6 py-3.5">Status</th>
                                    <th scope="col" class="px-6 py-3.5">Dibuat</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="item in filteredCampaigns" :key="item.id" class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="h-14 w-14 rounded-lg bg-checkerboard p-1 border border-slate-200 flex items-center justify-center overflow-hidden shadow-xs">
                                            <img
                                                :src="item.frame_url"
                                                :alt="item.title"
                                                class="max-h-full max-w-full object-contain"
                                                loading="lazy"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 text-sm leading-snug">{{ item.title }}</div>
                                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-700 border border-slate-200">
                                                /{{ item.slug }}
                                            </span>
                                            <button
                                                @click="copyPublicLink(item.slug)"
                                                type="button"
                                                class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md text-slate-600 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition active:scale-95"
                                                :title="'Salin link: ' + item.slug"
                                            >
                                                <Check v-if="copiedSlug === item.slug" class="w-3.5 h-3.5 text-emerald-600 stroke-[2.5]" />
                                                <Copy v-else class="w-3.5 h-3.5" />
                                                <span>{{ copiedSlug === item.slug ? 'Tersalin!' : 'Salin Link' }}</span>
                                            </button>
                                            <a
                                                :href="'/' + item.slug"
                                                target="_blank"
                                                class="text-xs text-slate-400 hover:text-slate-700 p-0.5 transition inline-flex items-center"
                                                title="Buka halaman editor publik"
                                            >
                                                <ExternalLink class="w-3.5 h-3.5" />
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            @click="toggleStatus(item)"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold cursor-pointer transition border"
                                            :class="item.is_active
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100'
                                                : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="item.is_active ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                        {{ formatDate(item.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <Link
                                                :href="route('admin.campaigns.edit', item.id)"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition"
                                            >
                                                <Edit class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit</span>
                                            </Link>
                                            <button
                                                @click="confirmDelete(item)"
                                                type="button"
                                                class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-600 shadow-xs hover:bg-red-50 hover:text-red-700 transition"
                                            >
                                                <Trash2 class="w-3.5 h-3.5 text-red-500" />
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus -->
        <Modal :show="!!deletingCampaign" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center gap-3 text-red-600 mb-2">
                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                        <Trash2 class="w-5 h-5 text-red-600" />
                    </div>
                    <h3 class="text-base font-bold text-slate-900">
                        Hapus Twibbon?
                    </h3>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mt-2">
                    Apakah Anda yakin ingin menghapus twibbon <strong>"{{ deletingCampaign?.title }}"</strong>? Tindakan ini bersifat permanen dan file bingkai di storage akan dihapus.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deleteCampaign">
                        Ya, Hapus Twibbon
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
