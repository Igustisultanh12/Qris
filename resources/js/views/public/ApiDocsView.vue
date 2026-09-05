<template>
  <PublicLayout>
    <div class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 mb-3">
          API Reference v1.0
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">Dokumentasi REST API Kreatif QRIS</h1>
        <p class="mt-2 text-sm sm:text-base text-slate-600 dark:text-slate-400 max-w-3xl">
          Integrasikan konversi QRIS static ke dynamic langsung ke dalam sistem kasir POS, web e-commerce, aplikasi mobile, atau microservice Anda.
        </p>

        <div class="mt-4 flex items-center gap-4 text-xs font-mono text-slate-500">
          <span>Base URL: <code class="bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-indigo-600">https://api.kreatifabadi.co.id/api/v1</code></span>
          <span>•</span>
          <a href="/docs/openapi.json" target="_blank" class="text-indigo-600 hover:underline">Download OpenAPI Spec (JSON)</a>
        </div>
      </div>

      <!-- Quickstart Code Sample Cards -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start mb-16">
        <!-- Endpoint Specification -->
        <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">POST</span>
              <code class="text-sm font-bold text-slate-900 dark:text-white">/api/v1/qris/dynamic</code>
            </div>
            <p class="text-xs text-slate-500 leading-relaxed">
              Membuat payload QRIS dinamis dengan nominal transaksi tertentu, menambahkan fee (opsional), dan menghasilkan SVG QR code siap render.
            </p>
          </div>

          <!-- Headers -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Required Headers</h4>
            <div class="bg-slate-50 dark:bg-slate-950 rounded-xl p-3 text-xs font-mono space-y-1.5 border border-slate-100 dark:border-slate-800">
              <p><span class="text-indigo-500">Authorization</span>: Bearer &lt;YOUR_API_KEY&gt;</p>
              <p><span class="text-indigo-500">Content-Type</span>: application/json</p>
              <p><span class="text-indigo-500">Idempotency-Key</span>: &lt;UNIQUE_REQUEST_ID&gt; <span class="text-slate-400 text-[10px]">(Opsional)</span></p>
            </div>
          </div>

          <!-- Body Parameters -->
          <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Body Parameters (JSON)</h4>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <div>
                  <span class="font-mono font-bold text-indigo-600">merchant_id</span>
                  <span class="text-rose-500 font-bold ml-1">*</span>
                  <p class="text-slate-400 text-[11px]">Merchant Code atau UUID merchant</p>
                </div>
                <span class="font-mono text-slate-400">string</span>
              </div>

              <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <div>
                  <span class="font-mono font-bold text-indigo-600">amount</span>
                  <span class="text-rose-500 font-bold ml-1">*</span>
                  <p class="text-slate-400 text-[11px]">Nominal transaksi (min 1.000)</p>
                </div>
                <span class="font-mono text-slate-400">integer</span>
              </div>

              <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <div>
                  <span class="font-mono font-bold text-indigo-600">reference</span>
                  <span class="text-rose-500 font-bold ml-1">*</span>
                  <p class="text-slate-400 text-[11px]">ID order dari sistem Anda</p>
                </div>
                <span class="font-mono text-slate-400">string</span>
              </div>

              <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                <div>
                  <span class="font-mono font-bold text-indigo-600">fee_type</span>
                  <p class="text-slate-400 text-[11px]">none, fixed, atau percentage</p>
                </div>
                <span class="font-mono text-slate-400">string</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Code Snippet Box with Language Tabs -->
        <div class="bg-slate-900 rounded-2xl overflow-hidden border border-slate-800 shadow-xl">
          <div class="flex items-center justify-between px-4 py-3 bg-slate-950/80 border-b border-slate-800">
            <div class="flex gap-1.5">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                class="px-3 py-1 rounded-lg text-xs font-mono font-semibold transition-colors"
                :class="activeTab === tab.id ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
              >
                {{ tab.name }}
              </button>
            </div>

            <button
              @click="copySnippet"
              class="text-xs text-slate-400 hover:text-white flex items-center gap-1"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <span>Salin</span>
            </button>
          </div>

          <pre class="p-5 text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed"><code>{{ snippets[activeTab] }}</code></pre>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import PublicLayout from '../../layouts/PublicLayout.vue';
import { useToastStore } from '../../stores/toast';

const toast = useToastStore();
const activeTab = ref('curl');

const tabs = [
  { id: 'curl', name: 'cURL' },
  { id: 'php', name: 'PHP' },
  { id: 'js', name: 'Node.js' },
  { id: 'python', name: 'Python' },
];

const snippets: Record<string, string> = {
  curl: `curl -X POST https://api.kreatifabadi.co.id/api/v1/qris/dynamic \\
  -H "Authorization: Bearer ka_live_YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -H "Idempotency-Key: ORD-9988-ABC" \\
  -d '{
    "merchant_id": "MC-KREATIF-001",
    "amount": 50000,
    "reference": "INV-202609-001",
    "fee_type": "fixed",
    "fee_value": 1000
  }'`,

  php: `<?php

$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.kreatifabadi.co.id/api/v1/qris/dynamic",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode([
    "merchant_id" => "MC-KREATIF-001",
    "amount" => 50000,
    "reference" => "INV-202609-001",
    "fee_type" => "fixed",
    "fee_value" => 1000
  ]),
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer ka_live_YOUR_API_KEY",
    "Content-Type: application/json",
    "Idempotency-Key: " . uniqid("ord_")
  ],
]);

$response = curl_exec($curl);
$data = json_decode($response, true);
echo "QRIS Dynamic: " . $data['data']['qris_string'];`,

  js: `const axios = require('axios');

async function createDynamicQris() {
  const response = await axios.post(
    'https://api.kreatifabadi.co.id/api/v1/qris/dynamic',
    {
      merchant_id: 'MC-KREATIF-001',
      amount: 50000,
      reference: 'INV-202609-001',
      fee_type: 'fixed',
      fee_value: 1000
    },
    {
      headers: {
        'Authorization': 'Bearer ka_live_YOUR_API_KEY',
        'Idempotency-Key': 'ORD-' + Date.now()
      }
    }
  );

  console.log('Dynamic QR String:', response.data.data.qris_string);
  console.log('QR Image URL:', response.data.data.qr_image_url);
}

createDynamicQris();`,

  python: `import requests

url = "https://api.kreatifabadi.co.id/api/v1/qris/dynamic"

headers = {
    "Authorization": "Bearer ka_live_YOUR_API_KEY",
    "Content-Type": "application/json",
    "Idempotency-Key": "ORD-12345"
}

payload = {
    "merchant_id": "MC-KREATIF-001",
    "amount": 50000,
    "reference": "INV-202609-001",
    "fee_type": "fixed",
    "fee_value": 1000
}

response = requests.post(url, json=payload, headers=headers)
print(response.json())`,
};

const copySnippet = () => {
  navigator.clipboard.writeText(snippets[activeTab.value]);
  toast.success('Disalin ke Clipboard!');
};
</script>
