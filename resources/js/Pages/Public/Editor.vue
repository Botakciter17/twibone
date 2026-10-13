<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Camera,
    Upload,
    Download,
    Copy,
    Check,
    RotateCw,
    ZoomIn,
    ZoomOut,
    RefreshCw,
    Move,
    AlertTriangle,
    Sparkles,
    ShieldCheck,
    FlipHorizontal
} from 'lucide-vue-next';

const props = defineProps({
    campaign: {
        type: Object,
        required: true,
    },
});

// Canvas & DOM refs
const canvasRef = ref(null);
const fileInputRef = ref(null);
const containerRef = ref(null);
const generatorFormRef = ref(null);
const generatorResultRef = ref(null);

// State
const frameLoaded = ref(false);
const frameLoadError = ref(false);
const userPhotoLoaded = ref(false);
const isProcessing = ref(false);
const isExporting = ref(false);
const showLowResWarning = ref(false);
const captionCopied = ref(false);
const showIosModal = ref(false);
const iosResultUrl = ref(null);
const copyFallbackModal = ref(false);

// Caption Generator State
const namaLengkap = ref('');
const namaOrganisasi = ref('');
const isCaptionGenerated = ref(false);
const generatedCaptionText = ref('');
const generatorInputError = ref('');

// Transform state
const scale = ref(1.0); // 0.2 to 4.0
const rotation = ref(0); // -180 to 180 deg
const isFlippedHorizontal = ref(false);
const panX = ref(0);
const panY = ref(0);

// Image instances
let frameImg = null;
let userImg = null;
let currentUserObjectUrl = null;
let userImgNaturalWidth = 0;
let userImgNaturalHeight = 0;
let frameNaturalWidth = 1080;
let frameNaturalHeight = 1080;
let baseCoverScale = 1.0;

// Gesture state
let isDragging = false;
let startPointerX = 0;
let startPointerY = 0;
let startPanX = 0;
let startPanY = 0;
let initialPinchDistance = 0;
let startPinchScale = 1.0;

// Aspect ratio
const canvasAspectRatio = computed(() => {
    return `${frameNaturalWidth} / ${frameNaturalHeight}`;
});

// Deteksi placeholder pada caption template
const hasPlaceholders = computed(() => {
    if (!props.campaign.caption) return false;
    return /\{Nama Lengkap\}|\[Nama Lengkap\]|\{Nama Organisasi\}|\[Nama Organisasi\]|\{nama\}|\[nama\]|\{organisasi\}|\[organisasi\]/i.test(props.campaign.caption);
});

