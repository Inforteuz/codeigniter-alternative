# CodeIgniter Alternative — 8.5–9 darajaga chiqish yo‘li

**Maqsad:** Hissiyotlarni hisobga olmasdan, faqat texnik jihatdan framework sifatini oshirish.

---

## 1. XAVFSIZLIK (majburiy)

### 1.1 BaseModel::search() — SQL injection

**Muammo:** `search()` ichida `$term` to‘g‘ridan-to‘g‘ri LIKE ga qo‘yiladi, `addslashes` yetarli emas. Field nomlari ham tekshirilmasa xavfli.

**Qilish:**
- `$fields` ni faqat modelda ruxsat etilgan (masalan, `$searchable = []`) ro‘yxatdan olish.
- `$term` ni hech qachon SQL stringiga qo‘shmaslik; faqat placeholder va `$this->lastParams` orqali bind qilish.
- Misol: `like($field, $term, 'both')` ni ichki qatorda chaqirish yoki parametrli WHERE yig‘ish.

### 1.2 BaseModel::increment() / decrement()

**Muammo:** `new \PDOStatement("({$column} + {$amount})")` noto‘g‘ri — PDOStatement bunday ishlatilmaydi, `$column`/`$amount` escape qilinmagan.

**Qilish:**
- `UPDATE table SET column = column + :amount WHERE ...` kabi bitta SQL yozish.
- `:amount` ni `$amount` bilan bind qilish; `$column` ni whitelist (masalan, faqat `$this->fillable` yoki alohida ro‘yxat) orqali tekshirish.

### 1.3 Session fixation

**Qilish:** Login muvaffaqiyatli bo‘lgach `session_regenerate_id(true)` chaqirish (Auth yoki login controllerda).

### 1.4 CSRF token vaqtinchalik

**Qilish:** Token ni sessionda saqlashdan tashqari, kerak bo‘lsa per-request yoki limited lifetime (masalan, 1 soat) qo‘shish va eski tokenlarni rad etish.

---

## 2. ARXITEKTURA VA KOD TUZILISHI

### 2.1 Composer PSR-4 autoload

**Muammo:** Custom autoloader barcha namespace’larni qoplamaydi (Composers, Routes, app/Core, app/Database, Helpers va hokazo). Yangi klass qo‘shganda qo‘lda path qo‘shish kerak.

**Qilish:**
- `composer.json` da:
  - `"App\\": "app/"`
  - `"System\\": "system/"`
- `composer dump-autoload` va `autoloader.php` da faqat `require_once __DIR__ . '/vendor/autoload.php'` qoldirish (yoki eski autoloaderni asta-sekin o‘chirish).
- Barcha klasslar namespace va papka strukturasiga mosligini tekshirish.

### 2.2 BaseController bo‘linishi (~1300 qator)

**Muammo:** Bitta klassda request, response, session, cookie, view, validation, flash, cache, file upload, pagination — SRP buziladi, test qilish va o‘zgartirish qiyin.

**Qilish:**
- **Traits** (qisqa muddat): `HasRequest`, `HasSession`, `HasView`, `HasValidation`, `HasFlash` — har biri o‘z metodlari.
- **Yoki** alohida servislar: `Request`, `Response`, `Session`, `ViewRenderer` — Container’da register qilib, controller’da faqat `$this->request`, `$this->view` kabi inject qilish.
- BaseController faqat ulardan foydalanadigan ingichka “orchestrator” bo‘lsin.

### 2.3 Xato ko‘rsatish birlashtirilishi

**Muammo:** `showError()` va `logError()` Router va BaseController’da takrorlanadi; HTML inline Router ichida uzoq.

**Qilish:**
- Yagona `System\Error\ErrorRenderer` (yoki `App\Core\Error\ErrorPageRenderer`): `render(int $code, string $message = '')`, `log(string $message)`.
- Router va BaseController faqat shu klassni chaqirsin.
- Error sahifalar uchun HTML ni `app/Views/errors/` dagi fayllarga yoki bitta template’ga olib chiqish; fallback HTML minimal bo‘lsin.

