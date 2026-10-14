#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ST7735.h>
#include <SPI.h>

// ==================== KONFIGURASI WIFI & BACKEND ====================
// ESP32 dan laptop backend HARUS tersambung ke WiFi yang sama.
#define WIFI_SSID       "SANKEN_TAMU"
#define WIFI_PASSWORD   "SankenPluit"

// Alamat backend Laravel (php artisan serve --host=0.0.0.0 --port=8000).
const char* SERVER_ORIGIN = "http://192.168.6.246:8000";

// Harus sama dengan kolom mesin_qr.uid_esp di database.
const char* ID_ESP        = "ESP32_01";

// Pin LCD ST7735S
#define TFT_CS     5
#define TFT_RST    4
#define TFT_DC     2

// Pin 3 Push Button
#define BTN_UP     13
#define BTN_DOWN   12
#define BTN_SELECT 14

#define WARNA_ABU  0x7BEF

Adafruit_ST7735 tft = Adafruit_ST7735(TFT_CS, TFT_DC, TFT_RST);

// ==================== STATE SISTEM ====================
// GET /api/esp/state HANYA dipanggil saat ESP menyala dan saat menu "Refresh Data".
// Tidak ada ping/polling berkala. Request lain hanya POST /api/esp/status saat user ganti status.
int     selectedMenu     = 0;
String  namaMesin        = "-";
String  statusAktif      = "READY";
String  operatorAktif    = "";
String  jenisOperator    = "";     // OPERATOR / TEKNISI / SETUP (dari backend)
bool    serverOnline     = false;  // hasil request terakhir
int     kodeHttpTerakhir = 0;

// Timer dihitung lokal dari nilai backend terakhir, jadi tetap jalan tanpa request.
long          timerBackend     = 0;
unsigned long timerDiambilPada = 0;

const uint16_t TIMEOUT_HTTP    = 8000; // ms menunggu respon server
const uint16_t TIMEOUT_CONNECT = 3000; // ms membuka koneksi TCP
const unsigned long TIMEOUT_WIFI = 10000;

// ==================== PILIHAN STATUS & HAK AKSES ====================
// Nilai "status" dikirim apa adanya ke backend (POST /api/esp/status).
// Setiap status hanya bisa dipilih oleh jenis operator tertentu:
//   PRODUKSI -> Operator, MOULD -> Teknisi, SETTER -> Setup
// Memilih status yang sedang aktif akan mengembalikan mesin ke READY.
struct OpsiStatus {
  const char* status;   // dikirim ke backend
  const char* jenis;    // jenis operator yang boleh memilih
  const char* singkat;  // label pendek di status bar
  uint16_t    warna;
};

const int JUMLAH_STATUS = 3;
const OpsiStatus listStatus[JUMLAH_STATUS] = {
  {"PRODUKSI", "OPERATOR", "PRD", ST7735_GREEN},
  {"MOULD",    "TEKNISI",  "MLD", ST7735_YELLOW},
  {"SETTER",   "SETUP",    "SET", ST7735_MAGENTA},
};
const char* STATUS_DIAM = "READY";
const int   IDX_REFRESH = JUMLAH_STATUS;

// Cache UI (mencegah flickering: status bar hanya digambar ulang jika berubah)
String   namaTerakhirDigambar = "";
String   barTerakhir          = "";
uint16_t warnaBarTerakhir     = 0;

// Kunci menu & pesan sementara
bool          menuTerkunci = false;
unsigned long kunciSampai  = 0;
unsigned long pesanSampai  = 0;

// Debounce Push Button
const int     PIN_BTN[3]       = {BTN_UP, BTN_DOWN, BTN_SELECT};
bool          lastBtn[3]       = {HIGH, HIGH, HIGH};
unsigned long lastBtnChange[3] = {0, 0, 0};
const unsigned long DEBOUNCE   = 50;

unsigned long lastStatusBar = 0;
const unsigned long INTERVAL_STATUSBAR = 250; // hanya refresh LCD lokal, tanpa jaringan

