@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span class="badge badge-info">Selamat Datang</span>
        <h2 style="font-size: 20px; font-weight: 800; margin: 12px 0 4px 0; color: #0f172a;">Selamat Bergabung di Qmis!</h2>
        <p style="color: #64748b; font-size: 13px; margin: 0;">Akun bisnis Anda telah aktif dengan akses uji coba gratis 14 hari.</p>
    </div>

    <p>Halo <strong>{{ $user->name }}</strong>,</p>
    <p>Terima kasih telah mendaftar di <strong>Qmis (PT Kreatif Sky Abadi)</strong>. Kini Anda dapat mengubah QRIS statis toko/bisnis Anda menjadi QRIS dinamis dengan nominal rupiah otomatis dan integrasi REST API instan.</p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 20px 0;">
        <h4 style="margin: 0 0 10px 0; font-size: 13px; color: #334155; text-transform: uppercase;">Detail Akun Anda</h4>
        <table class="table-data" style="margin: 0;">
            <tr>
                <td>Nama Bisnis</td>
                <td>{{ $customer->business_name }}</td>
            </tr>
            <tr>
                <td>Email Akun</td>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <td>Paket Aktif</td>
                <td>Basic (Trial 14 Hari)</td>
            </tr>
            <tr>
                <td>Kuota Merchant</td>
                <td>Hingga {{ $customer->max_merchants }} Merchant QRIS</td>
            </tr>
        </table>
    </div>

    <h4 style="font-size: 14px; margin: 20px 0 8px 0; color: #0f172a;">Langkah Selanjutnya:</h4>
    <ol style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8; color: #475569;">
        <li>Masuk ke Dashboard Merchant Anda.</li>
        <li>Daftarkan QRIS statis pertama Anda melalui menu <strong>Merchant</strong> (bisa scan pakai webcam!).</li>
        <li>Buat transaksi uji coba melalui <strong>Generator QRIS</strong> atau panggil REST API kami.</li>
    </ol>

    <div style="text-align: center; margin-top: 28px;">
        <a href="{{ url('/dashboard') }}" class="btn">Buka Dashboard Saya &rarr;</a>
    </div>
@endsection