### 2.4 Session boshqaruvini birlashtirish

**Muammo:** Session `index.php` va BaseController’da ikki joyda tekshiriladi/sozlanadi.

**Qilish:** Session start va sozlashni bitta joyda qilish (masalan, `System\Core\Session::start()` yoki bootstrap fayl), index.php faqat uni chaqirsin. Controller session’dan faqat ma’lumot o‘qiydi/yozadi.

---

## 3. MODEL VA MA’LUMOTLAR QATLAMI

### 3.1 BaseModel::search() — xavfsiz va izchil

**Qilish:** Yuqoridagi xavfsizlikdan tashqari, `$searchable` property qo‘shish (masalan `protected $searchable = ['name', 'email']`). `search($term)` faqat shu ustunlar bo‘yicha `like()` yoki parametrli WHERE yig‘sin.

### 3.2 Soft delete filtri avtomatik

**Muammo:** `useSoftDeletes` bo‘lsa ham, har bir `get()`/`first()` da avtomatik `WHERE deleted_at IS NULL` qo‘shilmayapti (faqat `onlyDeleted()` bor).

**Qilish:** `buildSelectQuery()` da agar `$this->useSoftDeletes` bo‘lsa va `onlyDeleted()` chaqirilmagan bo‘lsa, default holda `deleted_at IS NULL` qo‘shish. `withDeleted()` chaqirilganda bu filterni o‘chirish.

### 3.3 Database — connection config

**Qilish:** DB parol va maxfiy ma’lumotlar `.env` dan kelishini tasdiqlash; production’da default bo‘sh parol bo‘lmasin (env’da majburiy tekshiruv yoki bootstrap’da check).

---

## 4. ROUTER VA REQUEST/RESPONSE

### 4.1 Request obyekti

**Muammo:** Hozir `$_GET`, `$_POST`, `$_SERVER` to‘g‘ridan-to‘g‘ri ishlatiladi; test qilish va mock qilish qiyin.

**Qilish:** `System\Http\Request` (yoki `App\Core\Http\Request`) — property’lar: `method`, `uri`, `query`, `post`, `headers`, `files`. Constructor’da globallardan to‘ldirish. Router va Controller faqat shu obyekt bilan ishlashi (ixtiyoriy, ammo 8.5+ uchun yaxshi).

### 4.2 Response obyekti

**Qilish:** `respondWithJSON()`, `setHeader()`, `setStatusCode()` kabi metodlarni `System\Http\Response` ga ko‘chirish. Controller’da `$this->response->json($data, 200)` kabi yagona interfeys. Bu test va middleware’lar uchun qulay.

### 4.3 405 Method Not Allowed

**Qilish:** Route mavjud, lekin method mos kelmasa (masalan, POST qilingan, faqat GET ro‘yxatda) 405 qaytarish va `Allow` header’da ruxsat etilgan metodlarni yozish.

---

## 5. VIEW VA FRONTEND

### 5.1 View cache key

**Muammo:** View cache kalitida `$this->request['uri']` ishlatilishi — bir xil URI da turli query string bo‘lsa ham bitta cache. Kerak bo‘lsa query’ni ham hisobga olish yoki cache’ni faqat GET va query’siz sahifalarga qo‘llash.

### 5.2 Escape by default

**Qilish:** View’da chiqariladigan o‘zgaruvchilar uchun yagona helper, masalan `e($var)` — `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`. Hujjatda “raw chiqarish kerak bo‘lsa ataylab `{!! $var !!}` yoki `raw($var)`” deb yozish — XSS kamayadi.

### 5.3 Asset versioning

**Qilish:** `asset($path)` ga ixtiyoriy version parametri (yoki `.env` da `ASSET_VERSION`) qo‘shish — cache busting uchun (`style.css?v=123`).

---

## 6. CONFIGURATION VA ENVIRONMENT

### 6.1 Versiya birligi

**Qilish:** Bitta versiya manbai: masalan `config/app.php` yoki `composer.json` ["extra"]["version"]. Barcha README, FRAMEWORK_GUIDE, error footer, kod ichidagi @version shu yerdan o‘qisin yoki bitta konstanta.