// ==================== DEKLARASI FUNGSI ====================
bool   pastikanWiFi();
int    kirimRequest(bool post, const char* path, const String& body, String& respon);
bool   ambilState();
bool   setStatusMesin(const String& statusBaru, String& pesanGagal);
bool   parseEsp(const String& json);
String pesanError(const String& json);
bool   adaOperator();
bool   statusAktifIdx(int i);
bool   bolehPilih(int i);
int    idxSesuaiJenis();
void   bacaTombol();
void   prosesSelect();
void   gambarUlangSemua();
void   drawStaticUI();
void   updateMenuUI();
void   updateStatusBar();
void   tampilPesan(const String& teks, uint16_t warna, unsigned long durasiMs);
void   kunciMenu(unsigned long durasiMs);
void   cekKunciMenu();

// ==================== SETUP ====================
void setup() {
  Serial.begin(115200);

  for (int i = 0; i < 3; i++) pinMode(PIN_BTN[i], INPUT_PULLUP);

  tft.initR(INITR_BLACKTAB);
  tft.setRotation(1);
  tft.fillScreen(ST7735_BLACK);

  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  WiFi.setAutoReconnect(true);

  // Ambil state SEKALI saat ESP menyala
  tampilPesan("Loading Data...", ST7735_CYAN, 0);
  ambilState();
  gambarUlangSemua();
}

// ==================== LOOP ====================
void loop() {
  cekKunciMenu();
  bacaTombol();

  // Hanya update tampilan lokal (timer, WiFi), tanpa request ke server
  if (millis() - lastStatusBar >= INTERVAL_STATUSBAR) {
    lastStatusBar = millis();
    updateStatusBar();
  }
}

// ==================== TOMBOL ====================
void bacaTombol() {
  for (int i = 0; i < 3; i++) {
    bool baca = digitalRead(PIN_BTN[i]);
    if (baca == lastBtn[i] || millis() - lastBtnChange[i] <= DEBOUNCE) continue;

    lastBtnChange[i] = millis();
    lastBtn[i] = baca;
    if (baca != LOW || menuTerkunci) continue;

    if (PIN_BTN[i] == BTN_UP) {
      selectedMenu = (selectedMenu + JUMLAH_STATUS) % (JUMLAH_STATUS + 1);
      updateMenuUI();
    } else if (PIN_BTN[i] == BTN_DOWN) {
      selectedMenu = (selectedMenu + 1) % (JUMLAH_STATUS + 1);
      updateMenuUI();
    } else if (PIN_BTN[i] == BTN_SELECT) {
      prosesSelect();
    }
  }
}

void prosesSelect() {
  // ================= REFRESH DATA =================
  if (selectedMenu == IDX_REFRESH) {
    tampilPesan("Refreshing Data...", ST7735_CYAN, 0);
    bool ok = ambilState();
    gambarUlangSemua();
    tampilPesan(ok ? "Refreshed OK!" : "Refresh Gagal!", ok ? ST7735_GREEN : ST7735_RED, ok ? 1500 : 2000);
    kunciMenu(ok ? 1500 : 2000);
    return;
  }

  // ================= TOGGLE STATUS =================
  const OpsiStatus& opsi = listStatus[selectedMenu];

  // Cek hak akses dari data state terakhir (tanpa request).
  // Jika operator baru di-assign di dashboard, tekan "Refresh Data" dulu.
  if (!bolehPilih(selectedMenu)) {
    if (!adaOperator()) tampilPesan("No operator! Refresh", ST7735_RED, 2500);
    else                tampilPesan(String("Khusus ") + opsi.jenis, ST7735_RED, 2500);
    kunciMenu(2500);
    return;
  }

  String target = statusAktifIdx(selectedMenu) ? String(STATUS_DIAM) : String(opsi.status);

  tampilPesan("Updating Status...", ST7735_CYAN, 0);
  String pesanGagal;
  if (setStatusMesin(target, pesanGagal)) {
    tampilPesan("Status: " + target, ST7735_GREEN, 1500);
    kunciMenu(1500);
  } else {
    tampilPesan(pesanGagal, ST7735_RED, 2500);
    kunciMenu(2500);
  }
  gambarUlangSemua();
}

