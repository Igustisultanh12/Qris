<template>
  <div class="space-y-4">
    <!-- Camera stream container -->
    <div v-if="scanning" class="relative rounded-2xl overflow-hidden bg-black aspect-square max-w-sm mx-auto shadow-inner border border-slate-700">
      <video ref="videoEl" class="w-full h-full object-cover" playsinline muted></video>
      <canvas ref="canvasEl" class="hidden"></canvas>

      <!-- Target frame overlay -->
      <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="w-48 h-48 border-2 border-indigo-500/80 rounded-2xl animate-pulse-slow relative">
          <div class="absolute top-0 left-0 w-4 h-4 border-t-4 border-l-4 border-indigo-500 rounded-tl"></div>
          <div class="absolute top-0 right-0 w-4 h-4 border-t-4 border-r-4 border-indigo-500 rounded-tr"></div>
          <div class="absolute bottom-0 left-0 w-4 h-4 border-b-4 border-l-4 border-indigo-500 rounded-bl"></div>
          <div class="absolute bottom-0 right-0 w-4 h-4 border-b-4 border-r-4 border-indigo-500 rounded-br"></div>
        </div>
      </div>

      <div class="absolute bottom-3 inset-x-0 text-center">
        <span class="inline-block px-3 py-1 rounded-full bg-black/60 backdrop-blur-md text-xs font-medium text-white">
          Arahkan kamera ke QRIS static
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3">
      <button
        type="button"
        @click="scanning ? stopCamera() : startCamera()"
        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all shadow-sm"
        :class="scanning
          ? 'bg-rose-600 hover:bg-rose-700 text-white'
          : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-500/20'"
      >
        <svg v-if="!scanning" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span>{{ scanning ? 'Hentikan Kamera' : 'Buka Kamera Scanner' }}</span>
      </button>

      <button
        type="button"
        @click="fileInputEl?.click()"
        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-colors"
      >
        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>Upload Gambar</span>
      </button>

      <input
        ref="fileInputEl"
        type="file"
        accept="image/*"
        class="hidden"
        @change="handleFileUpload"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted, onMounted } from 'vue';
import jsQR from 'jsqr';
import { useToastStore } from '../stores/toast';
import { decodeQrFromFile, cleanQrisPayload } from '../utils/qrDecoder';

const emit = defineEmits<{
  (e: 'scan', data: string): void;
  (e: 'scan-success', data: string): void;
}>();

const toast = useToastStore();
const scanning = ref(false);
const videoEl = ref<HTMLVideoElement | null>(null);
const canvasEl = ref<HTMLCanvasElement | null>(null);
const fileInputEl = ref<HTMLInputElement | null>(null);

let stream: MediaStream | null = null;
let animFrame: number | null = null;

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach((track) => track.stop());
    stream = null;
  }
  if (animFrame) {
    cancelAnimationFrame(animFrame);
    animFrame = null;
  }
  scanning.value = false;
};

const tick = () => {
  if (!videoEl.value || !canvasEl.value) return;

  if (videoEl.value.readyState === videoEl.value.HAVE_ENOUGH_DATA) {
    const canvas = canvasEl.value;
    const video = videoEl.value;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    if (ctx) {
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const code = jsQR(imageData.data, imageData.width, imageData.height, {
        inversionAttempts: 'attemptBoth',
      });

      if (code && code.data && code.data.includes('000201')) {
        const payload = cleanQrisPayload(code.data);
        emit('scan', payload);
        emit('scan-success', payload);
        toast.success('QRIS Berhasil Dipindai!');
        stopCamera();
        return;
      }
    }
  }

  animFrame = requestAnimationFrame(tick);
};

const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' },
    });

    scanning.value = true;
    if (videoEl.value) {
      videoEl.value.srcObject = stream;
      videoEl.value.setAttribute('playsinline', 'true');
      await videoEl.value.play();
      animFrame = requestAnimationFrame(tick);
    }
  } catch {
    toast.error('Gagal Mengakses Kamera', 'Pastikan izin kamera diizinkan pada browser Anda.');
    scanning.value = false;
  }
};

const decodeImage = async (file: File) => {
  try {
    const payload = await decodeQrFromFile(file);
    emit('scan', payload);
    emit('scan-success', payload);
    toast.success('QRIS Berhasil Didecode!');
  } catch (err: any) {
    toast.error('QR Code Tidak Ditemukan', err.message || 'Pastikan gambar QR terlihat jelas dan memiliki kontras yang baik.');
  }
};

const handleFileUpload = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) decodeImage(file);
  target.value = '';
};

const handlePaste = (e: ClipboardEvent) => {
  const items = e.clipboardData?.items;
  if (!items) return;

  for (const item of items) {
    if (item.type.startsWith('image/')) {
      const file = item.getAsFile();
      if (file) {
        decodeImage(file);
        break;
      }
    }
  }
};

onMounted(() => {
  window.addEventListener('paste', handlePaste);
});

onUnmounted(() => {
  stopCamera();
  window.removeEventListener('paste', handlePaste);
});
</script>
