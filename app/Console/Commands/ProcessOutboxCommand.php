<?php

namespace App\Console\Commands;

use App\Enums\OutboxStatus;
use App\Models\Outbox;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:process-outbox {--batch=100}')]
#[Description('Search pending outboxes and dispatch them')]
class ProcessOutboxCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = (int) $this->option('batch');

        $pendingOutboxes = DB::transaction(function () use ($batchSize) {
            return Outbox::where('status', OutboxStatus::PENDING)
                ->oderBy('created_at', 'desc')
                ->limit($batchSize)
                ->lockForUpdate()
                ->skipLocked()
                ->get();
        });

        foreach ($pendingOutboxes as $outbox) {
            // Job
        }
    }
}
