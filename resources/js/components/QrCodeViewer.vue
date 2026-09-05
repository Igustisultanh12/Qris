<template>
  <div class="flex flex-col items-center bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm text-center">
    <!-- Merchant Name Header -->
    <div class="mb-4">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 mb-2">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        QRIS DYNAMIC AKTIF
      </span>
      <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ merchantName }}</h3>
      <p class="text-xs text-slate-500">{{ merchantCity }} • MCC {{ mcc || '5411' }}</p>
    </div>

    <!-- QR Code Display Box -->
    <div id="qris-print-area" class="bg-white p-4 rounded-2xl shadow-md border-2 border-slate-900 dark:border-slate-700 max-w-[280px] w-full aspect-square flex items-center justify-center">
      <div v-if="resolvedSvg" class="w-full h-full [&>svg]:w-full [&>svg]:h-full" v-html="resolvedSvg"></div>
      <img v-else-if="resolvedImage" :src="resolvedImage" alt="Dynamic QRIS" class="w-full h-full object-contain" />
      <div v-else class="text-xs text-slate-400">QR Code memuat...</div>
    </div>

    <!-- Amount & Breakdown -->
    <div class="mt-4 w-full bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 text-left text-sm border border-slate-100 dark:border-slate-800">
      <div class="flex justify-between py-1">
        <span class="text-slate-500">Nominal Transaksi</span>
        <span class="font-medium text-slate-900 dark:text-white">Rp {{ (amount || 0).toLocaleString('id-ID') }}</span>
      </div>
      <div v-if="(fee || 0) > 0" class="flex justify-between py-1">
        <span class="text-slate-500">Biaya Layanan</span>
        <span class="font-medium text-slate-900 dark:text-white">Rp {{ (fee || 0).toLocaleString('id-ID') }}</span>
      </div>
      <div class="flex justify-between py-2 border-t border-slate-200 dark:border-slate-700 mt-1 font-bold text-base text-indigo-600 dark:text-indigo-400">
        <span>Total Bayar</span>
        <span>Rp {{ (resolvedTotal || 0).toLocaleString('id-ID') }}</span>
      </div>
      <div v-if="reference" class="text-xs text-slate-400 mt-1">
        Ref: <span class="font-mono">{{ reference }}</span>
      </div>
    </div>

    <!-- Actions: Download PNG, Download SVG, Copy, Print -->
    <div class="grid grid-cols-2 gap-2 w-full mt-4">
      <button
        type="button"
        @click="copyPayload"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-colors"
      >
        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        <span>Salin String</span>
      </button>

      <button
        type="button"
        @click="printQr"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-colors"
      >
        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        <span>Cetak QR</span>
      </button>

      <button
        type="button"
        @click="downloadSvg"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        <span>Download SVG</span>
      </button>

      <button
        type="button"
        @click="downloadPng"
        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        <span>Download PNG</span>
      </button>
    </div>

    <!-- Disclaimer Notice (Requirement #66) -->
    <p class="text-[11px] text-slate-400 text-center mt-4 leading-relaxed">
      QRIS yang dihasilkan oleh platform ini merupakan hasil pemrosesan payload QRIS berdasarkan data QRIS merchant yang diberikan pengguna. Platform tidak menerbitkan atau mengubah status merchant pada sistem penyelenggara/acquirer QRIS.
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useToastStore } from '../stores/toast';

const props = withDefaults(
  defineProps<{
    merchantName?: string;
    merchantCity?: string;
    mcc?: string;
    amount?: number;
    fee?: number;
    total?: number;
    reference?: string;
    qrisPayload?: string;
    qrisString?: string;
    svgRaw?: string;
    svgContent?: string;
    qrImage?: string;
    pngDataUri?: string;
  }>(),
  {
    merchantName: 'Merchant',
    merchantCity: 'Indonesia',
    mcc: '5411',
    amount: 0,
    fee: 0,
    total: 0,
    reference: '',
    qrisPayload: '',
    qrisString: '',
    svgRaw: '',
    svgContent: '',
    qrImage: '',
    pngDataUri: '',
  }
);

const toast = useToastStore();

const resolvedPayload = computed(() => props.qrisPayload || props.qrisString || '');
const resolvedSvg = computed(() => props.svgRaw || props.svgContent || '');
const resolvedImage = computed(() => props.qrImage || props.pngDataUri || '');
const resolvedTotal = computed(() => {
  if (props.total && props.total > 0) return props.total;
  return (props.amount || 0) + (props.fee || 0);
});

const copyPayload = async () => {
  if (!resolvedPayload.value) {
    toast.error('String QRIS kosong');
    return;
  }
  try {
    await navigator.clipboard.writeText(resolvedPayload.value);
    toast.success('Disalin ke Clipboard', 'String QRIS dynamic berhasil disalin.');
  } catch {
    toast.error('Gagal Menyalin');
  }
};

const downloadSvg = () => {
  if (!resolvedSvg.value) {
    toast.error('Data SVG tidak tersedia');
    return;
  }
  const blob = new Blob([resolvedSvg.value], { type: 'image/svg+xml' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `qris-${props.reference || 'dynamic'}.svg`;
  a.click();
  URL.revokeObjectURL(url);
};

const downloadPng = () => {
  if (resolvedImage.value) {
    const a = document.createElement('a');
    a.href = resolvedImage.value;
    a.download = `qris-${props.reference || 'dynamic'}.png`;
    a.click();
  } else if (resolvedSvg.value) {
    // Convert SVG to PNG on canvas
    const img = new Image();
    const svgBlob = new Blob([resolvedSvg.value], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(svgBlob);

    img.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = 600;
      canvas.height = 600;
      const ctx = canvas.getContext('2d');
      if (!ctx) return;
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, 600, 600);
      ctx.drawImage(img, 0, 0, 600, 600);

      const pngUrl = canvas.toDataURL('image/png');
      const a = document.createElement('a');
      a.href = pngUrl;
      a.download = `qris-${props.reference || 'dynamic'}.png`;
      a.click();
      URL.revokeObjectURL(url);
    };
    img.src = url;
  } else {
    toast.error('Data gambar QRIS tidak tersedia');
  }
};

const printQr = () => {
  window.print();
};
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #qris-print-area, #qris-print-area * {
    visibility: visible;
  }
  #qris-print-area {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 300px;
    height: 300px;
  }
}
</style>
