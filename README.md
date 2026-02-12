# 🌍 Silkroad Tour - Website

Professionelle Website für geführte Usbekistan-Touren entlang der historischen Seidenstraße.

![Status](https://img.shields.io/badge/Status-Production-green)
![PHP](https://img.shields.io/badge/PHP-7.2+-blue)
![License](https://img.shields.io/badge/License-Private-red)

---

## 📋 Projekt-Übersicht

- **Projektname:** Silkroad Tour
- **Beschreibung:** Marketing-Website mit Buchungsformular für 12-tägige Usbekistan-Rundreisen
- **Tech-Stack:** HTML5, CSS3, JavaScript (Vanilla), PHP, MySQL
- **Hosting:** Strato Shared Hosting
- **Features:** 
  - Responsive Design
  - DSGVO-konform (Cookie-Banner)
  - Datenbankintegration
  - Formular mit Spam-Schutz
  - Interaktive Karte

---

## 🗂️ Ordnerstruktur

```
Silkroad/
├── index.html                  # Hauptseite mit Formular
├── tour.html                   # Tourübersicht
├── kontakt.html               # Kontaktseite
├── accommodation.html         # Unterkünfte
├── buses-flights.html         # Transport-Info
├── preparation.html           # Reisevorbereitung
├── terms.html                 # AGB
├── impressum.html             # Impressum
├── datenschutz.html           # Datenschutz (DSGVO)
│
├── assets/
│   ├── css/
│   │   └── styles.css         # Haupt-Stylesheet
│   ├── js/
│   │   └── main.js            # JavaScript (Cookie, Formular, etc.)
│   └── images/                # Bilder & Icons
│
├── silkroad_db/               # 🔒 Datenbank-Integration
│   ├── index.php              # Standalone Formular
│   ├── submit.php             # API Endpoint (speichert in MySQL)
│   ├── db.php                 # 🚨 NICHT IN GIT! (Passwort)
│   ├── db.php.example         # Template (ohne Passwort)
│   ├── thanks.html            # Danke-Seite
│   └── .htaccess              # Sicherheitsheader
│
├── .gitignore                 # Git Ignore-Liste
└── README.md                  # Diese Datei
```

---

## 🚀 Installation & Setup

### 1️⃣ Repository klonen

```bash
git clone https://github.com/DEIN-USERNAME/silkroad-tour.git
cd silkroad-tour
```

### 2️⃣ Datenbank konfigurieren

**Wichtig:** Vor dem ersten Upload!

```bash
# Template kopieren
cp silkroad_db/db.php.example silkroad_db/db.php

# Daten eintragen (in db.php)
define('DB_HOST', 'localhost');
define('DB_NAME', 'DEIN_DB_NAME');
define('DB_USER', 'DEIN_DB_USER');
define('DB_PASS', 'DEIN_PASSWORT');
```

### 3️⃣ MySQL-Tabelle erstellen

In phpMyAdmin ausführen:

```sql
CREATE TABLE tour_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  tour VARCHAR(255) NOT NULL,
  travel_date DATE NOT NULL,
  adults INT NOT NULL DEFAULT 1,
  children INT NOT NULL DEFAULT 0,
  toddlers INT NOT NULL DEFAULT 0,
  message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 4️⃣ Upload via FTP

**FileZilla:**
- Host: `511702953.ssh.w1.strato.hosting`
- Port: `22`
- Protokoll: `SFTP`
- User: `stu21809377`

Hochladen:
- Alle Dateien außer `.git/`
- ⚠️ **`db.php` mit echten Daten!**

---

## 🔒 Sicherheit

### Was ist geschützt:

✅ **Prepared Statements** - SQL-Injection Schutz  
✅ **Input-Validierung** - Email, Datum, Telefon geprüft  
✅ **Honeypot** - Spam-Bot Schutz  
✅ **CSRF-Token** - Cross-Site Request Forgery Schutz  
✅ **Rate-Limiting** - Max 3 Anfragen/Std pro IP  
✅ **.htaccess** - db.php blockiert  
✅ **.gitignore** - Passwörter nicht in Git  

### Sensible Dateien (NICHT in Git):

```
silkroad_db/db.php          # 🚨 Enthält DB-Passwort
api/config.php              # 🚨 Falls vorhanden
*.log                       # Logs
```

Diese werden durch `.gitignore` automatisch ausgeschlossen!

---

## 🧪 Testen

### Lokal (ohne Server):
```bash
# Python Server starten
python -m http.server 8000

# Browser öffnen
open http://localhost:8000
```

⚠️ **Hinweis:** Formular funktioniert nur auf dem Server (benötigt PHP + MySQL)!

### Live (nach Upload):
1. Öffne: `https://silkroadtour.de`
2. Scrolle zum Formular
3. Fülle aus & sende ab
4. Prüfe in phpMyAdmin: `tour_requests` Tabelle

---

## 📊 Datenbank-Schema

**Tabelle:** `tour_requests`

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT | Eindeutige ID (Auto-Increment) |
| `name` | VARCHAR(255) | Kundenname |
| `email` | VARCHAR(255) | E-Mail-Adresse |
| `phone` | VARCHAR(50) | Telefonnummer |
| `tour` | VARCHAR(255) | Gewählter Reisezeitraum |
| `travel_date` | DATE | Start-Datum |
| `adults` | INT | Anzahl Erwachsene |
| `children` | INT | Anzahl Kinder (2-11 J.) |
| `toddlers` | INT | Anzahl Kleinkinder (0-2 J.) |
| `message` | TEXT | Optionale Nachricht |
| `created_at` | TIMESTAMP | Anmeldezeitpunkt |

---

## 📝 Features

### Frontend:
- ✅ Responsive Design (Mobile-First)
- ✅ Interaktive Karte mit Pins
- ✅ Bildergalerien (Slideshow)
- ✅ Cookie-Banner (DSGVO)
- ✅ Client-Side Validierung
- ✅ Smooth Scrolling

### Backend:
- ✅ MySQL Datenbank-Integration
- ✅ PHP POST-Endpoint
- ✅ Email-Validierung
- ✅ Spam-Schutz (Honeypot)
- ✅ Rate-Limiting
- ✅ Error-Logging

---

## 🛠️ Entwicklung

### Vor jedem Commit:

```bash
# Status prüfen
git status

# Sicherstellen dass db.php NICHT dabei ist!
git add .
git commit -m "Deine Nachricht"
git push
```

### Neue Features hinzufügen:

1. Branch erstellen: `git checkout -b feature/neue-funktion`
2. Änderungen machen
3. Testen!
4. Commit & Push
5. Pull Request erstellen

---

## 📞 Support & Kontakt

**Website:** https://silkroadtour.de  
**Email:** info@silkroadtour.de  
**WhatsApp:** 0170 - 7222 110

---

## ⚖️ Rechtliches

### DSGVO-Compliance:
- ✅ Datenschutzerklärung vorhanden
- ✅ Impressum vorhanden
- ✅ Cookie-Banner implementiert
- ✅ Keine Tracking ohne Einwilligung

### To-Do für Go-Live:
- [ ] Impressum mit echten Daten füllen
- [ ] Datenschutzerklärung rechtlich prüfen lassen
- [ ] SSL-Zertifikat aktivieren (HTTPS)
- [ ] Backup-Strategie festlegen

---

## 📚 Dokumentation

Weitere Infos:
- `silkroad_db/SETUP-ANLEITUNG.md` - Detaillierte DB-Setup Anleitung
- `ANMELDEFORMULAR-DOKUMENTATION.md` - Formular-Dokumentation (falls vorhanden)

---

## 🎯 Roadmap

### Phase 1 (✅ Fertig):
- [x] Website-Design
- [x] Formular mit DB-Integration
- [x] DSGVO-Compliance
- [x] Hosting Setup

### Phase 2 (🔄 Optional):
- [ ] Admin-Dashboard für Anfragen
- [ ] Email-Benachrichtigungen
- [ ] Zahlungsintegration
- [ ] Mehrsprachigkeit (EN, RU)

---

## 🐛 Known Issues

Keine bekannten Probleme! 🎉

Bei Problemen:
1. Browser-Konsole (F12) prüfen
2. Strato Error-Log checken
3. phpMyAdmin: Daten vorhanden?

---

## 📜 Lizenz

**Private / Proprietary**

Dieses Projekt ist privat. Keine Verbreitung ohne Genehmigung.

---

**Version:** 1.0  
**Letztes Update:** Januar 2026  
**Status:** ✅ Production Ready
