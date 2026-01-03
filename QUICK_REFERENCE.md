# RBAC Quick Reference Card

## Test Users (After running: php artisan migrate:fresh --seed)

| Email | Password | Role | Can Do |
|-------|----------|------|--------|
| admin@example.com | password | Admin | Everything |
| test@example.com | password | User | CRUD products, Manage cameras |
| guest@example.com | password | Guest | Read only |

## Middleware Usage

### In Routes
```php
// Single permission
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('permission:can_create');

// Single role
Route::get('/admin/users', [AdminController::class, 'listUsers'])
    ->middleware('role:Admin');

// Multiple roles (any)
Route::get('/content', [ContentController::class, 'index'])
    ->middleware('role:Admin,Moderator');

// Combined
Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])
    ->middleware(['role:Admin', 'permission:can_manage_users']);
```

### In Controllers
```php
public function __construct()
{
    $this->middleware('auth:api');
    $this->middleware('permission:can_create')->only(['store']);
    $this->middleware('permission:can_delete')->only(['destroy']);
    $this->middleware('role:Admin,Moderator')->except(['index', 'show']);
}
```

## Model Methods

### User Model
```php
$user = auth()->user();

// Check single role
$user->hasRole('Admin'); // bool

// Check multiple roles (any)
$user->hasAnyRole(['Admin', 'User']); // bool

// Check permission
$user->hasPermission('can_create'); // bool

// Get all permissions
$user->getPermissions(); // Collection

// Get all roles
$user->roles; // Collection

// Check if admin
$user->isAdmin(); // bool
```

## Blade Directives

### Check Permission
```blade
@if(auth()->user()->hasPermission('can_create'))
    <button>Create New</button>
@endif
```

### Check Role
```blade
@if(auth()->user()->hasRole('Admin'))
    <a href="/admin">Admin Panel</a>
@endif
```

### Using Components
```blade
<x-has-permission permission="can_create">
    <button>Create New</button>
</x-has-permission>

<x-has-role role="Admin">
    <a href="/admin">Admin Panel</a>
</x-has-role>
```

## Available Permissions

| Permission | Description |
|-----------|-------------|
| can_create | Create new resources |
| can_read | View/read resources |
| can_update | Update existing resources |
| can_delete | Delete resources |
| can_manage_users | Manage user accounts |
| can_manage_roles | Manage roles |
| can_manage_permissions | Manage permissions |
| can_manage_cameras | Manage cameras |

## API Testing with cURL

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

### Use Token
```bash
curl -X GET http://localhost:8000/api/product \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Common Responses

### Success (200)
```json
{
  "data": { ... }
}
```

### Unauthenticated (401)
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### No Permission (403)
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

### No Role (403)
```json
{
  "success": false,
  "message": "You do not have the required role to access this resource."
}
```

## Laravel Tinker Commands

```php
// Check user permissions
$user = User::find(1);
$user->getPermissions();

// Assign role to user
UserRole::create(['user_id' => 1, 'role_id' => 2]);

// Create new role
$role = Role::create(['name' => 'Moderator', 'description' => 'Content moderator']);

// Create permissions for role
Permission::create([
    'role_id' => $role->id,
    'can_create' => true,
    'can_read' => true,
    // ... other permissions
]);
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Unauthenticated" | Check JWT token in Authorization header |
| "No permission" | User lacks required permission |
| "No role" | User lacks required role |
| "Foreign key constraint" | Run `migrate:fresh --seed` |
| Middleware not working | Check `bootstrap/app.php` registration |

## File Locations

- **Middleware:** `app/Http/Middleware/`
- **Models:** `app/Models/`
- **Controllers:** `app/Http/Controllers/`
- **Routes:** `routes/api.php`
- **Seeders:** `database/seeders/`
- **Migrations:** `database/migrations/`
- **Config:** `bootstrap/app.php`

## Quick Start

```bash
# 1. Fresh install
php artisan migrate:fresh --seed

# 2. Login as admin
# GET TOKEN from login response

# 3. Test permissions
curl -X GET http://localhost:8000/api/product \
  -H "Authorization: Bearer TOKEN"

# 4. View routes
php artisan route:list

# 5. Debug in tinker
php artisan tinker
>>> $user = User::find(1);
>>> $user->getPermissions();
```

