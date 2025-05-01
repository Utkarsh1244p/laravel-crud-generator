# Laravel CRUD Generator

<!--
[![Latest Version](https://img.shields.io/github/v/release/Utkarsh1244p/laravel-crud-generator?style=flat-square)](https://packagist.org/packages/utkarsh1244p/laravel-crud-generator)
[![License](https://img.shields.io/github/license/Utkarsh1244p/laravel-crud-generator?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/utkarsh1244p/laravel-crud-generator?style=flat-square)](https://packagist.org/packages/utkarsh1244p/laravel-crud-generator)
-->

Generate Laravel CRUD models, controllers, API routes, migrations, factories, and request files with a single Artisan command.

---

## ✨ Features

- 🚀 **Single-command generation** - Create all CRUD components with one command
- 📦 **Complete CRUD Stack** generates:
  - **Model** (with `HasFactory`, `SoftDeletes`, guarded `id`)
  - **Controller** (with production-ready CRUD methods)
  - **Migration** (with field type detection)
  - **Factory** (with smart Faker data generation)
  - **Request** (separate Store/Update with validation)
  - **API Routes** (auto-added to routes/api.php)
  - **Response Trait** (standardized JSON responses)
- 🔥 **Advanced Features**:
  - Dual-mode field handling (explicit or validated data)
  - Automatic foreign key relationships
  - Consistent error handling with try-catch
  - Configurable field types and modifiers

---

## 🏭 Factory Generation

### Key Features:

- **Automatic Type Detection** – Maps field types to appropriate Faker methods
- **Foreign Key Handling** – Skips `*_id` fields to avoid relationship issues
- **Default Timestamps** – Adds `created_at` and `updated_at` automatically
- **Customizable** – Easily extend support for more data types if needed

### Supported Field Types

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

## 🧾 Request File Generation

### Key Features:

- **Smart Rule Generation**
  - `required` rules for Store requests
  - `sometimes` rules for Update requests
  - Auto-detects rules based on field type
- **Foreign Key Handling**
  - Adds `exists:table,column` rule for foreign keys
- **Customizable Messages**
  - Basic default error messages generated
  - Easy to extend for localization or overrides

### Field Type Mapping

| Field Type     | Validation Rules             |
|----------------|------------------------------|
| string         | string, max:255              |
| text           | string                       |
| integer        | integer                      |
| decimal/float  | numeric                      |
| boolean        | boolean                      |
| date           | date                         |
| foreign        | exists:related_table,id      |

---

## 📦 Installation (Public Repository)

Since the package is not yet published to Packagist, add the GitHub repo manually:

```bash
composer config repositories.crud-generator vcs https://github.com/Utkarsh1244p/laravel-crud-generator
composer require utkarsh1244p/laravel-crud-generator:dev-main --prefer-source
