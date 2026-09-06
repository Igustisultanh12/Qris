import jsQR from 'jsqr';

/**
 * Clean raw QR data to extract standard EMVCo QRIS string.
 */
export function cleanQrisPayload(raw: string): string {
  const trimmed = raw.trim();
  const qrisIndex = trimmed.indexOf('000201');
  if (qrisIndex >= 0) {
    return trimmed.substring(qrisIndex);
  }
  return trimmed;
}

/**
 * Attempt to decode a canvas element with jsQR.
 */
function tryDecodeCanvas(canvas: HTMLCanvasElement): string | null {
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  if (!ctx) return null;

  try {
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, canvas.width, canvas.height, {
      inversionAttempts: 'attemptBoth',
    });
    return code?.data ? cleanQrisPayload(code.data) : null;
  } catch (err) {
    console.error('jsQR decode canvas error:', err);
    return null;
  }
}

/**
 * Decode QR from an HTMLImageElement with multi-scale fallback strategy.
 * Handles high-resolution camera photos, standard web images, and low-res screenshots.
 */
export function decodeQrFromImageElement(img: HTMLImageElement): string | null {
  const canvas = document.createElement('canvas');
  const naturalW = img.naturalWidth || img.width;
  const naturalH = img.naturalHeight || img.height;

  if (!naturalW || !naturalH) return null;

  // 1. Try original resolution
  canvas.width = naturalW;
  canvas.height = naturalH;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  if (!ctx) return null;

  ctx.drawImage(img, 0, 0);
  let res = tryDecodeCanvas(canvas);
  if (res) return res;

  const maxDim = Math.max(naturalW, naturalH);

  // 2. Downscale if large (> 1000px) - smartphone camera photos
  if (maxDim > 1000) {
    const scale = 800 / maxDim;
    canvas.width = Math.round(naturalW * scale);
    canvas.height = Math.round(naturalH * scale);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    res = tryDecodeCanvas(canvas);
    if (res) return res;
  }

  // 3. Medium scale (500px)
  if (maxDim > 600) {
    const scale = 500 / maxDim;
    canvas.width = Math.round(naturalW * scale);
    canvas.height = Math.round(naturalH * scale);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    res = tryDecodeCanvas(canvas);
    if (res) return res;
  }

  // 4. Slightly upscale if small (< 300px)
  if (maxDim < 300) {
    const scale = 600 / maxDim;
    canvas.width = Math.round(naturalW * scale);
    canvas.height = Math.round(naturalH * scale);
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    res = tryDecodeCanvas(canvas);
    if (res) return res;
  }

  return null;
}

/**
 * Decode a File object (PNG, JPG, WEBP, SVG) into a QR payload string.
 */
export function decodeQrFromFile(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    if (!file.type.startsWith('image/')) {
      return reject(new Error('File yang dipilih bukan gambar yang didukung.'));
    }

    const reader = new FileReader();
    reader.onerror = () => reject(new Error('Gagal membaca file gambar.'));
    reader.onload = () => {
      const img = new Image();
      img.onerror = () => reject(new Error('Gagal memuat gambar ke browser.'));
      img.onload = () => {
        const payload = decodeQrFromImageElement(img);
        if (payload) {
          resolve(payload);
        } else {
          reject(new Error('QR Code tidak terdeteksi pada gambar. Pastikan gambar memiliki kontras yang baik, tidak buram, dan kode QR terlihat utuh.'));
        }
      };
      img.src = reader.result as string;
    };
    reader.readAsDataURL(file);
  });
}
