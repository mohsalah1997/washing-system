# 🚀 Quick Reference - Admin Panel Guide

## 📍 Access Points

| Section | URL | Purpose |
|---------|-----|---------|
| Admin Dashboard | `/admin` | Main admin hub |
| Users | `/admin/users` | Manage admin accounts |
| Roles | `/admin/roles` | Create/edit roles |
| Permissions | `/admin/permissions` | Manage permissions |
| Categories | `/admin/categories` | Manage content categories |
| Content | `/admin/content` | Manage content items |

## 🔑 Login Credentials

```
Super Admin Access:
Email: admin@example.com
Password: admin123

Admin Access:
Email: admin2@example.com
Password: admin123

Editor Access:
Email: editor@example.com
Password: editor123

Viewer Access (Read-Only):
Email: viewer@example.com
Password: viewer123
```

## 📋 Common Tasks

### Create a New Admin User
1. Navigate to `/admin/users`
2. Click "Create" button
3. Enter: Name, Email, Password
4. Select role(s) from the "Roles & Permissions" section
5. Click "Save"

### Create a New Role
1. Navigate to `/admin/roles`
2. Click "Create" button
3. Enter role name (e.g., "moderator")
4. Check permissions to assign
5. Click "Save"

### Add Permission to Role
1. Navigate to `/admin/roles`
2. Click to edit the role
3. In "Permissions" section, check desired permissions
4. Click "Save"

### Create New Permission
1. Navigate to `/admin/permissions`
2. Click "Create" button
3. Enter permission name (e.g., "publish_content")
4. Optionally assign roles
5. Click "Save"

### Change User's Role
1. Navigate to `/admin/users`
2. Click edit button for user
3. In "Roles & Permissions" section, check/uncheck roles
4. Click "Save"

## 🔍 Filtering & Searching

### Users Filter
- **By Role**: Click "Roles" filter dropdown, select role
- **By Name/Email**: Use search box at top

### Roles Filter
- **By Name**: Use search box
- **Sort by**: Click column header

### Permissions Filter
- **By Name**: Use search box
- **Sort by**: Click column header

## 🎯 Permission Naming Convention

```
[action]_[resource]

Examples:
- view_category
- create_content
- update_user
- delete_role
- restore_permission
- force_delete_category
```

## 📊 Role Capabilities Summary

```
Super Admin (super_admin)
├── 24 Permissions
├── Full system access
└── Can manage roles & permissions

Admin (admin)
├── 20 Permissions
├── All resource management
└── Cannot manage permissions

Editor (editor)
├── 6 Permissions
├── Can view, create, update
└── Cannot delete or manage users

Viewer (viewer)
├── 3 Permissions
├── View only access
└── No modification rights
```

## 🛡️ Security Best Practices

1. **Change Default Passwords**
   - All test accounts use default passwords
   - Change before going to production

2. **Limit Super Admins**
   - Use super_admin role sparingly
   - Regular admins sufficient for most tasks

3. **Monitor Access**
   - Review user access logs
   - Track who made what changes

4. **Backup Data**
   - Regular database backups
   - Test restoration procedures

5. **Use Strong Passwords**
   - Minimum 8 characters
   - Mix of upper, lower, numbers, symbols

## 🐛 Troubleshooting

### "Permission Denied" Error
**Solution**: 
1. Check user's role in `/admin/users`
2. Verify role has required permission in `/admin/roles`
3. Clear cache: `php artisan cache:clear`

### Can't See Admin Panel
**Solution**:
1. Ensure you're logged in
2. Check user has a role assigned
3. Verify role has necessary permissions

### Changes Not Taking Effect
**Solution**:
1. Clear application cache: `php artisan cache:clear`
2. Clear query cache: `php artisan cache:forget spatie.permission.cache`
3. Log out and log back in

### User Can't Create Items
**Solution**:
1. Check user's role permissions
2. Add `create_[resource]` permission to role
3. Verify role is assigned to user

## 📱 Mobile Support

The admin panel is fully responsive:
- Works on desktop (recommended for complex tasks)
- Works on tablets (good for browsing/editing)
- Works on mobile (limited for form-heavy tasks)

## ⌨️ Keyboard Shortcuts

- `Ctrl+F` - Search in table
- `Enter` - Submit form
- `Esc` - Close modal/dialog

## 📧 Account Management

### Reset User Password
1. Go to `/admin/users`
2. Edit the user
3. Enter new password
4. Save

### Deactivate User
1. Delete the user (soft delete by default)
2. Can be restored if needed

### Export User Data
(Currently not implemented, can be added)

## 🔄 Sync Permissions

If you add new resources and need to sync permissions:

1. Update `database/seeders/AdminUserSeeder.php`
2. Add new permissions to the array
3. Run: `php artisan db:seed --class=AdminUserSeeder`

## 📞 Support Resources

- **Filament Docs**: https://filamentphp.com/docs
- **Spatie Permission**: https://spatie.be/docs/laravel-permission
- **Laravel Documentation**: https://laravel.com/docs
- **Admin Setup Guide**: See `ADMIN_SETUP.md`
- **Features List**: See `FEATURES_IMPLEMENTED.md`

## ✨ Pro Tips

1. **Organize Roles by Function**
   - Create specific roles for each department
   - Example: content_manager, reviewer, publisher

2. **Regular Audits**
   - Review user access monthly
   - Remove unused accounts

3. **Permission Naming**
   - Be consistent with naming
   - Document custom permissions

4. **Test Before Deploy**
   - Create test users with each role
   - Verify all permissions work

5. **Backup Important Data**
   - Before major permission changes
   - Before deleting roles/permissions

---

**Last Updated**: January 19, 2026
**Status**: Ready for Use ✅
