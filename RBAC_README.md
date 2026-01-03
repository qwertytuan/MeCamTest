# Role-Based Access Control (RBAC) Implementation

This Laravel application now includes a complete Role-Based Access Control system with fine-grained permissions.

## 📋 Table of Contents

- [Quick Start](#quick-start)
- [Features](#features)
- [Architecture](#architecture)
- [Documentation](#documentation)
- [Usage](#usage)
- [Testing](#testing)

## 🚀 Quick Start

### 1. Setup Database
```bash
php artisan migrate:fresh --seed
```

### 2. Test Users (All passwords: `password`)
- **Admin:** admin@example.com
- **User:** test@example.com
- **Guest:** guest@example.com

### 3. Login and Get Token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

### 4. Use Token in Requests
```bash
curl -X GET http://localhost:8000/api/product \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## ✨ Features

### 🔐 Three-Level Access Control
1. **Authentication** - JWT token-based
2. **Roles** - Admin, User, Guest
3. **Permissions** - Granular action-level control

### 📊 Built-in Permissions
- ✅ `can_create` - Create new resources
- ✅ `can_read` - View resources
- ✅ `can_update` - Update resources
- ✅ `can_delete` - Delete resources
- ✅ `can_manage_users` - User management
- ✅ `can_manage_roles` - Role management
- ✅ `can_manage_permissions` - Permission management
- ✅ `can_manage_cameras` - Camera management

### 🎭 Default Roles

| Role | Create | Read | Update | Delete | Manage Users | Manage Cameras |
|------|--------|------|--------|--------|--------------|----------------|
| **Admin** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **User** | ✓ | ✓ | ✓ | ✓ | ✗ | ✓ |
| **Guest** | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ |

## 🏗️ Architecture

```
User
  └─ UserRole (pivot)
      └─ Role
          └─ Permission
```

Each user can have one role, and each role has a set of permissions.

## 📚 Documentation

### Core Documentation
- **[RBAC_DOCUMENTATION.md](RBAC_DOCUMENTATION.md)** - Complete system documentation
- **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)** - Quick reference card
- **[TESTING_RBAC.md](TESTING_RBAC.md)** - Testing guide with examples
- **[RBAC_SUMMARY.md](RBAC_SUMMARY.md)** - Implementation summary

### Code Examples
- **[ExampleRBACController.php](app/Http/Controllers/ExampleRBACController.php)** - Full examples of RBAC usage

## 💡 Usage

### In Controllers

```php
class YourController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:can_create')->only(['store']);
        $this->middleware('role:Admin')->only(['adminMethod']);
    }
}
```

### In Routes

```php
Route::post('/resource', [Controller::class, 'store'])
    ->middleware(['auth:api', 'permission:can_create']);

Route::get('/admin/panel', [Controller::class, 'admin'])
    ->middleware(['auth:api', 'role:Admin']);
```

### Manual Checks

```php
// Check role
if (auth()->user()->hasRole('Admin')) {
    // Admin logic
}

// Check permission
if (auth()->user()->hasPermission('can_create')) {
    // Create logic
}

// Check multiple roles
if (auth()->user()->hasAnyRole(['Admin', 'User'])) {
    // Logic for multiple roles
}
```

### In Blade Views

```blade
@if(auth()->user()->hasPermission('can_create'))
    <button>Create New</button>
@endif

@if(auth()->user()->hasRole('Admin'))
    <a href="/admin">Admin Panel</a>
@endif
```

## 🧪 Testing

### Test Permission Access

```bash
# As Admin (should work)
curl -X POST http://localhost:8000/api/product \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","price":99.99,"stock":10}'

# As Guest (should return 403)
curl -X POST http://localhost:8000/api/product \
  -H "Authorization: Bearer GUEST_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","price":99.99,"stock":10}'
```

### Test Role Access

```bash
# As Admin (should work)
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer ADMIN_TOKEN"

# As User (should return 403)
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer USER_TOKEN"
```

### Using Laravel Tinker

```bash
php artisan tinker
```

```php
// Get user with roles and permissions
$user = User::with('roles.permissions')->find(1);

// Check permissions
$user->hasPermission('can_create'); // true/false

// Get all permissions
$user->getPermissions();

// Check roles
$user->hasRole('Admin'); // true/false
```

## 🔧 API Endpoints

### Products (Protected by Permissions)
- `GET /api/product` - List products (requires `can_read`)
- `POST /api/product` - Create product (requires `can_create`)
- `PUT /api/product/{id}` - Update product (requires `can_update`)
- `DELETE /api/product/{id}` - Delete product (requires `can_delete`)

### Cameras (Protected by Permissions)
- `GET /api/admin/cameras` - List cameras (requires `can_read`)
- `POST /api/admin/cameras` - Create camera (requires `can_manage_cameras`)
- `PUT /api/admin/cameras/{id}` - Update camera (requires `can_manage_cameras`)
- `DELETE /api/admin/cameras/{id}` - Delete camera (requires `can_manage_cameras`)

### Admin (Protected by Role)
- `GET /api/admin/users` - List users (requires `Admin` role)
- `DELETE /api/admin/users/{id}` - Delete user (requires `Admin` role)
- `GET /api/admin/deleted-users` - List deleted users (requires `Admin` role)
- `POST /api/admin/restore-user/{id}` - Restore user (requires `Admin` role)

## 📦 Files Created/Modified

### New Files
- `app/Http/Middleware/CheckPermission.php` - Permission middleware
- `app/Http/Middleware/CheckRole.php` - Role middleware
- `app/View/Components/HasPermission.php` - Blade permission component
- `app/View/Components/HasRole.php` - Blade role component
- `app/Http/Controllers/ExampleRBACController.php` - Example implementations

### Modified Files
- `app/Models/User.php` - Added role/permission methods
- `app/Models/Role.php` - Added permissions relationship
- `app/Http/Controllers/Controller.php` - Added middleware support
- `app/Http/Controllers/*Controller.php` - Added permission checks
- `bootstrap/app.php` - Registered middlewares
- `database/seeders/DatabaseSeeder.php` - Added role/permission seeding

## 🔍 Troubleshooting

### Issue: "Unauthenticated"
**Solution:** Include valid JWT token in Authorization header

### Issue: "You do not have permission"
**Solution:** User lacks required permission. Check user's role permissions

### Issue: "You do not have the required role"
**Solution:** User lacks required role. Assign appropriate role to user

### Issue: Foreign key constraint error
**Solution:** Run `php artisan migrate:fresh --seed` to reset database

## 🎯 Best Practices

1. ✅ Always use `auth:api` middleware before role/permission checks
2. ✅ Combine role and permission checks for sensitive operations
3. ✅ Check resource ownership in addition to permissions
4. ✅ Use descriptive permission names
5. ✅ Document which roles have which permissions
6. ✅ Regularly audit user roles and permissions

## 🔄 Migration Guide

If you're upgrading an existing application:

```bash
# 1. Backup your database
mysqldump -u user -p database > backup.sql

# 2. Run migrations
php artisan migrate

# 3. Seed roles and permissions
php artisan db:seed

# 4. Assign roles to existing users
php artisan tinker
>>> User::find(1)->roles()->attach(1); // Assign role_id 1 to user_id 1
```

## 🚦 HTTP Status Codes

- **200** - Success
- **201** - Resource created
- **401** - Unauthenticated (no/invalid token)
- **403** - Forbidden (no permission/role)
- **404** - Resource not found
- **422** - Validation error
- **500** - Server error

## 📝 License

This RBAC implementation is part of the AuthTestJwt Laravel application.

## 🤝 Contributing

When adding new features that require permissions:

1. Add permission column to `permissions` table migration
2. Update `Permission` model's `$fillable` array
3. Update seeder to set permission values for each role
4. Add middleware to routes or controller constructor
5. Update documentation

---

**Need Help?** Check the detailed documentation files or the example controller for comprehensive usage examples.

