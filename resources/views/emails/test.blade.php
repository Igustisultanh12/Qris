@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span class="badge badge-success">Verifikasi Koneksi Sukses</span>
        <h2 style="font-size: 20px; font-weight: 800; margin: 12px 0 4px 0; color: #0f172a;">Tes Email Gateway Berhasil!</h2>
        <p style="color: #64748b; font-size: 13px; margin: 0;">Pengujian relay SMTP dan konfigurasi server pengiriman email PT Kreatif Abadi.</p>
    </div>

    <p>Halo Administrator,</p>
    <p>Pesan ini mengonfirmasi bahwa <strong>Sistem Email Gateway PT Kreatif Abadi</strong> telah terhubung dengan baik ke server SMTP dan siap digunakan untuk mengirimkan notifikasi transaksi, penagihan invoice, dan peringatan keamanan secara real-time.</p>

    <table class="table-data">
        <tr>
            <td>Waktu Pengiriman</td>
            <td>{{ now()->setTimezone('Asia/Jakarta')->format('d F Y, H:i:s') }} WIB</td>
        </tr>
        <tr>
            <td>Mailer Driver</td>
            <td>{{ $mailer ?? 'smtp' }}</td>
        </tr>
        <tr>
            <td>SMTP Host</td>
            <td>{{ $host ?? '127.0.0.1' }}</td>
        </tr>
        <tr>
            <td>Penerima Uji Coba</td>
            <td>{{ $recipient ?? '-' }}</td>
        </tr>
        <tr>
            <td>Status Koneksi</td>
            <td><strong style="color: #16a34a;">CONNECTED & ACTIVE</strong></td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 24px;">
        <a href="{{ url('/admin/email-gateway') }}" class="btn">Buka Pengaturan Gateway &rarr;</a>
    </div>
@endsection
