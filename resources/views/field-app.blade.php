<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <title>تطبيق الغسيل الميداني</title>
    <link rel="manifest" href="/field-app/manifest.webmanifest">
    <link rel="stylesheet" href="/field-app/styles.css">
</head>
<body>
<main class="container">
    <h1>تطبيق الغسيل الميداني</h1>
    <p id="connectionStatus" class="status">جاري التحقق من الاتصال...</p>

    <section class="card">
        <h2>تسجيل الدخول</h2>
        <form id="loginForm">
            <label>البريد الإلكتروني
                <input id="email" type="email" required>
            </label>
            <label>كلمة المرور
                <input id="password" type="password" required>
            </label>
            <button type="submit">دخول</button>
        </form>
    </section>

    <section class="card">
        <h2>تسجيل عملية غسيل</h2>
        <form id="readingForm">
            <label>بحث عن زبون
                <input id="customerSearch" type="text" placeholder="ID أو الاسم">
            </label>
            <label>الزبون
                <select id="customerId" required></select>
            </label>
            <label>تاريخ العملية
                <input id="readingDate" type="date" required>
            </label>
            <label>وزن الغسيل (كغ)
                <input id="readingValue" type="number" step="0.001" min="0.001" required>
            </label>
            <label>ملاحظة
                <input id="note" type="text">
            </label>
            <button type="submit">حفظ محليًا</button>
        </form>
        <small id="saveResult"></small>
    </section>

    <section class="card">
        <h2>المزامنة</h2>
        <div class="actions">
            <button id="refreshCustomersBtn" type="button">تحديث قائمة الزبائن</button>
            <button id="syncBtn" type="button">مزامنة الآن</button>
        </div>
        <p id="queueStatus"></p>
        <pre id="syncLog"></pre>
    </section>

    <section class="card">
        <h2>العمليات المحلية المعلقة</h2>
        <p id="pendingListEmpty">لا توجد عمليات محلية معلقة.</p>
        <div id="pendingList"></div>
    </section>
</main>
<script src="/field-app/app.js?v={{ filemtime(public_path('field-app/app.js')) }}"></script>
</body>
</html>
