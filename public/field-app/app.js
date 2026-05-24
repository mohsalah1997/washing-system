const DB_NAME = "meter-field-db";
const DB_VERSION = 1;
const CUSTOMER_STORE = "customers_cache";
const QUEUE_STORE = "pending_readings";
const SYNC_LOG_STORE = "sync_log";

const connectionStatusEl = document.getElementById("connectionStatus");
const queueStatusEl = document.getElementById("queueStatus");
const syncLogEl = document.getElementById("syncLog");
const saveResultEl = document.getElementById("saveResult");
const loginForm = document.getElementById("loginForm");
const readingForm = document.getElementById("readingForm");
let customerSelect = document.getElementById("customerId");
const customerSearch = document.getElementById("customerSearch");
const readingValueInput = document.getElementById("readingValue");
const noteInput = document.getElementById("note");
const readingDateInput = document.getElementById("readingDate");
const refreshCustomersBtn = document.getElementById("refreshCustomersBtn");
const syncBtn = document.getElementById("syncBtn");
const pendingListEl = document.getElementById("pendingList");
const pendingListEmptyEl = document.getElementById("pendingListEmpty");

const api = {
    token: localStorage.getItem("field_api_token") || "",
};
let currentUserId = parseInt(localStorage.getItem("field_user_id") || "", 10) || null;
let editingClientUuid = null;

function updateConnectionStatus() {
    connectionStatusEl.textContent = navigator.onLine ? "متصل بالإنترنت" : "بدون إنترنت - الحفظ المحلي فعال";
}

function openDb() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains(CUSTOMER_STORE)) {
                db.createObjectStore(CUSTOMER_STORE, { keyPath: "id" });
            }
            if (!db.objectStoreNames.contains(QUEUE_STORE)) {
                db.createObjectStore(QUEUE_STORE, { keyPath: "client_uuid" });
            }
            if (!db.objectStoreNames.contains(SYNC_LOG_STORE)) {
                db.createObjectStore(SYNC_LOG_STORE, { autoIncrement: true });
            }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function tx(storeName, mode, callback) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(storeName, mode);
        const store = transaction.objectStore(storeName);
        const result = callback(store);
        transaction.oncomplete = () => resolve(result);
        transaction.onerror = () => reject(transaction.error);
    });
}

async function getAll(storeName) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const request = db.transaction(storeName, "readonly").objectStore(storeName).getAll();
        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

function authHeaders() {
    return {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${api.token}`,
    };
}

function bindCustomerSelectListeners() {
    // no-op: weight entry is independent per order
}

function rebuildCustomerSelectElement() {
    const oldSelect = customerSelect;
    const newSelect = oldSelect.cloneNode(false);
    oldSelect.parentNode.replaceChild(newSelect, oldSelect);
    customerSelect = newSelect;
    bindCustomerSelectListeners();
}

async function fetchCustomersFromServer() {
    if (!api.token || !navigator.onLine) {
        return false;
    }

    const response = await fetch("/api/field/customers", {
        method: "GET",
        headers: authHeaders(),
    });

    if (!response.ok) {
        throw new Error("فشل تحميل الزبائن من السيرفر");
    }

    const data = await response.json();
    await tx(CUSTOMER_STORE, "readwrite", (store) => {
        store.clear();
        data.customers.forEach((customer) => store.put(customer));
    });

    return data.customers;
}

function renderCustomersFromList(customers) {
    const searchText = (customerSearch.value || "").trim().toLowerCase();
    const filtered = customers.filter((customer) => {
        if (!searchText) return true;
        return `${customer.id}`.includes(searchText) || customer.name.toLowerCase().includes(searchText);
    });

    customerSelect.innerHTML = "";
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "اختر الزبون";
    placeholder.disabled = true;
    placeholder.selected = true;
    customerSelect.appendChild(placeholder);
    filtered.forEach((customer) => {
        const option = document.createElement("option");
        option.value = customer.id;
        option.textContent = `#${customer.id} - ${customer.name}`;
        customerSelect.appendChild(option);
    });
    customerSelect.selectedIndex = 0;
}

async function renderCustomers() {
    const all = await getAll(CUSTOMER_STORE);
    const searchText = (customerSearch.value || "").trim().toLowerCase();
    const filtered = all.filter((customer) => {
        if (!searchText) return true;
        return `${customer.id}`.includes(searchText) || customer.name.toLowerCase().includes(searchText);
    });

    customerSelect.innerHTML = "";
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "اختر الزبون";
    placeholder.disabled = true;
    placeholder.selected = true;
    customerSelect.appendChild(placeholder);
    filtered.forEach((customer) => {
        const option = document.createElement("option");
        option.value = customer.id;
        option.textContent = `#${customer.id} - ${customer.name}`;
        customerSelect.appendChild(option);
    });
    customerSelect.selectedIndex = 0;
}

