<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Notifikasi Qmis - PT Kreatif Sky Abadi' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            padding: 32px 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: #c7d2fe;
        }
        .content {
            padding: 32px 30px;
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-info { background: #e0e7ff; color: #3730a3; }
        .footer {
            background-color: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            font-size: 12px;
            color: #64748b;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            margin-top: 16px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            font-size: 13px;
        }
        .table-data td {
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-data td:first-child {
            color: #64748b;
        }
        .table-data td:last-child {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Qmis</h1>
            <p>PT Kreatif Sky Abadi &bull; Platform SaaS & API QRIS Dinamis</p>
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            <p style="margin: 0 0 6px 0;"><strong>PT Kreatif Sky Abadi</strong> &bull; Gedung Cyber Lt. 5, Jl. Kuningan Barat, Jakarta Selatan</p>
            <p style="margin: 0;">Email otomatis dari sistem Qmis Gateway. Mohon tidak membalas email ini secara langsung.</p>
        </div>
    </div>
</body>
</html>
