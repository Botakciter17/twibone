<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ArrowLeft, Upload, ExternalLink, AlertCircle, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    campaign: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    _method: 'PUT',
    title: props.campaign.title,
    slug: props.campaign.slug,
    description: props.campaign.description || '',
    caption: props.campaign.caption || '',
    is_active: Boolean(props.campaign.is_active),
    frame: null,
});

const newPreviewUrl = ref(null);
const newImageDimensions = ref(null);
const clientFileError = ref(null);
const isDraggingFile = ref(false);

const processFile = (file) => {
    clientFileError.value = null;
    if (!file) {
        form.frame = null;
        newPreviewUrl.value = null;
        newImageDimensions.value = null;
        return;
    }

    if (file.type !== 'image/png') {
        clientFileError.value = 'File harus berformat PNG (.png).';
        form.frame = null;
        newPreviewUrl.value = null;
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        clientFileError.value = 'Ukuran file melebihi batas 2 MB.';
        form.frame = null;
        newPreviewUrl.value = null;
        return;
    }

    form.frame = file;

    const reader = new FileReader();
    reader.onload = (event) => {
        newPreviewUrl.value = event.target.result;
        const img = new Image();
        img.onload = () => {
            newImageDimensions.value = {
                width: img.naturalWidth,
                height: img.naturalHeight,
            };
        };
        img.src = event.target.result;
    };
    reader.readAsDataURL(file);
};

const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    processFile(file);
};

const handleDrop = (e) => {
    isDraggingFile.value = false;
    const file = e.dataTransfer?.files?.[0];
    processFile(file);
};

