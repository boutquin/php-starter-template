# PHP 8.3 PhpStorm Starter Project

An industrial-strength, professional-grade PHP 8.3+ starter template built for modern backend development with full PSR-12 support, PhpStorm integration, and ready-to-use configuration for local development, unit testing, and future CI.

---

## 🚀 Features

- **PHP 8.3 Ready** with strict typing and modern syntax
- **PSR-12 + PSR-5 Style** enforced via PHP-CS-Fixer and PhpStorm
- **Strict static analysis** via PHPStan (level 8 + strict rules)
- **Full PHPUnit 10+ support** with SQLite (in-memory) and MySQL readiness
- **Cross-platform consistency** via `.editorconfig` and `.gitattributes`
- **Test lifecycle** aligned with Rails: `development`, `test`, `production`
- Future-ready for **GitHub Actions** and CI integration

---

## 📦 Minimum Requirements

| Tool         | Version         |
|--------------|------------------|
| PHP          | 8.3+             |
| Composer     | 2.x              |
| PhpStorm     | Latest (2023.3+) |
| SQLite       | Optional (for unit tests) |
| MySQL        | Optional (for integration/prod) |

---

## 📁 Project Structure

```
.
php-template/
├── src/                                 # Application source code
│   └── Environment/
│       └── SimpleDotEnvLoader.php       # Framework-agnostic .env loader with logger support
│
├── tests/                               # Unit and integration tests
│   └── Environment/
│       └── SimpleDotEnvLoaderTest.php   # Full PHPUnit test suite for dotenv loader
│
├── composer.json                        # Project dependencies, autoload rules, script aliases
├── phpunit.xml                          # PHPUnit 12 config: bootstrap, testdox, strict flags
├── phpstan.neon                         # PHPStan strict rules (level 8 + strict-rules include)
├── .php-cs-fixer.dist.php              # PSR-12 + custom rules for automated code style
│
├── .editorconfig                        # Shared formatting rules across editors/IDEs
├── .env.example                         # Sample .env file (safe to commit)
├── .gitattributes                       # Git line endings, linguist hints, file normalization
├── .gitignore                           # Ignores caches, logs, IDE files, etc.
└── README.md                            # Project documentation and setup instructions
```

---

## 🧪 Running Tests

```bash
vendor/bin/phpunit
```

SQLite in-memory is used for fast unit testing by default. You can override with a test-specific DSN:

```bash
TEST_DB_DSN="mysql:host=127.0.0.1;dbname=mytest" \
TEST_DB_USER=root \
TEST_DB_PASSWORD=secret \
vendor/bin/phpunit
```

---

## 🧹 Code Quality

### Run PHPStan (level 8 + strict):

```bash
vendor/bin/phpstan analyse
```

### Run PHP-CS-Fixer (dry-run first):

```bash
vendor/bin/php-cs-fixer fix --diff --dry-run
```

To auto-fix:

```bash
vendor/bin/php-cs-fixer fix
```

---

## 🧪 Environments

Like Rails, this project supports:

- `development`: your local dev environment (e.g., MySQL, file-based .env)
- `test`: PHPUnit + SQLite in-memory for speed
- `production`: secure MySQL-based or managed DB config, no dev files

Use `.env`, `.env.test`, or `.env.production` to switch context.

---

## 🧠 PhpStorm Setup

1. **Settings → PHP**
    - Interpreter: PHP 8.3 (local)
    - Composer: Select `composer.json` and set executable to **"executable"**
        - ✅ Choose: `composer` from your local PHP install (not remote or PHAR)
2. **Settings → Editor → Code Style**
    - Import `.editorconfig`
    - Set hard wrap at 120, tab width = 4
3. **Settings → Tools → File Watchers (Optional)**
    - Add PHP-CS-Fixer to run on save

---

## 📦 Composer Scripts (optional)

You can add these to `composer.json`:

```json
"scripts": {
  "lint": "phpstan analyse",
  "fix": "php-cs-fixer fix",
  "test": "phpunit"
}
```

Then run with:

```bash
composer test
composer lint
composer fix
```

---

## 📝 License

Licensed under the [Apache 2.0 License](LICENSE).

---

## 📌 Author

Created by Pierre G. Boutquin  
GitHub: [@boutquin](https://github.com/boutquin)

---