// ==================== HAK AKSES ====================
bool adaOperator() {
  return operatorAktif.length() > 0 &&
         !operatorAktif.equalsIgnoreCase("null") &&
         operatorAktif != "-";
}

bool statusAktifIdx(int i) {
  return statusAktif.equalsIgnoreCase(listStatus[i].status);
}

// Status yang sedang aktif selalu boleh dipilih (untuk kembali ke READY).
// Selain itu: harus ada operator DAN jenisnya sesuai.
bool bolehPilih(int i) {
  if (statusAktifIdx(i)) return true;
  return adaOperator() && jenisOperator.equalsIgnoreCase(listStatus[i].jenis);
}

// Index status yang boleh dipilih oleh operator saat ini, -1 jika tidak ada.
int idxSesuaiJenis() {
  if (!adaOperator()) return -1;
  for (int i = 0; i < JUMLAH_STATUS; i++) {
    if (jenisOperator.equalsIgnoreCase(listStatus[i].jenis)) return i;
  }
  return -1;
}

// ==================== KOMUNIKASI API LARAVEL ====================
// Bentuk respon backend (state / status):
// {"success":true,"esp":{"id_esp":"ESP32_01","no_mesin":"260","nama_mesin":"260 Weltech",
//   "status":"PRODUKSI","operator":"Agus","jenis_operator":"OPERATOR","timer_sec":62}}

bool pastikanWiFi() {
  if (WiFi.status() == WL_CONNECTED) return true;

  tampilPesan("Connecting WiFi...", ST7735_YELLOW, 0);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  unsigned long mulai = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - mulai < TIMEOUT_WIFI) {
    delay(250);
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("WiFi OK, IP ESP: %s\n", WiFi.localIP().toString().c_str());
    return true;
  }
  Serial.println("WiFi gagal tersambung");
  return false;
}

// Satu fungsi untuk GET & POST supaya header/timeout konsisten.
int kirimRequest(bool post, const char* path, const String& body, String& respon) {
  respon = "";
  if (!pastikanWiFi()) {
    kodeHttpTerakhir = -1;
    serverOnline = false;
    return -1;
  }

  HTTPClient http;
  http.setConnectTimeout(TIMEOUT_CONNECT);
  http.setTimeout(TIMEOUT_HTTP);
  http.begin(String(SERVER_ORIGIN) + path);
  http.addHeader("Accept", "application/json");
  http.addHeader("X-ESP-ID", ID_ESP);

  int code;
  if (post) {
    http.addHeader("Content-Type", "application/json");
    code = http.POST(body);
  } else {
    code = http.GET();
  }

  if (code > 0) {
    respon = http.getString();
  } else {
    Serial.printf("[%s %s] gagal transport: %d (%s)\n", post ? "POST" : "GET", path,
                  code, HTTPClient::errorToString(code).c_str());
  }
  http.end();

  kodeHttpTerakhir = code;
  serverOnline = (code > 0);
  return code;
}

// GET /api/esp/state — dipanggil hanya di setup() dan menu "Refresh Data"
bool ambilState() {
  String res;
  int code = kirimRequest(false, "/api/esp/state", "", res);
  Serial.printf("[GET /api/esp/state] code: %d | %s\n", code, res.c_str());

  if (code == 200 && parseEsp(res)) return true;

  if (code > 0) Serial.printf("[GET /api/esp/state] %s\n", pesanError(res).c_str());
  return false;
}

// POST /api/esp/status  body: {"status":"PRODUKSI"|"MOULD"|"SETTER"|"READY"}
bool setStatusMesin(const String& statusBaru, String& pesanGagal) {
  JsonDocument doc;
  doc["status"] = statusBaru;
  String body;
  serializeJson(doc, body);

  String res;
  int code = kirimRequest(true, "/api/esp/status", body, res);
  Serial.printf("[POST /api/esp/status] Code: %d | Input: %s | Response: %s\n",
                code, body.c_str(), res.c_str());

  if (code == 200) {
    statusAktif = statusBaru;
    parseEsp(res);
    return true;
  }

  pesanGagal = pesanError(res);
  if (pesanGagal.length() == 0) {
    pesanGagal = (code <= 0) ? "Server Offline!" : "Update Gagal (" + String(code) + ")";
  }
  return false;
}

