# LLM 기반 Ritase Parser

Mit diesem Feature kann der Fahrer-Dienstplan automatisch aus Gruppentextnachrichten (z. B. WhatsApp, Telegram) geparst, validiert und in das System importiert werden. Es verwendet einen rule-basierten Parser (ohne externe LLM-API) mit fuzzy matching, um Namen und Routen den Daten im System zuzuordnen.

## Verwendung

### 1. Öffnen des Parsers
- Navigieren Sie zu: `Dashboard → Kelola Ritase → Parser Ritase`
- Oder klicken Sie direkt auf den Link: `/ritase/parser`

### 2. Paste das Gruppentextformat

Der Parser erwartet das folgende Format:

```
22 07 26 rabu                    # === LINI PERTAMA ===
Bondan patching pare kota          # === RUTENNAME ===
Paket cmm blitar kota            # === RUTENNAME ===
1. Riki                          # === NAMENSLISTE ===
2. Kola
3. Firsa
...
Paket watualang ngawi           # === RUTENNAME ===
1. Gun
2. Anjar
...
```

**Formatregeln**

| Element | Beschreibung | Beispiel |
|---------|-------------|----------|
| **Lini Datum** | `DD MM YY hari` (TJ/TMittwoch) | `22 07 26 rabu` → 26 Juli 2022 |
| **Rutenname** | Muss eines der Keywords enthalten: `paket`, `bondan`, `patching`, `kota`, `kabupaten`, `rute`, `route` | `Paket cmm blitar kota` |
| **Linie der Fahrerliste** | Nummeriert: `1. Vollständiger Name`, `2. Name`, ... | `1. Riki`, `2. Kola` |
| **Namensvariationen** | Nicknames: `Mbah POR`, `Eka bence`, `Adib`, `Torik`, usw. |
| **Leerzeilen** | Wird ignoriert | - |

### 3. Wählen Sie die Periode
- Wählen Sie die entsprechende Abrechnungsperiode aus der Dropdown-Liste aus.

### 4. Preview-Wiedergabe
- Klicken Sie auf **Parse & Preview** (oder **Parse & Simpan** zum sofortigen Import)

### 5. Ergebnis-Oberfläche

#### 📊 Zusammenfassungs-Karten
- **Datum**: Parsiertes Datum aus dem Text
- **Anzahl der Routen**: Extraktierte Paketnamen
- **Anzahl der Fahrer**: Gesamtanzahl der Fahrer im Text
- **Fahrer-Übereinstimmungen**: Fahrer, die erfolgreich mit dem System abgeglichen wurden

#### 👥 Fahrer-Abgleichstabelle
| Eingabename | Status | System-Fahrer | Code | Confidence |
|------------|--------|--------------|------|------------|
| Riki | ✅ Gefunden | Riki | SPR-001 | 100% |
| Kola | ✅ Gefunden | Kola | SPR-002 | 100% |
| Mbah POR | ✅ Gefunden | POR | SPR-006 | 95% |

#### 🗺️ Routen-Abgleichstabelle
| Eingabe-Route | Status | System-Ziel | Code | Confidence |
|-------------|--------|---------------|------|------------|
| Bondan patching pare kota | ✅ Gefunden | Pare Kediri | TUJ-001 | 85% |
| Paket watualang ngawi | ✅ Gefunden | Watualang Ngawi | TUJ-002 | 80% |

#### 🧾 Paketdetails
Zeigt pro Route:
- **Route-Name**: Genau so wie im Text geparst
- **Fahrerliste**: Alle Fahrer, die dieser Route zugewiesen sind
- **Farb-Badges**: ✅ Grün → erfolgreich zugewiesen, 🔴 Rot → nicht gefunden

### 6. Speichern in Datenbank

**Option A: Preview zuerst**
- Schalten Sie **Speichern automatisch** aus, um die Ergebnis-Oberfläche anzuzeigen.
- Überprüfen Sie den Abgleich.
- Klicken Sie auf **Speichern Alle zu Datenbank** (im unteren Bildschirmrand verfügbar) → bestätigt den Import.

**Option B: Direkt importieren**
- Aktivieren Sie **Speichern automatisch** (Checkbox) im Hauptformular.
- Klicken Sie auf **Parse & Simpan** → importiert und speichert direkt in einer Transaktion.

**Sicherheits-Check** - Vor jeder Create-Operation:
- Duplikatsprüfung: Dieselbe Kombination aus Fahrer+Routen+Datum verhindert doppelte Datensätze.
- Accuracy: Fuzzy-matching und Confidence-Schwellenwerte (≥ 70% Fahrer, ≥ 60% Routen).

### 7. Protokoll und Fehlerbehandlung

Der Parser generiert ein detailliertes Protokoll, das folgt:
- ✅ Erfolgreich erstellt: Anzahl und Details
- ⏭️ Übersprungen: Duplikate oder nicht übereinstimmende Fahrer/Routen
- ❌ Fehler: Unerwartete Data-Formatierung, fehlende Datumszeile, usw.

#### Unterstützte Daten-Formatierung
- **Datums-Format:** `DD MM YY hari` (Woche abgekürzt) → ISO `YYYY-MM-DD`
- **Routen-Keyword-Extraktion:** `paket`, `bondan`, `patching`, `kota`, `kabupaten`, `rute`
- **Fahrer-Namen-Cleanup:** Entfernt Titel (`mbah`, `pak`), Satzzeichen, doppelte Leerzeichen.

