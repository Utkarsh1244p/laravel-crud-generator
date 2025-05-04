# Laravel CRUD Generator

<!--
[![Latest Version](https://img.shields.io/github/v/release/utkarshgayguwal/laravel-crud-generator?style=flat-square)](https://packagist.org/packages/utkarshgayguwal/laravel-crud-generator)
[![License](https://img.shields.io/github/license/utkarshgayguwal/laravel-crud-generator?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/utkarshgayguwal/laravel-crud-generator?style=flat-square)](https://packagist.org/packages/utkarshgayguwal/laravel-crud-generator)
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

### 📦 Full Stack Generation

| Component | Includes |
| --- | --- |
| Model | HasFactory, SoftDeletes, guarded $id |
| Controller | Complete CRUD methods with error handling |
| Migration | Field type detection + modifiers |
| Factory | Smart Faker data generation |
| Request | Separate Store/Update validation |
| Routes | Auto-added to routes/api.php |
| ResponseTrait | Standardized JSON responses |

* * *

## 🛠 Installation

1.  Require package via Composer:

```bash
composer require utkarshgayguwal/laravel-crud-generator
```
* * *

## 💻 Usage

### Basic Command

```bash
php artisan make:crud Post \--fields\="title:string,body:text"
```

### Field Syntax

field\_name:type\[:modifiers\]

#### Supported Types

| Type | Description | Example |
| --- | --- | --- |
| string | VARCHAR | name:string |
| text | TEXT | content:text |
| integer | INT | quantity:integer |
| decimal | DECIMAL | price:decimal:precision(8,2) |
| boolean | TINYINT(1) | is_active:boolean |
| foreign | Creates relationship | user_id:foreign:users:id |

#### Field Modifiers

| Modifier | Example | Result |
| --- | --- | --- |
| nullable | bio:text:nullable | $table->text('bio')->nullable() |
| default(value) | status:string:default(draft) | $table->string('status')->default('draft') |
| unique | email:string:unique | $table->string('email')->unique() |
| index | slug:string:index | $table->string('slug')->index() |
| cascade | user_id:foreign:cascade | Adds ->onDelete('cascade') |

* * *

## 🏗 Generated Code Examples

### 1\. Model (`app/Models/Post.php`)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model 
{
    use HasFactory, SoftDeletes;
    protected $guarded = ['id'];
}
```

### 2\. Controller (`app/Http/Controllers/PostController.php`)

```php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;

class PostController extends Controller
{
    public function store(StorePostRequest $request)
    {
        try {
            $post = Post::create($request->validated());
            return $this->successResponse($post, 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    // Includes index(), show(), update(), destroy()
}
```

### 3\. Migration (`database/migrations/xxxx_create_posts_table.php`)

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('body')->nullable();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

### 4\. Factory (`database/factories/PostFactory.php`)

```php
class PostFactory extends Factory
{
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'body' => $this->faker->paragraph,
            'user_id' => \App\Models\User::factory()
        ];
    }
}
```

### 5\. Request (`app/Http/Requests/StorePostRequest.php`)

```php
class StorePostRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'nullable|string'
        ];
    }
}
```

* * *

## ⚙ Advanced Usage

### Generate with Relationships

```bash

php artisan make:crud Comment \
  --fields="
    body:text:required,
    post_id:foreign:posts:id:cascade,
    user_id:foreign:users:id
  "
```

### Customize Response Trait

Edit `app/Traits/ApiResponse.php` to modify:

```php
protected function successResponse($data, $code = 200)
{
    return response()->json([
        'success' => true,
        'data' => $data
    ], $code);
}
```
* * *
## 📦 Installation (Public Repository)

Since the package is not yet published to Packagist, add the GitHub repo manually:

```bash
composer config repositories.crud-generator vcs https://github.com/utkarshgayguwal/laravel-crud-generator
composer require utkarshgayguwal/laravel-crud-generator:dev-main --prefer-source