bool parseEsp(const String& json) {
  JsonDocument doc;
  if (deserializeJson(doc, json)) return false;

  JsonObject esp = doc["esp"].as<JsonObject>();
  if (esp.isNull()) return false;

  if (!esp["nama_mesin"].isNull()) namaMesin = esp["nama_mesin"].as<String>();

  statusAktif   = esp["status"].isNull()   ? String(STATUS_DIAM) : esp["status"].as<String>();
  statusAktif.toUpperCase();

  operatorAktif = esp["operator"].isNull() ? String("") : esp["operator"].as<String>();
  operatorAktif.trim();

  // Jenis operator: OPERATOR / TEKNISI / SETUP
  jenisOperator = esp["jenis_operator"].isNull() ? String("") : esp["jenis_operator"].as<String>();
  jenisOperator.trim();
  jenisOperator.toUpperCase();

  timerBackend     = esp["timer_sec"].isNull() ? 0 : esp["timer_sec"].as<long>();
  timerDiambilPada = millis();
  return true;
}

// Ambil pesan error dari respon backend (422: {"errors":{...}}, 404/409: {"message":"..."}).
String pesanError(const String& json) {
  JsonDocument doc;
  if (deserializeJson(doc, json)) return "";

  JsonObject errors = doc["errors"].as<JsonObject>();
  if (!errors.isNull()) {
    for (JsonPair kv : errors) {
      JsonVariant v = kv.value();
      return v.is<JsonArray>() ? v[0].as<String>() : v.as<String>();
    }
  }
  if (!doc["message"].isNull()) return doc["message"].as<String>();
  return "";
}

// ==================== TAMPILAN LCD (UI) ====================
void gambarUlangSemua() {
  if (namaTerakhirDigambar != namaMesin) drawStaticUI();
  updateMenuUI();
}

void drawStaticUI() {
  tft.fillScreen(ST7735_BLACK);
  tft.setTextColor(ST7735_YELLOW);
  tft.setTextSize(2);
  tft.setCursor(5, 5);

  String title = namaMesin;
  if (title.length() > 13) title = title.substring(0, 13);
  tft.print(title);
  namaTerakhirDigambar = namaMesin;

  tft.drawFastHLine(0, 23, 160, ST7735_WHITE);
  tft.drawFastHLine(0, 112, 160, ST7735_WHITE);
  barTerakhir = ""; // paksa status bar digambar ulang
}

void updateMenuUI() {
  const int startY  = 27;
  const int spacing = 20;

  for (int i = 0; i < JUMLAH_STATUS; i++) {
    int y = startY + (i * spacing);
    tft.fillRect(0, y, 160, 18, ST7735_BLACK);
    tft.setTextSize(1);

    bool aktif    = statusAktifIdx(i);
    bool dipilih  = (i == selectedMenu);
    bool nonaktif = !bolehPilih(i);

    if (dipilih) {
      tft.setTextColor(ST7735_CYAN);
      tft.setCursor(2, y + 4);
      tft.print(">");
    }

    tft.setTextColor(nonaktif ? WARNA_ABU : ST7735_WHITE);
    tft.setCursor(12, y + 4);
    tft.print(listStatus[i].status);

    if (aktif) {
      tft.drawRect(100, y + 1, 55, 15, ST7735_GREEN);
      tft.setCursor(105, y + 4);
      tft.setTextColor(ST7735_GREEN);
      tft.print("[AKTIF]");
    } else if (nonaktif) {
      // NO OP = belum ada operator, LOCK = jenis operator tidak sesuai
      tft.drawRect(100, y + 1, 55, 15, dipilih ? ST7735_CYAN : WARNA_ABU);
      tft.setCursor(adaOperator() ? 116 : 113, y + 4);
      tft.setTextColor(WARNA_ABU);
      tft.print(adaOperator() ? "LOCK" : "NO OP");
    } else if (dipilih) {
      tft.drawRect(100, y + 1, 55, 15, ST7735_CYAN);
    }
  }

  // Menu Refresh Data (index terakhir)
  int yRefresh = startY + (JUMLAH_STATUS * spacing);
  tft.fillRect(0, yRefresh, 160, 18, ST7735_BLACK);
  tft.setTextSize(1);

  if (selectedMenu == IDX_REFRESH) {
    tft.setTextColor(ST7735_CYAN);
    tft.setCursor(2, yRefresh + 2);
    tft.print(">");
    tft.setTextColor(ST7735_YELLOW);
  } else {
    tft.setTextColor(ST7735_MAGENTA);
  }
  tft.setCursor(12, yRefresh + 2);
  tft.print("[ Refresh Data ]");

  barTerakhir = "";
  updateStatusBar();
}