const submit = () => {
    clientFileError.value = null;
    form.post(route('admin.campaigns.update', props.campaign.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="'Edit ' + campaign.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-900">
                        Edit Twibbon
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Perbarui rincian, caption, atau ganti bingkai tanpa mengubah link publik.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="'/' + campaign.slug"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition"
                    >
                        <span>Lihat Publik</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                    <Link :href="route('admin.campaigns.index')">
                        <SecondaryButton class="flex items-center gap-1.5 text-xs">
                            <ArrowLeft class="w-3.5 h-3.5" />
                            <span>Kembali</span>
                        </SecondaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Kolom Kiri: Form Fields (7 cols) -->
                    <div class="lg:col-span-7 space-y-5">
                        <div class="rounded-xl bg-white p-5 sm:p-6 shadow-xs border border-slate-200 space-y-5">
                            <!-- Judul -->
                            <div>
                                <InputLabel for="title" value="Judul Twibbon *" />
                                <TextInput
                                    id="title"
                                    type="text"
                                    v-model="form.title"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-1.5" :message="form.errors.title" />
                            </div>

                            <!-- Slug -->
                            <div>
                                <InputLabel for="slug" value="Slug Alamat Link (/{slug}) *" />
                                <div class="mt-1 flex rounded-lg shadow-xs">
                                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-xs font-mono text-slate-500 select-none">
                                        /
                                    </span>
                                    <input
                                        id="slug"
                                        type="text"
                                        v-model="form.slug"
                                        class="block w-full min-w-0 flex-1 rounded-none rounded-r-lg border-slate-300 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs sm:text-sm font-mono"
                                        required
                                    />
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Disarankan tidak mengubah slug jika link sudah disebarkan.
                                </p>
                                <InputError class="mt-1.5" :message="form.errors.slug" />
                            </div>

                            <!-- Ganti File PNG -->
                            <div>
                                <InputLabel value="Ganti File Bingkai PNG (Opsional)" />
                                <p class="text-[11px] text-slate-500 mb-2">
                                    Biarkan kosong jika tidak ingin mengganti bingkai. Hanya PNG transparan, maks 2 MB.
                                </p>

                                <div
                                    @dragover.prevent="isDraggingFile = true"
                                    @dragleave.prevent="isDraggingFile = false"
                                    @drop.prevent="handleDrop"
                                    class="relative rounded-xl border-2 border-dashed p-5 text-center transition cursor-pointer"
                                    :class="isDraggingFile ? 'border-slate-900 bg-slate-100' : 'border-slate-300 hover:border-slate-400 bg-slate-50/50'"
                                    @click="$refs.fileInput.click()"
                                >
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/png"
                                        @change="handleFileChange"
                                        class="hidden"
                                    />
                                    <div class="flex flex-col items-center">
                                        <div class="h-9 w-9 rounded-lg bg-white border border-slate-200 text-slate-700 flex items-center justify-center mb-1.5 shadow-xs">
                                            <Upload class="w-4 h-4" />
                                        </div>
                                        <p class="text-xs font-semibold text-slate-800">
                                            Pilih file baru untuk mengganti bingkai
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            Format PNG transparan hingga 2 MB
                                        </p>
                                    </div>
                                </div>

                                <div v-if="clientFileError" class="mt-2 text-xs text-red-600 font-medium flex items-center gap-1.5">
                                    <AlertCircle class="w-4 h-4 shrink-0" />
                                    <span>{{ clientFileError }}</span>
                                </div>
                                <InputError class="mt-1.5" :message="form.errors.frame" />
                            </div>

                            <!-- Deskripsi -->
                            <div>
                                <InputLabel for="description" value="Deskripsi Singkat (Opsional)" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-xs focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs sm:text-sm"
                                    placeholder="Jelaskan acara atau instruksi pemakaian..."
                                ></textarea>
                                <InputError class="mt-1.5" :message="form.errors.description" />
                            </div>

                            <!-- Caption -->
                            <div>
                                <InputLabel for="caption" value="Caption Bawaan (Opsional)" />
                                <p class="text-[11px] text-slate-500 mb-1">
                                    Teks yang dapat disalin pengguna dengan satu klik untuk media sosial.
                                </p>
                                <textarea
                                    id="caption"
                                    v-model="form.caption"
                                    rows="5"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-xs focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs sm:text-sm font-sans"
                                    placeholder="Saya {Nama Lengkap} dari {Nama Organisasi} siap mengikuti..."
                                ></textarea>
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <span class="text-[11px] text-slate-500 font-medium">Sisipkan variabel:</span>
                                    <button
                                        type="button"
                                        @click="form.caption = (form.caption ? form.caption + ' ' : '') + '{Nama Lengkap}'"
                                        class="text-[11px] font-mono font-semibold px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer"
                                    >
                                        + {Nama Lengkap}
                                    </button>
                                    <button
                                        type="button"
                                        @click="form.caption = (form.caption ? form.caption + ' ' : '') + '{Nama Organisasi}'"
                                        class="text-[11px] font-mono font-semibold px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer"
                                    >
                                        + {Nama Organisasi}
                                    </button>
                                </div>
                                <InputError class="mt-1.5" :message="form.errors.caption" />
                            </div>

                            <!-- Status Aktif -->
                            <div class="pt-1">
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-slate-300 text-slate-900 shadow-xs focus:ring-slate-900 h-4 w-4"
                                    />
                                    <span class="ms-2.5 text-xs sm:text-sm font-medium text-slate-800">
                                        Twibbon Aktif (dapat dibuka oleh publik)
                                    </span>
                                </label>
                                <InputError class="mt-1.5" :message="form.errors.is_active" />
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <Link :href="route('admin.campaigns.index')">
                                <SecondaryButton :disabled="form.processing">
                                    Batal
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton
                                type="submit"
                                :class="{ 'opacity-50': form.processing }"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Perbarui Twibbon' }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Preview Bingkai (5 cols) -->
                    <div class="lg:col-span-5 space-y-4">
                        <!-- Bingkai Saat Ini -->
                        <div class="rounded-xl bg-white p-5 sm:p-6 shadow-xs border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Bingkai Saat Ini
                                </span>
                                <span v-if="campaign.frame_width" class="text-[11px] font-mono text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    {{ campaign.frame_width }} × {{ campaign.frame_height }} px
                                </span>
                            </div>

                            <div class="relative w-full aspect-square rounded-xl border border-slate-200 bg-checkerboard flex items-center justify-center p-3 overflow-hidden shadow-inner">
                                <img
                                    :src="campaign.frame_url"
                                    :alt="campaign.title"
                                    class="max-h-full max-w-full object-contain pointer-events-none"
                                />
                            </div>
                        </div>

                        <!-- Bingkai Baru (jika ada file baru diunggah) -->
                        <div v-if="newPreviewUrl" class="rounded-xl bg-white p-5 sm:p-6 shadow-xs border-2 border-slate-900 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-900">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                    <span>Bingkai Pengganti</span>
                                </div>
                                <span v-if="newImageDimensions" class="text-[11px] font-mono text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 font-bold">
                                    {{ newImageDimensions.width }} × {{ newImageDimensions.height }} px
                                </span>
                            </div>

                            <div class="relative w-full aspect-square rounded-xl border border-slate-300 bg-checkerboard flex items-center justify-center p-3 overflow-hidden shadow-inner">
                                <img
                                    :src="newPreviewUrl"
                                    alt="Preview bingkai baru"
                                    class="max-h-full max-w-full object-contain pointer-events-none"
                                />
                            </div>
                            <p class="text-[11px] text-slate-500 text-center">
                                Bingkai baru ini akan menggantikan bingkai lama setelah Anda menyimpan.
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