// Action: Generate Caption
const generateCaption = () => {
    generatorInputError.value = '';

    if (!namaLengkap.value.trim()) {
        generatorInputError.value = 'Silakan masukkan Nama Lengkap Anda terlebih dahulu.';
        return;
    }

    if (!namaOrganisasi.value.trim()) {
        generatorInputError.value = 'Silakan masukkan Nama Organisasi terlebih dahulu.';
        return;
    }

    let text = props.campaign.caption || '';
    // Ganti {Nama Lengkap} dan [Nama Lengkap]
    text = text.replace(/\{Nama Lengkap\}|\[Nama Lengkap\]|\{nama\}|\[nama\]/gi, namaLengkap.value.trim());
    // Ganti {Nama Organisasi} dan [Nama Organisasi]
    text = text.replace(/\{Nama Organisasi\}|\[Nama Organisasi\]|\{organisasi\}|\[organisasi\]/gi, namaOrganisasi.value.trim());

    generatedCaptionText.value = text;
    isCaptionGenerated.value = true;

    nextTick(() => {
        if (generatorResultRef.value) {
            generatorResultRef.value.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
};

// Current active caption
const currentActiveCaption = computed(() => {
    if (hasPlaceholders.value) {
        if (isCaptionGenerated.value && generatedCaptionText.value) {
            return generatedCaptionText.value;
        }
        let text = props.campaign.caption || '';
        const nameVal = namaLengkap.value.trim() || '{Nama Lengkap}';
        const orgVal = namaOrganisasi.value.trim() || '{Nama Organisasi}';
        text = text.replace(/\{Nama Lengkap\}|\[Nama Lengkap\]|\{nama\}|\[nama\]/gi, nameVal);
        text = text.replace(/\{Nama Organisasi\}|\[Nama Organisasi\]|\{organisasi\}|\[organisasi\]/gi, orgVal);
        return text;
    }
    return props.campaign.caption || '';
});

// Render canvas
const renderCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas || !frameImg || !frameLoaded.value) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.clearRect(0, 0, frameNaturalWidth, frameNaturalHeight);

    // Layer 1: User photo
    if (userPhotoLoaded.value && userImg) {
        ctx.save();
        const centerX = frameNaturalWidth / 2 + panX.value;
        const centerY = frameNaturalHeight / 2 + panY.value;

        ctx.translate(centerX, centerY);
        ctx.rotate((rotation.value * Math.PI) / 180);

        const currentScale = baseCoverScale * scale.value;
        const scaleX = currentScale * (isFlippedHorizontal.value ? -1 : 1);
        const scaleY = currentScale;
        ctx.scale(scaleX, scaleY);

        ctx.drawImage(
            userImg,
            -userImgNaturalWidth / 2,
            -userImgNaturalHeight / 2,
            userImgNaturalWidth,
            userImgNaturalHeight
        );
        ctx.restore();
    } else {
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, frameNaturalWidth, frameNaturalHeight);
    }

    // Layer 2: Frame twibbon
    ctx.drawImage(frameImg, 0, 0, frameNaturalWidth, frameNaturalHeight);
};

// Base cover scale calculation
const calculateBaseCoverScale = () => {
    if (!userImgNaturalWidth || !userImgNaturalHeight) return 1.0;
    const scaleX = frameNaturalWidth / userImgNaturalWidth;
    const scaleY = frameNaturalHeight / userImgNaturalHeight;
    return Math.max(scaleX, scaleY);
};

// Initialize twibbon frame
const initFrame = () => {
    frameLoaded.value = false;
    frameLoadError.value = false;

    frameImg = new Image();
    const url = props.campaign.frame_url;

    try {
        const resolvedUrl = new URL(url, window.location.href);
        if (resolvedUrl.origin !== window.location.origin) {
            frameImg.crossOrigin = 'anonymous';
        }
    } catch (_) {}

    frameImg.onload = () => {
        frameNaturalWidth = frameImg.naturalWidth || 1080;
        frameNaturalHeight = frameImg.naturalHeight || 1080;

        if (canvasRef.value) {
            canvasRef.value.width = frameNaturalWidth;
            canvasRef.value.height = frameNaturalHeight;
        }

        frameLoaded.value = true;
        renderCanvas();
    };

    frameImg.onerror = () => {
        if (frameImg.crossOrigin) {
            frameImg.crossOrigin = null;
            frameImg.src = url;
            return;
        }
        frameLoadError.value = true;
    };

    frameImg.src = url;
};

// Trigger file input
const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

// Handle uploaded photo
const handleUserPhoto = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    isProcessing.value = true;
    showLowResWarning.value = false;

    try {
        if (currentUserObjectUrl) {
            URL.revokeObjectURL(currentUserObjectUrl);
            currentUserObjectUrl = null;
        }

        const img = new Image();
        currentUserObjectUrl = URL.createObjectURL(file);

        await new Promise((resolve, reject) => {
            img.onload = () => {
                userImg = img;
                userImgNaturalWidth = img.naturalWidth;
                userImgNaturalHeight = img.naturalHeight;
                resolve();
            };
            img.onerror = (err) => {
                if (currentUserObjectUrl) {
                    URL.revokeObjectURL(currentUserObjectUrl);
                    currentUserObjectUrl = null;
                }
                reject(err);
            };
            img.src = currentUserObjectUrl;
        });

        if (userImgNaturalWidth < 500 || userImgNaturalHeight < 500) {
            showLowResWarning.value = true;
        }

        baseCoverScale = calculateBaseCoverScale();
        scale.value = 1.0;
        rotation.value = 0;
        isFlippedHorizontal.value = false;
        panX.value = 0;
        panY.value = 0;
        userPhotoLoaded.value = true;

        await nextTick();
        renderCanvas();
    } catch (error) {
        console.error('Gagal memproses gambar:', error);
        alert('Gagal memuat foto. Coba pilih foto lain.');
    } finally {
        isProcessing.value = false;
        if (fileInputRef.value) fileInputRef.value.value = '';
    }
};

