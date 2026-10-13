<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم حظر الوصول — Smart Syndic Security</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #090d16;
            color: #f8fafc;
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 1rem;
        }
        .blocked-card {
            background: #0f172a;
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 1.25rem;
            padding: 3rem 2rem;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.25);
        }
        .blocked-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
        .blocked-ip {
            display: inline-block;
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            font-family: monospace;
            font-size: 1.1rem;
            margin: 1rem 0;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <div class="blocked-card">
        <div class="blocked-icon">🛡️⛔</div>
        <h2 style="font-size: 1.6rem; color: #ef4444; margin: 0.5rem 0;">تم حظر الوصول إلى المنصة</h2>
        <p style="color: #94a3b8; line-height: 1.7;">
            تم إيقاف اتصالك بواسطة جدار الحماية الأمني للمنصة بسبب رصد سلوك مشبوه أو تجاوز حدود الأمان المسموح بها.
        </p>
        <div class="blocked-ip">{{ $ip }}</div>
        <p style="font-size: 0.85rem; color: #64748b;">
            إذا كنت تعتقد أن هذا الحظر تم عن طريق الخطأ، يرجى التواصل مع إدارة النظام المركزية.
        </p>
    </div>
</body>
</html>
