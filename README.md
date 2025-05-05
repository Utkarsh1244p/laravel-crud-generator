# Laravel Smart Scaffold

[![Latest Version](https://img.shields.io/github/v/release/utkarshgayguwal/laravel-smart-scaffold?style=flat-square)](https://packagist.org/packages/utkarshgayguwal/laravel-smart-scaffold)
[![License](https://img.shields.io/github/license/utkarshgayguwal/laravel-smart-scaffold?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/utkarshgayguwal/laravel-smart-scaffold?style=flat-square)](https://packagist.org/packages/utkarshgayguwal/laravel-smart-scaffold)
[![PHP Version](https://img.shields.io/packagist/php-v/utkarshgayguwal/laravel-smart-scaffold?style=flat-square)](https://packagist.org/packages/utkarshgayguwal/laravel-smart-scaffold)

🚀 Generate complete Laravel CRUD operations with a single command! Smart Scaffold automatically creates all necessary components, including models, controllers with error handling, migrations, requests, factories, resources, and routes - ready for immediate API testing in Postman.

---

## 🚀 Why Choose Smart Scaffold?

- ⚡ **Instant Development** - Get your API endpoints working in minutes
- 🛡 **Production-Ready Code** - Built-in error handling and validation
- 🎯 **Complete API Stack** - Everything you need for RESTful APIs
- 🤖 **Smart Generation** - Automatic relationships and field detection
- 🎯 **Postman-Ready** - API endpoints ready for immediate testing

---

## ✨ Key Features

### 📦 Complete CRUD Stack

- **Models** with `HasFactory` and `SoftDeletes`
- **Controllers** with comprehensive CRUD methods and error handling
- **Migrations** with smart field type detection
- **Factories** with intelligent Faker data generation
- **Requests** with separate Store/Update validation
- **Resources** for standardized API responses
- **Routes** automatically added to `routes/api.php`

### 🔥 Advanced Features

- **Smart Relationships** - Automatic foreign key detection
- **Error Handling** - Built-in try-catch blocks
- **Standardized Responses** - Consistent JSON API format
- **Flexible Field Types** - Support for all Laravel field types
- **Customizable Validation** - Configurable validation rules
- **Postman Integration** - Ready-to-use API endpoints

### 🛠️ Field Types & Modifiers

| Type | Description | Example |
| --- | --- | --- |
| `string` | VARCHAR | `name:string` |
| `text` | TEXT | `content:text` |
| `integer` | INT | `quantity:integer` |
| `decimal` | DECIMAL | `price:decimal:precision(8,2)` |
| `boolean` | TINYINT(1) | `is_active:boolean` |
| `foreign` | Creates relationship | `user_id:foreign:users:id` |

| Modifier | Example | Result |
| --- | --- | --- |
| `nullable` | `bio:text:nullable` | `$table->text('bio')->nullable()` |
| `default(value)` | `status:string:default(draft)` | `$table->string('status')->default('draft')` |
| `unique` | `email:string:unique` | `$table->string('email')->unique()` |
| `index` | `slug:string:index` | `$table->string('slug')->index()` |
| `cascade` | `user_id:foreign:cascade` | Adds `->onDelete('cascade')` |

---

## 🛠 Installation

```bash
# Install via Composer
composer require utkarshgayguwal/laravel-smart-scaffold

# Run the publish command (optional)
php artisan vendor:publish --provider="UtkarshGayguwal\SmartScaffold\Providers\SmartScaffoldServiceProvider"
```

---

## 💻 Usage Examples

### Basic CRUD Generation

```bash
# Generate a Category model with name and description
php artisan make:crud Category --fields='name:string,description:text'; 
```

### Advanced Field Types

```bash
# Generate with various field types and modifiers
php artisan make:crud Product \
  --fields="
    name:string:required:max:255,
    description:text:nullable,
    price:decimal:default(0),
    stock:integer:default(0),
    category_id:foreign:categories:id:cascade
  "
```

## 🚀 Quick Start

1. Install the package:
```bash
composer require utkarshgayguwal/laravel-smart-scaffold
```

2. Generate a CRUD:
```bash
php artisan make:crud User --fields="email:string:unique,password:string"
```

3. Test in Postman:
- GET `/api/products` - List all users
- POST `/api/products` - Create a new user
- GET `/api/products/{id}` - View a user
- POST `/api/products/{id}?_method=PUT` - Update a user
- DELETE `/api/products/{id}` - Delete a user

---

## 📝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---