// Zoom and Rotate controls
const setZoom = (val) => {
    scale.value = Math.max(0.2, Math.min(4.0, Number(val.toFixed(2))));
    renderCanvas();
};

const setRotation = (deg) => {
    rotation.value = deg;
    renderCanvas();
};

const rotateBy90 = () => {
    let newRot = (rotation.value + 90) % 360;
    if (newRot > 180) newRot -= 360;
    rotation.value = newRot;
    renderCanvas();
};

const rotateByMinus90 = () => {
    let newRot = (rotation.value - 90) % 360;
    if (newRot < -180) newRot -= 360;
    rotation.value = newRot;
    renderCanvas();
};

const rotateBy180 = () => {
    let newRot = (rotation.value + 180) % 360;
    if (newRot > 180) newRot -= 360;
    if (newRot <= -180) newRot += 360;
    rotation.value = newRot;
    renderCanvas();
};

const toggleFlip = () => {
    isFlippedHorizontal.value = !isFlippedHorizontal.value;
    renderCanvas();
};

const resetTransform = () => {
    scale.value = 1.0;
    rotation.value = 0;
    isFlippedHorizontal.value = false;
    panX.value = 0;
    panY.value = 0;
    renderCanvas();
};

// Pointer & Gesture events
const getCanvasScaleFactor = () => {
    if (!canvasRef.value) return 1;
    const rect = canvasRef.value.getBoundingClientRect();
    return frameNaturalWidth / rect.width;
};

const onPointerDown = (e) => {
    if (!userPhotoLoaded.value) return;
    if (e.target.setPointerCapture) {
        e.target.setPointerCapture(e.pointerId);
    }
    isDragging = true;
    startPointerX = e.clientX;
    startPointerY = e.clientY;
    startPanX = panX.value;
    startPanY = panY.value;
};

const onPointerMove = (e) => {
    if (!isDragging || !userPhotoLoaded.value) return;
    const factor = getCanvasScaleFactor();
    const deltaX = (e.clientX - startPointerX) * factor;
    const deltaY = (e.clientY - startPointerY) * factor;

    panX.value = startPanX + deltaX;
    panY.value = startPanY + deltaY;
    renderCanvas();
};

const onPointerUp = (e) => {
    isDragging = false;
    if (e.target.releasePointerCapture) {
        try {
            e.target.releasePointerCapture(e.pointerId);
        } catch (_) {}
    }
};

const onTouchStart = (e) => {
    if (e.touches.length === 2) {
        isDragging = false;
        const dx = e.touches[0].clientX - e.touches[1].clientX;
        const dy = e.touches[0].clientY - e.touches[1].clientY;
        initialPinchDistance = Math.hypot(dx, dy);
        startPinchScale = scale.value;
    }
};

const onTouchMove = (e) => {
    if (e.touches.length === 2 && initialPinchDistance > 0) {
        const dx = e.touches[0].clientX - e.touches[1].clientX;
        const dy = e.touches[0].clientY - e.touches[1].clientY;
        const currentDistance = Math.hypot(dx, dy);
        const newScale = (currentDistance / initialPinchDistance) * startPinchScale;
        scale.value = Math.max(0.2, Math.min(4.0, Number(newScale.toFixed(2))));
        renderCanvas();
    }
};

