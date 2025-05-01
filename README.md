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

## 📦 Installation

### 1. Add the repository manually

#### 🔓 For public GitHub repository:

```bash
composer config repositories.crud-generator vcs https://github.com/Utkarsh1244p/laravel-crud-generator
