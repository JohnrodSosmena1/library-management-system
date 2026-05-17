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
            'Philosophy', 'Math', 'Health', 'Economics', 'Religion',
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

        // Borrowings / Transactions
        // Create sample borrow records so they appear in phpMyAdmin immediately.
        // NOTE: Triggers (created in 2026_05_09_000001_create_library_triggers_if_missing.php)
        // will handle quantity/status updates when the triggers exist.

        $uJohn  = User::where('email', 'johndoe@gmail.com')->first();
        $uMaria = User::where('email', 'mcruz@email.com')->first();
        $uRico  = User::where('email', 'ricot@email.com')->first();
        $uLisa  = User::where('email', 'lisago@email.com')->first();
        $uBen   = User::where('email', 'benlim@email.com')->first();

        // If triggers are not installed, this still works; it will just not auto-update quantities.
        // Use CURRENT date-based values so overdue computations are consistent.
        $borrowedAt = Carbon::now()->subDays(10)->toDateString();
        $dueAt      = Carbon::now()->subDays(3)->toDateString();

        // Avoid exhausting quantities due to trigger-based decrement.
        // Keep each additional overdue row for distinct books where possible.


        $borrowings = [
            // Pending (request waiting for librarian approval)
            ['book' => 'Dreams', 'user' => $uJohn,  'status' => 'Pending',  'borrow_date' => null, 'due_date' => null, 'return_date' => null],
            ['book' => 'Cosmos', 'user' => $uMaria, 'status' => 'Pending',  'borrow_date' => null, 'due_date' => null, 'return_date' => null],

            // Borrowed (not overdue)
            ['book' => 'Love', 'user' => $uJohn,  'status' => 'Borrowed', 'borrow_date' => $borrowedAt, 'due_date' => Carbon::now()->addDays(7)->toDateString(), 'return_date' => null],
            ['book' => 'Cosmos', 'user' => $uBen,   'status' => 'Borrowed', 'borrow_date' => Carbon::now()->subDays(6)->toDateString(), 'due_date' => Carbon::now()->addDays(9)->toDateString(), 'return_date' => null],
            ['book' => 'Noli Me Tangere', 'user' => $uLisa, 'status' => 'Borrowed', 'borrow_date' => Carbon::now()->subDays(8)->toDateString(), 'due_date' => Carbon::now()->addDays(15)->toDateString(), 'return_date' => null],

            // Overdue
            ['book' => 'Philippine Politics', 'user' => $uRico, 'status' => 'Overdue', 'borrow_date' => Carbon::now()->subDays(20)->toDateString(), 'due_date' => $dueAt, 'return_date' => null],
            ['book' => 'Cosmos', 'user' => $uRico, 'status' => 'Overdue', 'borrow_date' => Carbon::now()->subDays(16)->toDateString(), 'due_date' => Carbon::now()->subDays(9)->toDateString(), 'return_date' => null],
            ['book' => 'Love', 'user' => $uMaria, 'status' => 'Overdue', 'borrow_date' => Carbon::now()->subDays(14)->toDateString(), 'due_date' => Carbon::now()->subDays(7)->toDateString(), 'return_date' => null],

            // Returned (with some late returns to show fines)
            ['book' => 'Noli Me Tangere', 'user' => $uLisa, 'status' => 'Returned', 'borrow_date' => Carbon::now()->subDays(30)->toDateString(), 'due_date' => Carbon::now()->subDays(20)->toDateString(), 'return_date' => Carbon::now()->subDays(5)->toDateString()],
            ['book' => 'Love', 'user' => $uBen, 'status' => 'Returned', 'borrow_date' => Carbon::now()->subDays(12)->toDateString(), 'due_date' => Carbon::now()->subDays(6)->toDateString(), 'return_date' => Carbon::now()->subDays(4)->toDateString()],

            // Rejected (request denied)
            ['book' => 'Intro to Programming', 'user' => $uJohn, 'status' => 'Rejected', 'borrow_date' => null, 'due_date' => null, 'return_date' => null],
        ];

        foreach ($borrowings as $b) {
            $book = $bookModels[$b['book']] ?? Book::where('title', $b['book'])->first();
            if (!$book || !$b['user']) {
                continue;
            }

            Borrowing::updateOrCreate(
                [
                    'user_id' => $b['user']->id,
                    'book_id' => $book->id,
                    'status'  => $b['status'],
                ],
                [
                    'date_borrowed' => $b['borrow_date'],
                    'due_date'      => $b['due_date'],
                    'return_date'   => $b['return_date'],
                ]
            );
        }

    }
}