const onWheel = (e) => {
    if (!userPhotoLoaded.value) return;
    e.preventDefault();
    const delta = -e.deltaY * 0.001;
    const newScale = Math.max(0.2, Math.min(4.0, scale.value + delta));
    scale.value = Number(newScale.toFixed(2));
    renderCanvas();
};

// Keyboard navigation (WCAG Dragging Alternative)
const onKeyDown = (e) => {
    if (!userPhotoLoaded.value) return;
    const step = 15;
    if (e.key === 'ArrowLeft') {
        panX.value -= step;
        renderCanvas();
    } else if (e.key === 'ArrowRight') {
        panX.value += step;
        renderCanvas();
    } else if (e.key === 'ArrowUp') {
        panY.value -= step;
        renderCanvas();
    } else if (e.key === 'ArrowDown') {
        panY.value += step;
        renderCanvas();
    } else if (e.key === '+' || e.key === '=') {
        setZoom(scale.value + 0.1);
    } else if (e.key === '-' || e.key === '_') {
        setZoom(scale.value - 0.1);
    } else if (e.key.toLowerCase() === 'r') {
        rotateBy90();
    }
};

// Download result
const downloadResult = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    isExporting.value = true;
    renderCanvas();

    canvas.toBlob((blob) => {
        isExporting.value = false;
        if (!blob) {
            alert('Gagal mengekspor gambar.');
            return;
        }

        const fileName = `twibbon-${props.campaign.slug}.png`;
        const url = URL.createObjectURL(blob);
        const isIOS = (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        if (isIOS) {
            iosResultUrl.value = url;
            showIosModal.value = true;
        } else {
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 60000);
        }
    }, 'image/png');
};

// Copy to clipboard execution
const executeCopy = (text) => {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            captionCopied.value = true;
            setTimeout(() => {
                captionCopied.value = false;
            }, 2500);
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
};

// Main copy trigger
const copyCaption = () => {
    if (!props.campaign.caption) return;

    // Jika caption butuh generator tapi belum di-generate
    if (hasPlaceholders.value && !isCaptionGenerated.value) {
        if (namaLengkap.value.trim() && namaOrganisasi.value.trim()) {
            generateCaption();
        } else {
            if (generatorFormRef.value) {
                generatorFormRef.value.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const inputEl = generatorFormRef.value.querySelector('input');
                inputEl?.focus();
            }
            generatorInputError.value = 'Silakan isi Nama dan Organisasi lalu klik Generate Caption.';
            return;
        }
    }

    const textToCopy = isCaptionGenerated.value ? generatedCaptionText.value : currentActiveCaption.value;
    executeCopy(textToCopy);
};

const fallbackCopy = (text) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        const success = document.execCommand('copy');
        if (success) {
            captionCopied.value = true;
            setTimeout(() => {
                captionCopied.value = false;
            }, 2500);
        } else {
            copyFallbackModal.value = true;
        }
    } catch (_) {
        copyFallbackModal.value = true;
    }
    document.body.removeChild(textArea);
};

onMounted(() => {
    initFrame();
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
    if (iosResultUrl.value) {
        URL.revokeObjectURL(iosResultUrl.value);
    }
    if (currentUserObjectUrl) {
        URL.revokeObjectURL(currentUserObjectUrl);
    }
});
</script>

