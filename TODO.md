# TODO

- [x] Add migration to drop `name` column from `users` and `librarians`
- [x] Update `AuthController@register` to stop writing `name` and only use `first_name`/`last_name`
- [x] Update `app/Models/User.php` remove `name` from `$fillable`
- [ ] Verify codebase for any direct `$user->name` / `->name` usage that refers to DB column (replace with `fullName()` or first/last)
- [ ] Run `php artisan migrate`
- [ ] Smoke test: register + profile full name