#### Fuzzy-Matching-Algorithmus
- **Jaro-Winkler-Ähnlichkeit** (für Namen, bis zu 4 Zeichenpräfix)
- **Thresholds:** Fahrer ≥ 70% (unterstützt kleine Tippfehler), Routen ≥ 60%
- **Fallback:** Erstklassige exakte Übereinstimmung, Second-Class fuzzy-match für den Rest.

### 8. Route-zu-Kabupaten-Felder

Der System-Routenname wird von den Support-Kabupaten in Front des Routenextraktors gemappt.
Beispiel: `Bondan` → `Kediri` (Fahrer bekommen DT basierend auf Kabupatumsverteilung)

### 9. Werte von DT und Jumlah Sopir (Backend)

**Eingabe** → **Berechnung** (Laravel Rules & Validierung)
- **DT-Grenzen** → `Jurisdiction Hijau Kahai plus Muka Golong` (minimal Rp 0)
- **Rit-Rekening** → Zählt die Anzahl der Fahrten pro Fahrer pro Tag + Zustand
- **Akumulierte Anzahl von Rit** → Basis für Fahrtenanzahl-Limits

### 10. API-Endpunkte

| Methode | URL | Beschreibung |
|--------|-----|-------------| |
| GET | `/ritase/parser` | Formular für den Rutentextparser |
| POST | `/ritase/parser` | Verarbeiten Sie den Text und geben Sie die Antwort zurück |
| GET | `/ritase/parser/test` | Testschleife mit einem bekannten Beispieltext |

### 11. Schlüsselfunktionen im Code

| Datei | Beschreibung |
|------|-------------| |
| `app/Services/RitaseParserService.php` | Kernservice: Parser, Matchmaker, DB-Creator |
| `app/Http/Controllers/RitaseController.php` | UI-Funktionen: Parser-Formular, Prozess, Test |
| `routes/web.php` | Registriert den UI-Router für die Parser-Routen |
| `resources/views/ritase/parser.blade.php` | Frontend: Formulare, Beispiel, Javascript |
| `resources/views/ritase/parser-result.blade.php` | Ergebnis-Oberfläche: Tabellen, Karten |

### 12. Beispiele

#### Beispiel 1: Normales Format
```
25 12 29 selasa
Paket cmm blitar kota
1. Kola
2. Riki
3. Firsa
```

#### Beispiel 2: Großes Paket
```
22 07 26 rabu
Bondan patching pare kota
Paket cmm blitar kota
1. Riki
2. Kola
3. Firsa
4. Wahyu
5. Ginem
6. Mbah POR
7. Didik
8. Yuri
9. Agung
Paket watualang ngawi
1. Gun
2. Anjar
...
```

**Tipp:** Verwenden Sie den **Beispiel**-Button auf der Parser-Oberfläche → Füllt den Text für Sie automatisch aus.

### 13. Fehlerbehebung

#### Typische Anforderung
- Das Fehlen der Datumszeile → Fehler: "Das Datum wurde nicht erkannt. Format: DD MM YY hari"
- Driver/Routen nicht übereinstimmend → Zeigen Sie **Rote Zeilen** in den Tabellen an
- Duplikatainträge → überspannt und wird automatisch ignoriert
- Falsche Datumsformatierung → Großregister vs. Kleinregister (keine Rolle, wird erweitert)

#### Wie man die Verarbeitungsdetails überprüft
- Nach erfolgreichem Import → der Benutzer wird zu `Kelola Ritase → Datenritase` weitergeleitet
- Überprüfen Sie `ritases`-Einträge im Datenbankschema

### 14. Entwicklungskonfiguration

- **Keine Abhängigkeit von externen LLM-APIs** – rule-based und fuzzy matching zentral im System.
- **Altes Dataset kompatibel** – passt digitale Daten in digitale Einträge ein.
- **Optimiert für Gruppen-Textformat** – extrahiert fehler-tolerant Namen/Routen.
- **Transaktionsbasiert** – Atomicer „Einmal importiert, immer gespeichert“.

### 15. Erweiterung (zukünftige Ideen)

- API für LLMs (OpenAI, Gemini) → konfigurierbar anstelle von rule-based.
- Wrapper für WhatsApp/Telegram → direkte Integration als Webhook.
- Batch-Upload-Panel → JSON-upload für viele Rohdaten.
- Export-Funktion: Exportieren der Rohdaten-Spalten im CSV/TSV-Format.

### 16. Lizenz

MIT

---

**Features:**
- 🌐 Frontend: Livewire-free, Tailwind + Alpine.js?
- 🔧 Backend: Habit der Laravel-Framework
- 🧠 Matching-Engine: Fuzzy-string-ähnlichkeit über Laravel-Query
- 📜 Validierung: Laravel-Einstellung + Modell-zu-Kabupaten-Mapping
- 👥 Abteilungsübergreifend (Ritase + Sopir + Tujuan)
- 📈 Audit-Trail: Datenbank `ritases.created_at/at` für jede Importaktion

Nutzen Sie dieses Feature, um die manuelle Dateneingabe aufzuräumen, Fehler zu reduzieren und Echtzeit-Basierte-Daten für die Berechnung des Fahrtenverdienstes zu erhalten.