<template>
    <Head :title="'Yuk Pasang Twibbon ' + campaign.title + ' ✨'" />

    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col justify-between selection:bg-slate-900 selection:text-white">
        <!-- Header Publik -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-20 shadow-xs">
            <div class="max-w-xl mx-auto px-4 py-3.5 text-center">
                <h1 class="text-sm sm:text-base font-bold truncate text-slate-900 leading-tight" :title="campaign.title">
                    {{ campaign.title }}
                </h1>
            </div>
        </header>

        <!-- Main Content (Mobile-First Editor) -->
        <main class="flex-1 max-w-xl w-full mx-auto px-4 py-4 sm:py-6 flex flex-col gap-4">
            <!-- Deskripsi Acara -->
            <div v-if="campaign.description" class="rounded-xl bg-white p-4 border border-slate-200 shadow-xs text-xs sm:text-sm text-slate-600 whitespace-pre-line leading-relaxed">
                {{ campaign.description }}
            </div>

            <!-- Error Bingkai -->
            <div v-if="frameLoadError" class="rounded-xl bg-red-50 p-4 border border-red-200 text-red-700 text-xs sm:text-sm flex items-start gap-2">
                <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
                <span>Gagal memuat bingkai twibbon. Pastikan file bingkai dapat diakses di server.</span>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full rounded-2xl overflow-hidden bg-checkerboard shadow-xs border border-slate-300 select-none">
                <!-- Wrapper dengan Aspect Ratio Bingkai -->
                <div
                    ref="containerRef"
                    class="relative w-full flex items-center justify-center touch-none overflow-hidden"
                    :style="{ aspectRatio: canvasAspectRatio }"
                    @pointerdown="onPointerDown"
                    @pointermove="onPointerMove"
                    @pointerup="onPointerUp"
                    @pointercancel="onPointerUp"
                    @touchstart="onTouchStart"
                    @touchmove="onTouchMove"
                    @wheel="onWheel"
                    tabindex="0"
                    aria-label="Editor canvas twibbon. Gunakan tombol panah untuk menggeser, plus minus untuk zoom, r untuk memutar."
                >
                    <!-- Canvas Element -->
                    <canvas
                        ref="canvasRef"
                        class="w-full h-full object-contain cursor-grab active:cursor-grabbing"
                    ></canvas>

                    <!-- Empty State: Belum Ada Foto -->
                    <div
                        v-if="!userPhotoLoaded && frameLoaded"
                        @click="triggerFileInput"
                        class="absolute inset-0 bg-slate-900/40 flex flex-col items-center justify-center p-5 text-center cursor-pointer transition hover:bg-slate-900/50"
                    >
                        <div class="max-w-xs w-full bg-white rounded-2xl p-5 shadow-lg border border-slate-200 text-center flex flex-col items-center gap-3">
                            <div class="h-14 w-14 rounded-2xl bg-slate-100 text-slate-800 flex items-center justify-center border border-slate-200">
                                <Camera class="w-7 h-7" />
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-bold text-slate-900">
                                    Pilih Foto Anda
                                </h2>
                                <p class="text-[11px] text-slate-500 mt-1">
                                    Dari galeri atau kamera ponsel (JPG, PNG, WebP)
                                </p>
                            </div>
                            <button
                                type="button"
                                class="w-full min-h-[44px] rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-slate-800 active:scale-98 transition flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <Upload class="w-4 h-4" />
                                <span>Unggah Foto Sekarang</span>
                            </button>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="isProcessing || !frameLoaded" class="absolute inset-0 bg-white/80 flex items-center justify-center">
                        <div class="flex items-center gap-2.5 text-slate-800 font-semibold text-xs sm:text-sm bg-white px-4 py-2.5 rounded-xl shadow-xs border border-slate-200">
                            <svg class="animate-spin h-4 w-4 text-slate-800" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                            <span>Menyiapkan gambar...</span>
                        </div>
                    </div>
                </div>

                <!-- Petunjuk Sentuh saat foto sudah terpasang -->
                <div v-if="userPhotoLoaded" class="absolute top-2.5 left-2.5 pointer-events-none">
                    <span class="bg-slate-900/80 text-white text-[10px] font-medium px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1.5">
                        <Move class="w-3 h-3" />
                        <span>Geser & cubit (pinch) foto</span>
                    </span>
                </div>
            </div>

            <!-- Peringatan Resolusi Rendah -->
            <div v-if="showLowResWarning" class="rounded-xl bg-amber-50 p-3.5 border border-amber-200 text-amber-900 text-xs flex items-start gap-2">
                <AlertTriangle class="w-4 h-4 shrink-0 text-amber-700 mt-0.5" />
                <span>Foto beresolusi rendah. Hasil cetak atau unduhan twibbon mungkin terlihat buram.</span>
            </div>

            <!-- Hidden File Input -->
            <input
                ref="fileInputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                @change="handleUserPhoto"
                class="hidden"
            />

            <!-- Control Box & Action Toolbar -->
            <div class="rounded-2xl bg-white p-4 sm:p-5 border border-slate-200 shadow-xs space-y-4">
                <!-- Baris Tombol Aksi Cepat -->
                <div class="flex items-center justify-between gap-2.5">
                    <button
                        @click="triggerFileInput"
                        type="button"
                        class="min-h-[44px] flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-4 py-2.5 text-xs font-bold text-slate-800 active:scale-98 transition cursor-pointer"
                    >
                        <Camera class="w-4 h-4 text-slate-700" />
                        <span>{{ userPhotoLoaded ? 'Ganti Foto' : 'Pilih Foto' }}</span>
                    </button>

                    <button
                        v-if="userPhotoLoaded"
                        @click="resetTransform"
                        type="button"
                        class="min-h-[44px] px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 active:scale-98 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer"
                        title="Kembalikan ke posisi awal"
                    >
                        <RefreshCw class="w-3.5 h-3.5 text-slate-500" />
                        <span>Pusatkan</span>
                    </button>
                </div>

                <!-- Slider Zoom & Rotasi (Hanya saat foto ada) -->
                <div v-if="userPhotoLoaded" class="pt-3 border-t border-slate-100 space-y-4">
                    <!-- Zoom Slider -->
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-1.5">
                            <span class="flex items-center gap-1.5">
                                <ZoomIn class="w-3.5 h-3.5 text-slate-500" />
                                <span>Ukuran Foto (Zoom)</span>
                            </span>
                            <span class="font-mono text-slate-500 text-[11px] bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                {{ Math.round(scale * 100) }}%
                            </span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <button
                                @click="setZoom(scale - 0.1)"
                                type="button"
                                class="h-10 w-10 shrink-0 rounded-xl bg-slate-100 text-slate-800 font-bold hover:bg-slate-200 active:scale-95 flex items-center justify-center transition border border-slate-200 text-base cursor-pointer"
                                aria-label="Perkecil foto"
                            >
                                -
                            </button>
                            <input
                                type="range"
                                min="0.2"
                                max="3.0"
                                step="0.05"
                                :value="scale"
                                @input="setZoom(parseFloat($event.target.value))"
                                class="w-full cursor-pointer"
                                aria-label="Slider ukuran zoom foto"
                            />
                            <button
                                @click="setZoom(scale + 0.1)"
                                type="button"
                                class="h-10 w-10 shrink-0 rounded-xl bg-slate-100 text-slate-800 font-bold hover:bg-slate-200 active:scale-95 flex items-center justify-center transition border border-slate-200 text-base cursor-pointer"
                                aria-label="Perbesar foto"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Rotate & Flip Controls -->
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-2">
                            <span class="flex items-center gap-1.5">
                                <RotateCw class="w-3.5 h-3.5 text-slate-500" />
                                <span>Putar & Balik Foto</span>
                            </span>
                            <span class="font-mono text-slate-500 text-[11px] bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                {{ rotation }}° {{ isFlippedHorizontal ? '(Cermin)' : '' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-4 gap-2 mb-2.5">
                            <button
                                @click="rotateByMinus90"
                                type="button"
                                class="h-9 px-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs active:scale-95 flex items-center justify-center transition border border-slate-200 cursor-pointer"
                                title="Putar -90 derajat"
                            >
                                -90°
                            </button>
                            <button
                                @click="rotateBy90"
                                type="button"
                                class="h-9 px-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs active:scale-95 flex items-center justify-center transition border border-slate-200 cursor-pointer"
                                title="Putar +90 derajat"
                            >
                                +90°
                            </button>
                            <button
                                @click="rotateBy180"
                                type="button"
                                class="h-9 px-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs active:scale-95 flex items-center justify-center transition border border-slate-200 cursor-pointer"
                                title="Putar 180 derajat (Balik Atas-Bawah)"
                            >
                                180°
                            </button>
                            <button
                                @click="toggleFlip"
                                type="button"
                                :class="[
                                    'h-9 px-2 rounded-xl font-bold text-xs active:scale-95 flex items-center justify-center gap-1 transition border cursor-pointer',
                                    isFlippedHorizontal 
                                        ? 'bg-slate-900 text-white border-slate-900' 
                                        : 'bg-slate-100 hover:bg-slate-200 text-slate-800 border-slate-200'
                                ]"
                                title="Balik foto secara horizontal (Cermin / Mirror)"
                            >
                                <FlipHorizontal class="w-3.5 h-3.5" />
                                <span>Cermin</span>
                            </button>
                        </div>
                        <input
                            type="range"
                            min="-180"
                            max="180"
                            step="1"
                            :value="rotation"
                            @input="setRotation(parseInt($event.target.value))"
                            class="w-full cursor-pointer"
                            aria-label="Slider presisi rotasi foto"
                        />
                    </div>
                </div>

                <!-- Tombol Download Utama -->
                <div class="pt-2">
                    <button
                        @click="downloadResult"
                        :disabled="!userPhotoLoaded || isExporting"
                        type="button"
                        class="min-h-[50px] w-full rounded-xl bg-slate-900 px-6 py-3.5 text-sm sm:text-base font-bold text-white shadow-xs transition hover:bg-slate-800 active:bg-slate-950 disabled:opacity-40 disabled:pointer-events-none flex items-center justify-center gap-2.5 cursor-pointer"
                    >
                        <Download v-if="!isExporting" class="w-5 h-5 stroke-[2.5]" />
                        <svg v-else class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        <span>{{ isExporting ? 'Memproses Unduhan...' : 'Download Twibbon (PNG)' }}</span>
                    </button>

                    <div class="flex items-center justify-center gap-1.5 text-[11px] text-slate-500 mt-2.5">
                        <ShieldCheck class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Privasi Aman &bull; Foto diproses langsung di peramban Anda</span>
                    </div>
                </div>
            </div>

            <!-- FITUR: Generator Caption Interaktif (Jika memiliki template placeholder) -->
            <div v-if="hasPlaceholders" ref="generatorFormRef" class="rounded-2xl bg-white p-4 sm:p-5 border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                    <div class="h-8 w-8 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                        <Sparkles class="w-4 h-4" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 leading-tight">Generator Caption</h3>
                        <p class="text-[11px] text-slate-500">Lengkapi data Anda untuk membuat caption siap salin</p>
                    </div>
                </div>

                <!-- Form Input Identitas -->
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="namaLengkap"
                            placeholder="Masukkan nama lengkap Anda..."
                            class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 px-3.5 py-2.5 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition"
                            @keyup.enter="generateCaption"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Nama Organisasi <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="namaOrganisasi"
                            placeholder="Masukkan nama organisasi Anda..."
                            class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 px-3.5 py-2.5 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition"
                            @keyup.enter="generateCaption"
                        />
                    </div>

                    <div v-if="generatorInputError" class="text-xs text-red-600 font-medium flex items-center gap-1.5 pt-1">
                        <AlertTriangle class="w-3.5 h-3.5 shrink-0" />
                        <span>{{ generatorInputError }}</span>
                    </div>

                    <!-- Tombol Generate Caption -->
                    <button
                        @click="generateCaption"
                        type="button"
                        class="w-full min-h-[46px] rounded-xl bg-slate-900 text-white font-bold text-xs sm:text-sm px-4 py-2.5 shadow-xs hover:bg-slate-800 active:scale-98 transition flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <Sparkles class="w-4 h-4" />
                        <span>Generate Caption</span>
                    </button>
                </div>

                <!-- Hasil Caption Yang Digenerate -->
                <div v-if="isCaptionGenerated" ref="generatorResultRef" class="pt-3 border-t border-slate-100 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                            <Check class="w-4 h-4 stroke-[2.5]" />
                            <span>Caption Berhasil Dibuat!</span>
                        </div>
                        <button
                            @click="copyCaption"
                            type="button"
                            class="min-h-[40px] px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition active:scale-95 border cursor-pointer"
                            :class="captionCopied
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                : 'bg-slate-900 text-white border-slate-900 hover:bg-slate-800'"
                        >
                            <Check v-if="captionCopied" class="w-3.5 h-3.5 stroke-[2.5]" />
                            <Copy v-else class="w-3.5 h-3.5" />
                            <span>{{ captionCopied ? 'Tersalin!' : 'Salin Caption' }}</span>
                        </button>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3.5 text-xs sm:text-sm text-slate-700 font-sans border border-slate-200 whitespace-pre-wrap select-all leading-relaxed">
                        {{ generatedCaptionText }}
                    </div>
                </div>
            </div>

            <!-- Card Caption Standar (Jika tidak menggunakan placeholder) -->
            <div v-else-if="campaign.caption" class="rounded-2xl bg-white p-4 sm:p-5 border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Caption Media Sosial
                    </h3>
                    <button
                        @click="copyCaption"
                        type="button"
                        class="text-xs font-bold text-slate-700 hover:text-slate-900 transition flex items-center gap-1.5 active:scale-95 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-lg border border-slate-200 cursor-pointer"
                    >
                        <Check v-if="captionCopied" class="w-3.5 h-3.5 text-emerald-600 stroke-[2.5]" />
                        <Copy v-else class="w-3.5 h-3.5" />
                        <span>{{ captionCopied ? 'Tersalin!' : 'Salin Teks' }}</span>
                    </button>
                </div>
                <div class="rounded-xl bg-slate-50 p-3.5 text-xs sm:text-sm text-slate-700 font-sans border border-slate-200 whitespace-pre-wrap select-all leading-relaxed">
                    {{ campaign.caption }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-4 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
            <p>BagusArya 2026</p>
        </footer>

        <!-- Modal Safari iOS -->
        <div v-if="showIosModal" class="fixed inset-0 z-50 bg-slate-900/80 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-5 text-center space-y-4 shadow-xl border border-slate-200">
                <h3 class="font-bold text-base text-slate-900">Hasil Twibbon Siap!</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Di browser iOS Safari, <strong>tekan lama (hold)</strong> gambar di bawah ini, lalu pilih <strong>"Simpan ke Foto"</strong> (Save to Photos).
                </p>
                <div class="rounded-xl overflow-hidden border border-slate-200 bg-checkerboard flex justify-center p-2">
                    <img :src="iosResultUrl" alt="Twibbon Result" class="max-h-72 w-auto object-contain rounded-lg shadow-xs" />
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="iosResultUrl"
                        target="_blank"
                        class="flex-1 min-h-[44px] rounded-xl border border-slate-300 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <span>Buka Layar Penuh</span>
                    </a>
                    <button
                        @click="showIosModal = false"
                        type="button"
                        class="flex-1 min-h-[44px] rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Fallback Salin Manual -->
        <div v-if="copyFallbackModal" class="fixed inset-0 z-50 bg-slate-900/80 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-5 space-y-3 shadow-xl border border-slate-200">
                <h3 class="font-bold text-base text-slate-900">Salin Caption Manual</h3>
                <p class="text-xs text-slate-600">
                    Akses clipboard dibatasi oleh browser. Silakan salin teks di bawah ini:
                </p>
                <textarea
                    readonly
                    class="w-full h-36 p-3 text-xs rounded-xl bg-slate-50 border border-slate-300 font-sans focus:ring-0 select-all"
                    :value="currentActiveCaption"
                    @focus="$event.target.select()"
                ></textarea>
                <button
                    @click="copyFallbackModal = false"
                    type="button"
                    class="w-full min-h-[44px] rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition cursor-pointer"
                >
                    Selesai
                </button>
            </div>
        </div>
    </div>
</template>
