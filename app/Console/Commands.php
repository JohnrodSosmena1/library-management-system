<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use Illuminate\Console\Command;

class MarkOverdueCommand extends Command
{
    protected $signature   = 'library:mark-overdue';
    protected $description = 'Mark all past-due borrowings and books as Overdue';

    public function handle(): int
    {
        $updated = Borrowing::where('status', 'Borrowed')
            ->where('due_date', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($updated as $borrowing) {
            $borrowing->update(['status' => 'Overdue']);
            $borrowing->book->update(['status' => 'Overdue']);
            $count++;
        }

        $this->info("Marked {$count} borrowing(s) as overdue.");
        return Command::SUCCESS;
    }
}