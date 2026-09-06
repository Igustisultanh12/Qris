@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span class="badge badge-info">Verifikasi Keamanan</span>
        <h2 style="font-size: 20px; font-weight: 800; margin: 12px 0 4px 0; color: #0f172a;">Kode Verifikasi OTP Anda</h2>
        <p style="color: #64748b; font-size: 13px; margin: 0;">Gunakan kode di bawah ini untuk memverifikasi akun Qmis Anda.</p>
    </div>

    <p>Halo <strong>{{ $user->name }}</strong>,</p>
    <p>Terima kasih telah mendaftar di platform <strong>Qmis (PT Kreatif Sky Abadi)</strong>. Untuk mengaktifkan akun Anda dan melanjutkan ke dashboard, silakan masukkan 6 digit kode OTP berikut pada halaman pendaftaran:</p>

    <div style="background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; margin: 24px 0;">
        <div style="font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #4f46e5; font-family: monospace;">
            {{ $otp }}
        </div>
        <p style="color: #64748b; font-size: 12px; margin: 8px 0 0 0;">Kode ini hanya berlaku selama <strong>15 menit</strong>. Jangan bagikan kode ini kepada siapapun.</p>
    </div>

    <p style="font-size: 13px; color: #64748b; line-height: 1.6;">
        Jika Anda tidak merasa melakukan pendaftaran akun di platform Qmis, Anda dapat mengabaikan email ini dengan aman.
    </p>

    <div style="text-align: center; margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8;">
        Tim Keamanan PT Kreatif Sky Abadi &bull; Dukungan: support@kreatifskyabadi.co.id
    </div>
@endsection
