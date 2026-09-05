@extends('emails.layout')

@section('content')
    <div style="text-align: center; margin-bottom: 24px;">
        <span class="badge badge-success">Pembayaran Berhasil</span>
        <h2 style="font-size: 20px; font-weight: 800; margin: 12px 0 4px 0; color: #0f172a;">Transaksi QRIS Terbayar!</h2>
        <p style="color: #64748b; font-size: 13px; margin: 0;">Pembayaran QRIS dinamis telah diverifikasi secara otomatis.</p>
    </div>

    <p>Halo,</p>
    <p>Transaksi QRIS dinamis dengan nomor referensi <strong>{{ $transaction->reference }}</strong> telah berhasil dibayar oleh pelanggan.</p>

    <table class="table-data">
        <tr>
            <td>ID Transaksi</td>
            <td style="font-family: monospace;">{{ $transaction->uuid ?? $transaction->id }}</td>
        </tr>
        <tr>
            <td>Nomor Referensi</td>
            <td style="font-family: monospace;">{{ $transaction->reference }}</td>
        </tr>
        <tr>
            <td>Merchant</td>
            <td>{{ $transaction->merchant->name ?? 'Merchant' }}</td>
        </tr>
        <tr>
            <td>Nominal Pokok</td>
            <td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Biaya Tambahan (Fee)</td>
            <td>Rp {{ number_format($transaction->fee_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="font-size: 15px; font-weight: bold; color: #0f172a;">Total Terbayar</td>
            <td style="font-size: 16px; font-weight: bold; color: #4f46e5;">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Waktu Transaksi</td>
            <td>{{ $transaction->paid_at ? \Carbon\Carbon::parse($transaction->paid_at)->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB' : now()->format('d M Y, H:i:s') }}</td>
        </tr>
    </table>

    <p style="font-size: 12px; color: #64748b; margin-top: 20px;">
        Dana transaksi telah diteruskan langsung ke rekening PJP Acquirer merchant Anda sesuai dengan QRIS statis terdaftar.
    </p>

    <div style="text-align: center; margin-top: 24px;">
        <a href="{{ url('/customer/transactions') }}" class="btn">Lihat Riwayat Transaksi &rarr;</a>
    </div>
@endsection