void tampilPesan(const String& teks, uint16_t warna, unsigned long durasiMs) {
  String t = teks.length() > 25 ? teks.substring(0, 25) : teks;
  tft.fillRect(0, 113, 160, 15, ST7735_BLACK);
  tft.setTextSize(1);
  tft.setTextColor(warna);
  tft.setCursor(5, 117);
  tft.print(t);
  pesanSampai = millis() + durasiMs;
  barTerakhir = ""; // setelah pesan habis, status bar digambar ulang
}

void kunciMenu(unsigned long durasiMs) {
  menuTerkunci = true;
  kunciSampai = millis() + durasiMs;
}

void cekKunciMenu() {
  if (menuTerkunci && (long)(millis() - kunciSampai) >= 0) menuTerkunci = false;
}

void updateStatusBar() {
  if ((long)(millis() - pesanSampai) < 0) return; // pesan sementara masih tampil

  String   teks;
  uint16_t warna = ST7735_WHITE;

  if (WiFi.status() != WL_CONNECTED) {
    teks = "WiFi Offline"; warna = ST7735_RED;
  }
  else if (!serverOnline) {
    teks = "Server Offline"; warna = ST7735_RED;
  }
  else if (kodeHttpTerakhir == 404) {
    teks = "ESP Blm Terdaftar"; warna = ST7735_ORANGE;
  }
  else if (statusAktif == "OFF") {
    teks = "Off"; warna = ST7735_CYAN;
  }
  else if (statusAktif == STATUS_DIAM) {
    // READY: tampilkan status apa yang siap dijalankan sesuai jenis operator
    int idx = idxSesuaiJenis();
    if (!adaOperator())  { teks = "Ready belum running"; warna = ST7735_ORANGE; }
    else if (idx < 0)    { teks = "Jenis op tdk dikenal"; warna = ST7735_ORANGE; }
    else                 { teks = String("Ready siap ") + listStatus[idx].status; warna = ST7735_GREEN; }
  }
  else {
    // PRODUKSI / MOULD / SETTER: timer lanjut dihitung lokal dari nilai refresh terakhir
    long detikTotal = timerBackend + (long)((millis() - timerDiambilPada) / 1000);
    if (detikTotal < 0) detikTotal = 0;

    char timerBuf[12];
    snprintf(timerBuf, sizeof(timerBuf), "%02ld:%02ld:%02ld",
             detikTotal / 3600, (detikTotal % 3600) / 60, detikTotal % 60);

    String singkat = statusAktif.substring(0, 3);
    warna = ST7735_WHITE;
    for (int i = 0; i < JUMLAH_STATUS; i++) {
      if (statusAktifIdx(i)) { singkat = listStatus[i].singkat; warna = listStatus[i].warna; }
    }

    teks = singkat + " " + timerBuf;
    if (adaOperator()) teks += " " + operatorAktif.substring(0, 6);
  }

  // Gambar ulang hanya jika ada perubahan (anti flicker)
  if (teks == barTerakhir && warna == warnaBarTerakhir) return;

  tft.fillRect(0, 113, 160, 15, ST7735_BLACK);
  tft.setTextSize(1);
  tft.setTextColor(warna);
  tft.setCursor(5, 117);
  tft.print(teks);

  barTerakhir      = teks;
  warnaBarTerakhir = warna;
}