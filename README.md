# Laravel CRUD Generator
<!--
[![Latest Version](https://img.shields.io/github/v/release/Utkarsh1244p/laravel-crud-generator?style=flat-square)](https://packagist.org/packages/utkarsh1244p/laravel-crud-generator)
[![License](https://img.shields.io/github/license/Utkarsh1244p/laravel-crud-generator?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/utkarsh1244p/laravel-crud-generator?style=flat-square)](https://packagist.org/packages/utkarsh1244p/laravel-crud-generator)
-->
Generate Laravel CRUD models, controllers, API routes, and migration files with a single Artisan command.

---

## ✨ Features

- 🚀 Single-command CRUD generation
- 📦 Automatically creates:
  - Model (with `HasFactory`, `SoftDeletes`, guarded `id`)
  - Controller (with ready-to-use CRUD methods)
  - API Route (added to `routes/api.php`)
  - Migration file with optional custom fields
- ✅ Controller methods include:
  - JSON API responses with status codes
  - `try-catch` error handling
  - Pagination support
- 🔧 Configurable field types via command-line

---

## Supported Field Types for Factory Generation

When generating factories using the `--fields` option, the following field types are automatically mapped to Faker methods:

| Field Type     | Faker Method Used           |
|----------------|-----------------------------|
| string         | $this->faker->word          |
| text/longText  | $this->faker->text          |
| integer/bigInt | $this->faker->randomNumber  |
| float/decimal  | $this->faker->randomFloat(2)|
| boolean        | $this->faker->boolean       |
| date/dateTime  | $this->faker->dateTime      |
| json           | json_encode([...])          |

> 💡 For `json`, a sample JSON-encoded array is returned, e.g., `json_encode(['key' => 'value'])`.

---

## 📦 Installation (Public Repository)

Since the package is not yet published to Packagist, add the GitHub repo manually:

```bash
composer config repositories.crud-generator vcs https://github.com/Utkarsh1244p/laravel-crud-generator
composer require utkarsh1244p/laravel-crud-generator:dev-main --prefer-source
