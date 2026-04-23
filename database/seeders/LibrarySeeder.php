<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Librarian;
use App\Models\User;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // Create categories
        $categories = [
            'Fiction',
            'Science',
            'History',
            'Self-Help',
            'Technology',
            'Biography',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        // Create sample users (library patrons)
        User::updateOrCreate(
            ['email' => 'student1@library.edu'],
            ['name' => 'Juan dela Cruz', 'contact_no' => '09171234567', 'status' => 'Active', 'password' => null]
        );

        User::updateOrCreate(
            ['email' => 'student2@library.edu'],
            ['name' => 'Maria Santos', 'contact_no' => '09191234567', 'status' => 'Active', 'password' => null]
        );

        User::updateOrCreate(
            ['email' => 'researcher@library.edu'],
            ['name' => 'Dr. Antonio Lopez', 'contact_no' => '09221234567', 'status' => 'Active', 'password' => null]
        );

        // Create sample librarians
        Librarian::firstOrCreate(
            ['email' => 'head@library.edu'],
            ['name' => 'Ms. Rosa Garcia', 'password' => bcrypt('password'), 'contact_no' => '09171111111', 'role' => 'Head Librarian']
        );

        Librarian::firstOrCreate(
            ['email' => 'librarian1@library.edu'],
            ['name' => 'Mr. Fernando Reyes', 'password' => bcrypt('password'), 'contact_no' => '09172222222', 'role' => 'Librarian']
        );

        Librarian::firstOrCreate(
            ['email' => 'staff@library.edu'],
            ['name' => 'Alex Rivera', 'password' => bcrypt('password'), 'contact_no' => '09173333333', 'role' => 'Staff']
        );

        // Create sample books
        $sampleBooks = [
            ['title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald', 'category' => 'Fiction', 'isbn' => '978-0-7432-7356-5', 'quantity' => 3],
            ['title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee', 'category' => 'Fiction', 'isbn' => '978-0-06-112008-4', 'quantity' => 2],
            ['title' => 'A Brief History of Time', 'author' => 'Stephen Hawking', 'category' => 'Science', 'isbn' => '978-0-553-38016-3', 'quantity' => 2],
            ['title' => 'Sapiens', 'author' => 'Yuval Noah Harari', 'category' => 'History', 'isbn' => '978-0-06-231609-7', 'quantity' => 4],
            ['title' => 'Atomic Habits', 'author' => 'James Clear', 'category' => 'Self-Help', 'isbn' => '978-0735211292', 'quantity' => 5],
            ['title' => 'The Pragmatic Programmer', 'author' => 'David Thomas & Andrew Hunt', 'category' => 'Technology', 'isbn' => '978-0-13-595705-9', 'quantity' => 2],
            ['title' => 'Steve Jobs', 'author' => 'Walter Isaacson', 'category' => 'Biography', 'isbn' => '978-1-4516-4853-9', 'quantity' => 3],
            ['title' => '1984', 'author' => 'George Orwell', 'category' => 'Fiction', 'isbn' => '978-0-452-26256-1', 'quantity' => 2],
        ];

        foreach ($sampleBooks as $book) {
            $category = Category::where('name', $book['category'])->first();
            Book::firstOrCreate(
                ['isbn' => $book['isbn']],
                [
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'category_id' => $category->id,
                    'quantity' => $book['quantity'],
                    'status' => 'Available',
                ]
            );
        }

        $this->command->info('✅ Library seeding completed successfully!');
    }
}