function newUuid() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
    // RFC4122-ish UUID v4 fallback for older environments.
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (c) => {
        const r = Math.random() * 16 | 0;
        const v = c === "x" ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function isValidUuid(value) {
    return /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(String(value || ""));
}

function ensureValidUuid(value) {
    return isValidUuid(value) ? value : newUuid();
}

async function queueReading(payload) {
    await tx(QUEUE_STORE, "readwrite", (store) => store.put(payload));
    await updateQueueStatus();
    await renderPendingList();
}

async function getPendingReadingsForCurrentUser() {
    if (!currentUserId) {
        return [];
    }
    const queue = await getAll(QUEUE_STORE);
    return queue.filter((item) => Number(item.owner_user_id) === Number(currentUserId));
}

async function findExistingPendingForCustomer(customerId) {
    const pending = await getPendingReadingsForCurrentUser();
    return pending.find((item) => Number(item.customer_id) === Number(customerId)) || null;
}

async function updateQueueStatus() {
    const queue = await getPendingReadingsForCurrentUser();
    queueStatusEl.textContent = `عدد العمليات بانتظار المزامنة: ${queue.length}`;
}

async function appendLog(line) {
    const timestamped = `[${new Date().toLocaleString()}] ${line}`;
    await tx(SYNC_LOG_STORE, "readwrite", (store) => store.add({ line: timestamped }));
    const logs = await getAll(SYNC_LOG_STORE);
    syncLogEl.textContent = logs.slice(-40).map((item) => item.line).join("\n");
}

async function doSync() {
    if (!navigator.onLine) {
        await appendLog("لا يوجد إنترنت، تأجيل المزامنة.");
        return;
    }
    if (!api.token) {
        await appendLog("لا يوجد توكن دخول. سجّل الدخول أولًا.");
        return;
    }

    const queue = await getPendingReadingsForCurrentUser();
    if (!queue.length) {
        await appendLog("لا يوجد عناصر للمزامنة.");
        return;
    }

    const batch = queue.slice(0, 20).map((item) => {
        const normalizedUuid = ensureValidUuid(item.client_uuid);
        if (normalizedUuid !== item.client_uuid) {
            const patched = { ...item, client_uuid: normalizedUuid };
            tx(QUEUE_STORE, "readwrite", (store) => store.put(patched));
            return patched;
        }
        return item;
    });
    const requestBody = JSON.stringify({ readings: batch });

    let response;
    try {
        response = await fetch("/api/field/readings/sync", {
            method: "POST",
            headers: authHeaders(),
            body: requestBody,
        });
    } catch (error) {
        await appendLog("فشلت المزامنة مع السيرفر.");
        return;
    }

    if (!response.ok) {
        await appendLog("فشلت المزامنة مع السيرفر.");
        return;
    }

    const data = await response.json();
    await appendLog(`تمت مزامنة ${data.summary.synced} / ${data.summary.received}.`);

    for (const result of data.results) {
        if (result.status === "synced") {
            await tx(QUEUE_STORE, "readwrite", (store) => store.delete(result.client_uuid));
        } else if (result.status === "rejected") {
            if (result.message === "Customer not found.") {
                // Stale local item (usually after DB reset/reseed). Remove it to prevent endless sync failures.
                await tx(QUEUE_STORE, "readwrite", (store) => store.delete(result.client_uuid));
                await appendLog(`تم حذف سجل معلق لأن الزبون غير موجود على السيرفر. أعد تحديث قائمة الزبائن ثم أدخل السجل من جديد.`);
            } else {
                await appendLog(`فشل سطر في المزامنة: ${result.message || "سبب غير معروف"}`);
            }
        }
    }

    await updateQueueStatus();
    await renderPendingList();
}

loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    const response = await fetch("/api/field/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({
            email,
            password,
            device_name: "android-field-app",
        }),
    });

    if (!response.ok) {
        await appendLog("فشل تسجيل الدخول.");
        return;
    }

    const data = await response.json();
    api.token = data.token;
    currentUserId = Number(data.user?.id || 0) || null;
    localStorage.setItem("field_api_token", api.token);
    localStorage.setItem("field_user_id", String(currentUserId || ""));
    await appendLog("تم تسجيل الدخول بنجاح.");
    await updateQueueStatus();
    await renderPendingList();
});

readingForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!currentUserId) {
        saveResultEl.textContent = "سجل الدخول أولًا.";
        return;
    }

    const customerId = parseInt(document.getElementById("customerId").value, 10);
    const existing = editingClientUuid
        ? null
        : await findExistingPendingForCustomer(customerId);
    const clientUuid = editingClientUuid || existing?.client_uuid || newUuid();

    const payload = {
        client_uuid: clientUuid,
        customer_id: customerId,
        reading_date: readingDateInput.value,
        reading_value: parseFloat(readingValueInput.value),
        note: noteInput.value || null,
        owner_user_id: currentUserId,
        saved_at: new Date().toISOString(),
    };

    await queueReading(payload);
    const actionText = editingClientUuid ? "تم تعديل السجل المحلي." : (existing ? "تم تحديث السجل المحلي الحالي لهذا الزبون." : "تم حفظ السجل محليًا.");
    saveResultEl.textContent = actionText;
    await appendLog(`${actionText} للزبون #${payload.customer_id}.`);
    editingClientUuid = null;
    readingForm.reset();
    readingDateInput.valueAsDate = new Date();
});

customerSearch.addEventListener("input", () => {
    renderCustomers();
});

bindCustomerSelectListeners();

refreshCustomersBtn.addEventListener("click", async () => {
    try {
        const freshCustomers = await fetchCustomersFromServer();
        customerSearch.value = "";
        rebuildCustomerSelectElement();
        renderCustomersFromList(freshCustomers);
        await appendLog("تم تحديث قائمة الزبائن من السيرفر.");
    } catch (error) {
        await appendLog(error.message);
    }
});

syncBtn.addEventListener("click", async () => {
    await doSync();
});

window.addEventListener("online", async () => {
    updateConnectionStatus();
    await doSync();
});

window.addEventListener("offline", updateConnectionStatus);

async function renderPendingList() {
    const pending = await getPendingReadingsForCurrentUser();
    pendingListEl.innerHTML = "";
    if (!pending.length) {
        pendingListEmptyEl.style.display = "block";
        return;
    }

    pendingListEmptyEl.style.display = "none";
    const customers = await getAll(CUSTOMER_STORE);
    pending
        .sort((a, b) => new Date(b.saved_at).getTime() - new Date(a.saved_at).getTime())
        .forEach((item) => {
            const customer = customers.find((c) => Number(c.id) === Number(item.customer_id));
            const row = document.createElement("div");
            row.className = "pending-item";
            row.innerHTML = `
                <div><strong>${customer ? customer.name : "#" + item.customer_id}</strong></div>
                <div>التاريخ: ${item.reading_date}</div>
                <div>الوزن: ${item.reading_value} كغ</div>
                <div>الملاحظة: ${item.note || "-"}</div>
                <div class="pending-actions">
                    <button type="button" data-action="edit" data-id="${item.client_uuid}">تعديل</button>
                    <button type="button" data-action="delete" data-id="${item.client_uuid}">حذف</button>
                </div>
            `;
            pendingListEl.appendChild(row);
        });
}

pendingListEl.addEventListener("click", async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLButtonElement)) {
        return;
    }
    const clientUuid = target.dataset.id;
    const action = target.dataset.action;
    if (!clientUuid || !action) {
        return;
    }

    const pending = await getPendingReadingsForCurrentUser();
    const item = pending.find((entry) => entry.client_uuid === clientUuid);
    if (!item) {
        return;
    }

    if (action === "edit") {
        editingClientUuid = item.client_uuid;
        customerSelect.value = String(item.customer_id);
        readingDateInput.value = item.reading_date;
        readingValueInput.value = item.reading_value;
        noteInput.value = item.note || "";
        saveResultEl.textContent = "تم تحميل العملية للتعديل.";
    } else if (action === "delete") {
        await tx(QUEUE_STORE, "readwrite", (store) => store.delete(item.client_uuid));
        if (editingClientUuid === item.client_uuid) {
            editingClientUuid = null;
        }
        await updateQueueStatus();
        await renderPendingList();
        await appendLog(`تم حذف سجل محلي للزبون #${item.customer_id}.`);
    }
});

async function init() {
    updateConnectionStatus();
    readingDateInput.valueAsDate = new Date();

    if ("serviceWorker" in navigator) {
        // Force-disable stale PWA caches during active development/debug.
        const registrations = await navigator.serviceWorker.getRegistrations();
        await Promise.all(registrations.map((registration) => registration.unregister()));
        if (window.caches) {
            const cacheKeys = await caches.keys();
            await Promise.all(cacheKeys.map((key) => caches.delete(key)));
        }
    }

    await updateQueueStatus();

    try {
        await fetchCustomersFromServer();
    } catch (_error) {
        // Ignore and fallback to cache.
    }
    await renderCustomers();
    await renderPendingList();
    await doSync();
}

init();
