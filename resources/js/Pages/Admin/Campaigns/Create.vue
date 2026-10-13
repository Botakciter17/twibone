<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ArrowLeft, Upload, ImageIcon, CheckCircle2, AlertCircle } from 'lucide-vue-next';

const form = useForm({
    title: '',
    slug: '',
    description: '',
    caption: '',
    is_active: true,
    frame: null,
});

const previewUrl = ref(null);
const imageDimensions = ref(null);
const isManualSlug = ref(false);
const clientFileError = ref(null);
const isDraggingFile = ref(false);

const slugify = (text) => {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
};

watch(() => form.title, (newTitle) => {
    if (!isManualSlug.value) {
        form.slug = slugify(newTitle);
    }
});

const onSlugInput = () => {
    isManualSlug.value = true;
};

const processFile = (file) => {
    clientFileError.value = null;
    if (!file) {
        form.frame = null;
        previewUrl.value = null;
        imageDimensions.value = null;
        return;
    }

    if (file.type !== 'image/png') {
        clientFileError.value = 'File harus berformat PNG (.png).';
        form.frame = null;
        previewUrl.value = null;
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        clientFileError.value = 'Ukuran file melebihi batas 2 MB.';
        form.frame = null;
        previewUrl.value = null;
        return;
    }

    form.frame = file;

    const reader = new FileReader();
    reader.onload = (event) => {
        previewUrl.value = event.target.result;
        const img = new Image();
        img.onload = () => {
            imageDimensions.value = {
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
    if (!form.frame) {
        clientFileError.value = 'Silakan pilih file bingkai PNG transparan.';
        return;
    }

    form.post(route('admin.campaigns.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Buat Twibbon Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-900">
                        Buat Twibbon Baru
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Unggah bingkai PNG transparan dan atur detail campaign.
                    </p>
                </div>
                <Link :href="route('admin.campaigns.index')">
                    <SecondaryButton class="flex items-center gap-1.5 text-xs">
                        <ArrowLeft class="w-3.5 h-3.5" />
                        <span>Kembali</span>
                    </SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Kolom Kiri: Form Input (7 cols) -->
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
                                    placeholder="Contoh: LDK Aktivis 2026"
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
                                        @input="onSlugInput"
                                        class="block w-full min-w-0 flex-1 rounded-none rounded-r-lg border-slate-300 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 text-xs sm:text-sm font-mono"
                                        placeholder="ldk-aktivis-2026"
                                        required
                                    />
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Huruf kecil, angka, dan tanda hubung (-). Link: <span class="font-mono text-slate-700">/{{ form.slug || 'slug' }}</span>
                                </p>
                                <InputError class="mt-1.5" :message="form.errors.slug" />
                            </div>

                            <!-- Upload Frame PNG -->
                            <div>
                                <InputLabel value="File Bingkai PNG Transparan *" />
                                <p class="text-[11px] text-slate-500 mb-2">
                                    Wajib format PNG dengan area transparan (alpha channel). Maksimal 2 MB. Disarankan persegi (misal 1080×1080 px).
                                </p>

                                <div
                                    @dragover.prevent="isDraggingFile = true"
                                    @dragleave.prevent="isDraggingFile = false"
                                    @drop.prevent="handleDrop"
                                    class="relative rounded-xl border-2 border-dashed p-6 text-center transition cursor-pointer"
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
                                        <div class="h-10 w-10 rounded-lg bg-white border border-slate-200 text-slate-700 flex items-center justify-center mb-2 shadow-xs">
                                            <Upload class="w-5 h-5" />
                                        </div>
                                        <p class="text-xs font-semibold text-slate-800">
                                            Klik untuk pilih file atau seret file ke sini
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            PNG hingga 2 MB
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
                                        Aktifkan Twibbon sekarang (langsung dapat dibuka publik)
                                    </span>
                                </label>
                                <InputError class="mt-1.5" :message="form.errors.is_active" />
                            </div>
                        </div>

                        <!-- Tombol Submit -->
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
                                {{ form.processing ? 'Menyimpan...' : 'Simpan & Publikasikan' }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Live Preview (5 cols) -->
                    <div class="lg:col-span-5">
                        <div class="sticky top-24 rounded-xl bg-white p-5 sm:p-6 shadow-xs border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Pratinjau Bingkai
                                </h3>
                                <span v-if="imageDimensions" class="text-[11px] font-mono text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    {{ imageDimensions.width }} × {{ imageDimensions.height }} px
                                </span>
                            </div>

                            <div class="relative w-full aspect-square rounded-xl border border-slate-200 bg-checkerboard flex items-center justify-center p-3 overflow-hidden shadow-inner">
                                <img
                                    v-if="previewUrl"
                                    :src="previewUrl"
                                    alt="Preview bingkai"
                                    class="max-h-full max-w-full object-contain pointer-events-none"
                                />
                                <div v-else class="text-center p-6 text-slate-400">
                                    <ImageIcon class="w-10 h-10 mx-auto stroke-1 mb-2 text-slate-300" />
                                    <p class="text-xs font-medium text-slate-500">Belum ada bingkai dipilih</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Pola kotak-kotak menandakan area transparan untuk foto user</p>
                                </div>
                            </div>

                            <div v-if="previewUrl" class="rounded-lg bg-slate-50 p-3 border border-slate-200 text-[11px] text-slate-600 space-y-1">
                                <div class="flex items-center gap-1.5 font-semibold text-slate-800">
                                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                                    <span>File PNG berhasil dimuat</span>
                                </div>
                                <p class="text-slate-500">Area transparan akan diisi oleh foto pengguna saat menggunakan twibbon.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
