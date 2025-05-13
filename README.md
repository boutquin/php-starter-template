
# PHP 8.3 PhpStorm Starter Project 🚀

An **industrial‑strength, professional‑grade** PHP 8.3+ starter template built for modern backend development.  
Highlights:

* **Strict PSR‑12** coding style (enforced by PHP‑CS‑Fixer + PhpStorm settings)
* **Static analysis at level 8 + strict‑rules** (PHPStan)
* **Fast unit tests** on SQLite (in‑memory) & **integration tests** on MySQL
* **Composer script aliases** for a one‑command DX (`composer check`)
* **Future‑proof CI** (GitHub Actions ready 🔧 — see `.github/workflows/ci.yml`)
* Cross‑platform consistency via **.editorconfig** & **.gitattributes**

---

## 📋 Table of Contents
1. [Features](#-features)
2. [Requirements](#-minimum-requirements)
3. [Project Structure](#-project-structure)
4. [Quick Start](#-quick-start)
5. [Composer Scripts](#-composer-scripts)
6. [Environments](#-environments)
7. [Running Tests](#-running-tests)
8. [Code Quality Tools](#-code-quality-tools)
9. [PhpStorm Setup](#-phpstorm-setup)
10. [Suggested Enhancements](#-suggested-enhancements)
11. [License & Author](#-license--author)

---

## ✨ Features
|  Feature | Details |
|---------|---------|
| **PHP 8.3 Ready** | Strict types, promoted properties, readonly classes |
| **PSR Compliance** | PSR‑1, 4, 12 (coding); PSR‑3 (logging); PSR‑20 (clock) |
| **Static Analysis** | PHPStan level 8 + [`phpstan-strict-rules`](https://github.com/phpstan/phpstan-strict-rules) |
| **Testing** | PHPUnit 10+, TestDox, coverage HTML, SQLite/​MySQL abstraction |
| **CI‑ready** | Sample GitHub Actions workflow with caching & matrix DB builds |
| **DX niceties** | `.editorconfig`, `.gitattributes`, Makefile stub, Docker hint |

---

## 📦 Minimum Requirements
| Tool | Version |
|------|---------|
| **PHP** | 8.3+ (with Xdebug for coverage) |
| **Composer** | 2.x |
| **PhpStorm** | 2025.1 or newer |
| **SQLite** | 3.x (in‑memory unit tests) |
| **MySQL** | 8.x (integration & production) |

---

## 📁 Project Structure
```
php-template/
├── src/                                 # Application source code
│   └── Environment/
│       └── SimpleDotEnvLoader.php       # Framework-agnostic .env loader with logger support
│
├── tests/                               # Unit and integration tests
│   └── Environment/
│       └── SimpleDotEnvLoaderTest.php   # Full PHPUnit test suite for dotenv loader
│
├── .github/
│   └── workflows/ci.yml                 # GitHub Actions (PHP matrix + MySQL service)
├── composer.json                        # Project dependencies, autoload rules, script aliases
├── phpunit.xml                          # PHPUnit 12 config: bootstrap, testdox, strict flags
├── phpstan.neon                         # PHPStan strict rules (level 8 + strict-rules include)
├── .php-cs-fixer.dist.php               # PSR-12 + custom rules for automated code style
│
├── .editorconfig                        # Shared formatting rules across editors/IDEs
├── .env.example                         # Sample .env file (safe to commit)
├── .gitattributes                       # Git line endings, linguist hints, file normalization
├── .gitignore                           # Ignores caches, logs, IDE files, etc.
└── README.md   ← you are here
```

---

## ⚡ Quick Start
```bash
# Clone & install deps
git clone https://github.com/boutquin/php-template.git
cd php-template
composer install

# Run the full check (lint + tests)
composer check
```

---

## 🛠️ Composer Scripts
Extracted verbatim from **composer.json**:

```json
"scripts": {
  "test": "phpunit",
  "lint": "phpstan analyse",
  "fix": "php-cs-fixer fix",
  "fix:dry": "php-cs-fixer fix --diff --dry-run",
  "fix-parallel": "php-cs-fixer fix --parallel",
  "analyse": "@lint",
  "check": [
    "@lint",
    "@test"
  ]
}
```

> **Tip 📌** Use `composer check` before every commit — it runs both static analysis and all tests in one go.

---

## 🌐 Environments
| Name | Purpose | DB Driver | Notes |
|------|---------|----------|-------|
| **development** | Local dev & lightning‑fast unit tests | SQLite (in‑memory) | No MySQL needed; edit `.env` |
| **test** | Integration tests (GitHub Actions, CI) | MySQL 8 | Creates/tears down schema each run |
| **production** | Live environment | Managed MySQL / RDS | Hardened `.env.production`; no dev files |

Switch with either:
* Dedicated environment files (`.env`, `.env.test`, `.env.production`) **or**
* `APP_ENV=development|test|production` in your shell/CI vars.

---

## 🧪 Running Tests
```bash
# Unit tests (SQLite, default)
composer test

# Integration tests (MySQL)
APP_ENV=test \
TEST_DB_DSN="mysql:host=127.0.0.1;port=3306;dbname=php_template_test;charset=utf8mb4" \
TEST_DB_USERNAME="root" \
TEST_DB_PASSWORD="secret" \
composer test
```
Full coverage HTML will be generated in `build/coverage` if Xdebug is enabled.

---

## 🧹 Code Quality Tools
| Action | Command |
|--------|---------|
| **Static analysis** | `composer lint` |
| **Fix code style** | `composer fix` |
| **Dry‑run style check** | `composer fix:dry` |

---

## 🧠 PhpStorm Setup
1. **Languages & Frameworks → PHP**
    * Interpreter → PHP 8.3 local
    * CLI‑Interpreter path linked to your global `php`
2. **PHP → Quality Tools**
    * Add PHPStan & PHP‑CS‑Fixer (point to `vendor/bin`)
3. **Editor → Code Style**
    * Honour `.editorconfig`; set 4‑space indents
4. **Keymaps (optional)**
    * Bind _Run Composer Script…_ to a shortcut for speed

---

## 💡 Suggested Enhancements
* **Docker Compose**: add `docker-compose.yml` with PHP‑FPM 8.3, MySQL, and MailHog services for a truly reproducible setup.
* **pre‑commit hooks**: wire up `lint-staged` + Husky or `grumphp` to auto‑run `composer check`.
* **Makefile**: expose `make up`, `make test`, `make lint` wrappers for non‑PHP devs.
* **EditorConfig extras**: include `[*.md] trim_trailing_whitespace = false` so code blocks keep spacing.

---

## 📜 License & Author
* **License**: [Apache 2.0](LICENSE)
* **Author**: Pierre G. Boutquin — [@boutquin](https://github.com/boutquin)

---

> _Built with care, strict types, and just enough caffeine ☕️._