### 6.2 Config cache (ixtiyoriy)

**Qilish:** Production’da `.env` o‘rniga bitta `config/cached.php` (array) generate qilib, Env::get() avval shu faylni tekshirishi — so‘rovda fayl kamroq o‘qiladi.

---

## 7. TESTLAR VA SIFAT

### 7.1 PHPUnit bilan asosiy oqimlar

**Qilish:**
- Router: berilgan URI + method uchun to‘g‘ri controller::method chaqirilishini assert (mock yoki test controller).
- Container: `bind`/`make`/`singleton` to‘g‘ri ishlashi.
- Validation: bir nechta rule uchun success/error.
- BaseModel: where, first, get (SQLite in-memory yoki test DB).

Buning uchun index.php yoki bootstrap’ni “kernel” orqali ishga tushirish kerak bo‘lishi mumkin (request ni inject qilish).

### 7.2 .env.testing

**Qilish:** Test uchun alohida `.env.testing` (yoki `phpunit.xml` da env) — test DB, `APP_DEBUG=false` va hokazo. Asl `.env` production’da ishlatilmasin testda.

---

## 8. HUJJATLAR VA DX

### 8.1 API doc (method signature)

**Qilish:** Kritik klasslar (BaseController, BaseModel, Router, Container) uchun @param, @return, @throws to‘ldirish. IDE va hujjat generator (phpDocumentor/Psalm) uchun foyda.

### 8.2 CHANGELOG.md

**Qilish:** Versiyalar bo‘yicha o‘zgarishlar ro‘yxati (xavfsizlik, yangi feature, breaking change). Foydalanuvchilar yangilashda nima o‘zgarganini ko‘radi.

### 8.3 Breaking change’lar hujjati

**Qilish:** Kelajakda 2.x → 3.x kabi o‘tishda “migration guide” (eski metodlar o‘rniga yangilari, deprecated ro‘yxati).

---

## 9. KICHIK AMO QILISH MUMKIN BO‘LGANLAR

- **filterMessage():** `$$$$` typo tuzatish; regex va allowed character’lar aniq bo‘lsin.
- **base_url():** Subdirectory’da (masalan `/myapp/`) ishlashni tekshirish; `SCRIPT_NAME` bilan to‘g‘ri base path hisoblash.
- **Middleware:** Pipeline’da exception tutganda HTTP status (403/401) va log’ni bir xil joyda boshqarish.
- **Rate limit:** Middleware’da limit oshganda 429 qaytarish va `Retry-After` header (agar bor bo‘lsa).
- **CORS:** Preflight (OPTIONS) javobini router’da yoki CorsMiddleware’da aniq handle qilish.

---

## 10. USTUNLIK BO‘YICHA QISQA RO‘YXAT

| Tartib | Qisqa tavsiya | Ta’sir |
|--------|----------------|--------|
| 1 | BaseModel::search() va increment() xavfsizlik tuzatish | Yuqori |
| 2 | Composer PSR-4 autoload | Yuqori |
| 3 | ErrorRenderer birlashtirish, showError/logError dublikat olib tashlash | O‘rta |
| 4 | BaseController trait yoki servislarga bo‘lish | O‘rta |
| 5 | Session boshqaruvini bitta joyga olib kelish | O‘rta |
| 6 | Request/Response obyektlari (ixtiyoriy, lekin 9 ga yaqinlashtiradi) | O‘rta |
| 7 | Session regeneration login’da, CSRF lifetime | O‘rta |
| 8 | Soft delete default filter, view escape by default | Past–o‘rta |
| 9 | PHPUnit testlar (router, container, validation, model) | O‘rta |
| 10 | Versiya birligi, CHANGELOG, API doc | Past |

---

**Xulosa:** 1–4 bandlar bajarilsa va xavfsizlik nuqtalari to‘liq yopilsa, framework 8.5 ga yetadi. 5–7 qo‘shilsa 9 ga yaqinlashadi. 8–10 — uzoq muddatda barqarorlik va ishonch uchun.
