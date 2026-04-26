<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Librarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = collect([
            'Fantasy', 'History', 'Science', 'Technology',
            'Political Science', 'Literature', 'Arts', 'Reference',
        ])->mapWithKeys(fn($name) => [$name => Category::create(['name' => $name])]);

        // Default Admin/Librarian Account
        $admin = Librarian::create([
            'name'       => 'Admin',
            'email'      => 'admin@library.local',
            'password'   => Hash::make('admin123'),
            'contact_no' => '09000000000',
            'role'       => 'Head Librarian',
        ]);

        // Librarian
        $librarian = Librarian::create([
            'name'       => 'James Brian',
            'email'      => 'jbrian@dclic.gov.ph',
            'password'   => Hash::make('password'),
            'contact_no' => '09123456789',
            'role'       => 'Head Librarian',
        ]);

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
            ['name' => 'Maria Cruz', 'email' => 'mcruz@email.com',    'contact_no' => '09281234567'],
            ['name' => 'Rico Tan',   'email' => 'ricot@email.com',    'contact_no' => '09391234567'],
            ['name' => 'Lisa Go',    'email' => 'lisago@email.com',   'contact_no' => '09451234567'],
            ['name' => 'Ben Lim',    'email' => 'benlim@email.com',   'contact_no' => '09561234567'],
        ];

        $userModels = [];
        foreach ($users as $data) {
            $userModels[$data['name']] = User::create($data);
        }

        // Seed transactions
        Borrowing::create([
            'user_id'      => $userModels['Rico Tan']->id,
            'book_id'      => $bookModels['Philippine Politics']->id,
            'librarian_id' => $librarian->id,
            'date_borrowed'=> Carbon::now()->subDays(45),
            'due_date'     => Carbon::now()->subDays(15),
            'status'       => 'Overdue',
        ]);

        Borrowing::create([
            'user_id'      => $userModels['John Doe']->id,
            'book_id'      => $bookModels['Love']->id,
            'librarian_id' => $librarian->id,
            'date_borrowed'=> Carbon::now()->subDays(15),
            'due_date'     => Carbon::now()->addDays(15),
            'status'       => 'Borrowed',
        ]);

        Borrowing::create([
            'user_id'      => $userModels['Maria Cruz']->id,
            'book_id'      => $bookModels['Dreams']->id,
            'librarian_id' => $librarian->id,
            'date_borrowed'=> Carbon::now()->subDays(11),
            'due_date'     => Carbon::now()->addDays(19),
            'status'       => 'Borrowed',
        ]);

        Borrowing::create([
            'user_id'      => $userModels['Lisa Go']->id,
            'book_id'      => $bookModels['Noli Me Tangere']->id,
            'librarian_id' => $librarian->id,
            'date_borrowed'=> Carbon::now()->subDays(35),
            'due_date'     => Carbon::now()->subDays(5),
            'return_date'  => Carbon::now()->subDays(2),
            'status'       => 'Returned',
            'penalty'      => 0,
        ]);
    }
}