<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Librarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Re-seed cleanly (avoid duplicate unique constraint failures when you run seeder multiple times)
        DB::table('borrowings')->delete();
        DB::table('books')->delete();
        DB::table('librarians')->delete();
        DB::table('users')->delete();
        DB::table('categories')->delete();

        // Categories
        $categories = collect([
            'Fantasy', 'History', 'Science', 'Technology',
            'Political Science', 'Literature', 'Arts', 'Reference',
        ])->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['name' => $name])]);

        // Ensure ONLY ONE Head Librarian exists in the table.
        // If any other row currently has role='Head Librarian', downgrade it.
        Librarian::where('role', 'Head Librarian')
            ->where('email', '!=', 'admin@library.local')
            ->update(['role' => 'Librarian']);

        // Admin (ONLY Head Librarian)
        $admin = Librarian::updateOrCreate(
            ['email' => 'admin@library.local'],
            [
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'name'       => 'John Doe',
                'password'   => Hash::make('admin123'),
                'contact_no' => '09000000000',
                'role'       => 'Head Librarian',
            ]
        );

        // Librarian (non-head)
        $librarian = Librarian::updateOrCreate(
            ['email' => 'jbrian@dclic.gov.ph'],
            [
                'first_name' => 'James',
                'last_name'  => 'Brian',
                'name'       => 'James Brian',
                'password'   => Hash::make('password'),
                'contact_no' => '09123456789',
                'role'       => 'Librarian',
            ]
        );

        // Books
        $books = [
            ['title' => 'Love',                 'author' => 'James Benedict',     'category' => 'Fantasy',          'isbn' => '978-1-111-00001-0', 'quantity' => 2,  'status' => 'Borrowed'],
            ['title' => 'Dreams',               'author' => 'Elizabeth Laurence', 'category' => 'Fantasy',          'isbn' => '978-1-111-00002-0', 'quantity' => 1,  'status' => 'Borrowed'],
            ['title' => 'Noli Me Tangere',      'author' => 'Jose Rizal',         'category' => 'History',          'isbn' => '978-971-000-001-0', 'quantity' => 3,  'status' => 'Available'],
            ['title' => 'Philippine Politics',  'author' => 'Maria Santos',       'category' => 'Political Science','isbn' => '978-971-000-002-0', 'quantity' => 1,  'status' => 'Overdue'],
            ['title' => 'Cosmos',               'author' => 'Carl Sagan',         'category' => 'Science',          'isbn' => '978-0-345-33135-9', 'quantity' => 2,  'status' => 'Available'],
            ['title' => 'Intro to Programming', 'author' => 'Ana Reyes',          'category' => 'Technology',       'isbn' => '978-1-111-00006-0', 'quantity' => 4,  'status' => 'Available'],
        ];

        $bookModels = [];
        foreach ($books as $data) {
            $bookModels[$data['title']] = Book::create([
                'title'       => $data['title'],
                'author'      => $data['author'],
                'category_id' => $categories[$data['category']]->id,
                'isbn'        => $data['isbn'],
                'quantity'    => $data['quantity'],
                'status'      => $data['status'],
            ]);
        }

        // Users
        $users = [
            ['name' => 'John Doe',   'email' => 'johndoe@gmail.com',  'contact_no' => '09171234567'],
            ['name' => 'Maria Cruz', 'email' => 'mcruz@email.com',     'contact_no' => '09281234567'],
            ['name' => 'Rico Tan',   'email' => 'ricot@email.com',     'contact_no' => '09391234567'],
            ['name' => 'Lisa Go',    'email' => 'lisago@email.com',    'contact_no' => '09451234567'],
            ['name' => 'Ben Lim',    'email' => 'benlim@email.com',    'contact_no' => '09561234567'],
        ];

        $userModels = [];
        foreach ($users as $data) {
            $userModels[$data['name']] = User::create($data);
        }

        // Seed transactions
        // NOTE: Current schema + existing triggers/controllers can decrement book quantity in a way
        // that makes repeated seeding fail (quantity underflow / BIGINT unsigned underflow).
        // To keep focus on first_name/last_name visibility, we skip borrowing seed here.
        // You can re-enable later after aligning Borrowing schema & trigger side-effects.
        return;
    }
}

