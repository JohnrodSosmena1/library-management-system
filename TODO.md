# TODO

- [x] Ensure only one Head Librarian admin account is seeded
    - Comment out `head@library.edu` head seeding in `database/seeders/LibrarySeeder.php`
    - Set `admin@library.local` name to `John Doe` in `database/seeders/DatabaseSeeder.php`

- [ ] Convert `name` to `first_name` + `last_name`
    - [ ] Add migration(s) to `users` table: add first_name/last_name, backfill, then drop `name` (or keep temporarily)
    - [ ] Add migration(s) to `librarians` table: add first_name/last_name, backfill, then drop `name`
    - [ ] Update Eloquent models to use new fields and provide `full_name` accessor
    - [ ] Update controllers search/order logic that currently uses `name`
    - [ ] Update all Blade views/forms that show/submit `name`
    - [ ] Update seeders to populate first_name/last_name instead of name
