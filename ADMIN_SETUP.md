# Admin Panel Setup — نظام الغسيل

## Overview

This washing system project includes a complete admin panel for managing customers, wash orders, payments, and SMS settings — with full user, role, and permission management using Filament and Spatie Laravel Permission.

## Customers

When adding a customer, configure only:

- **Name and phone** — required contact details
- **الرصيد الافتتاحي (opening balance)** — optional credit or debit at signup

There is no initial washer counter field. Each wash order is independent: enter **وزن الغسيل** (kg), and the system calculates **تكلفة الغسل** using **سعر الكيلo** and **الحد الأدنى للمبلغ** from settings. Multiple orders per customer per day are allowed.

## Admin Users Created

The following test admin accounts have been created:

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | admin123 | Super Admin |
| admin2@example.com | admin123 | Admin |
| editor@example.com | editor123 | Editor |
| viewer@example.com | viewer123 | Viewer |

## Roles Overview

### 1. Super Admin
- **Permissions**: All permissions
- **Access**: Full control over the entire system
- **Capabilities**:
  - Manage users
  - Manage roles and permissions
  - Manage all resources (Categories, Content)
  - Access all features

### 2. Admin
- **Permissions**: All except permission management
- **Access**: Full resource management
- **Capabilities**:
  - Manage users
  - Manage all resources (Categories, Content)
  - Cannot manage roles or permissions

### 3. Editor
- **Permissions**: View, create, update for Categories and Content
- **Access**: Limited content management
- **Capabilities**:
  - View, create, and update categories
  - View, create, and update content
  - Cannot delete resources

### 4. Viewer
- **Permissions**: View only
- **Access**: Read-only access
- **Capabilities**:
  - View categories
  - View content
  - View users
  - Cannot make any modifications

## Permission List

### Category Permissions
- `view_category` - View categories
- `create_category` - Create new categories
- `update_category` - Edit categories
- `delete_category` - Delete categories
- `restore_category` - Restore deleted categories
- `force_delete_category` - Permanently delete categories

### Content Permissions
- `view_content` - View content
- `create_content` - Create new content
- `update_content` - Edit content
- `delete_content` - Delete content
- `restore_content` - Restore deleted content
- `force_delete_content` - Permanently delete content

### User Permissions
- `view_user` - View users
- `create_user` - Create new users
- `update_user` - Edit users
- `delete_user` - Delete users

### Role & Permission Management
- `view_role` - View roles
- `create_role` - Create new roles
- `update_role` - Edit roles
- `delete_role` - Delete roles
- `view_permission` - View permissions
- `create_permission` - Create new permissions
- `update_permission` - Edit permissions
- `delete_permission` - Delete permissions

## Filament Resources

Three main admin resources are available:

### 1. Users (`/admin/users`)
- View all users
- Create new users with role assignment
- Edit user information and roles
- Delete users
- Filter by roles

**Features**:
- Name and email management
- Role assignment (checkbox group)
- User creation date tracking
- Role display in table with badges

### 2. Roles (`/admin/roles`)
- Create custom roles
- Assign permissions to roles
- View role details
- Track number of permissions per role

**Features**:
- Role naming
- Permission assignment
- Permission count display
- Guard name configuration

### 3. Permissions (`/admin/permissions`)
- Create and manage permissions
- Assign permissions to roles
- View permission details
- Track roles using each permission

**Features**:
- Permission naming
- Role assignment
- Role count display
- Guard name configuration

## Navigation

Access the admin panel at: `http://localhost:8000/admin`

The admin sidebar includes:
1. Dashboard
2. Users (Sort order: 1)
3. Roles (Sort order: 2)
4. Permissions (Sort order: 3)
5. Categories
6. Content

## How to Use

### Login to Admin Panel
1. Navigate to `http://localhost:8000/admin`
2. Use any of the credentials above
3. You'll be logged in with the corresponding role's permissions

### Creating a New User
1. Navigate to **Users**
2. Click **Create** button
3. Fill in name and email
4. Set a password
5. Select one or more roles from the **Roles & Permissions** section
6. Click **Save**

### Creating a New Role
1. Navigate to **Roles**
2. Click **Create** button
3. Enter a role name
4. Select permissions for this role
5. Click **Save**

### Managing Permissions
1. Navigate to **Permissions**
2. View existing permissions
3. Edit to assign permissions to different roles
4. Create new permissions as needed

### Assigning Permissions to Roles
1. Navigate to **Roles**
2. Click a role to edit
3. In the **Permissions** section, check/uncheck permissions
4. Click **Save**

## Database Tables

The following tables are created for role and permission management:

- `roles` - Store role definitions
- `permissions` - Store permission definitions
- `role_has_permissions` - Junction table linking roles to permissions
- `model_has_roles` - Junction table linking users to roles
- `model_has_permissions` - Junction table for direct user permissions

## Additional Configuration

### Config Files

**`config/permission.php`** - Spatie Permission configuration
- Model settings
- Table names
- Cache configuration

**`config/filament-shield.php`** - Filament Shield configuration
- Shield display options
- Super admin configuration

## Security Notes

1. **Password Management**: Change the default admin passwords before deploying to production
2. **Super Admin Role**: Keep Super Admin role restricted to trusted admins
3. **Permission Hierarchy**: Higher roles (Super Admin → Admin → Editor → Viewer) should be carefully managed
4. **Audit Trail**: Monitor who has access to sensitive roles and permissions

## Troubleshooting

### Issue: "Cannot access admin panel"
- Ensure you're logged in with a user that has a role assigned
- Check the user's role has the required permissions

### Issue: "Permission denied" error
- Verify the user has the correct role assigned
- Check the role has the required permission
- Clear the permission cache: `php artisan cache:clear`

### Issue: "Roles not showing up"
- Ensure roles are created in the Roles section
- Verify the database migration ran successfully
- Check that the `roles` table is populated

## Extending the System

To add permissions for new resources:

1. Edit `database/seeders/AdminUserSeeder.php`
2. Add new permissions to the `$permissions` array
3. Run: `php artisan db:seed --class=AdminUserSeeder`

To create custom middleware for permission checks:
```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-users', function ($user) {
    return $user->hasPermissionTo('manage_users');
});
```

Then use in controllers:
```php
if (Gate::denies('manage-users')) {
    abort(403);
}
```
