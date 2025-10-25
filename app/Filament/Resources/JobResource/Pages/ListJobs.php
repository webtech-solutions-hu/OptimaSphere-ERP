<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use App\Models\CompletedJob;
use App\Models\Job;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('run_all_pending')
                ->label('Process All Jobs')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Process All Pending Jobs')
                ->modalDescription('This will execute all pending jobs in the queue. Are you sure you want to continue?')
                ->action(function () {
                    $initialJobCount = Job::whereNull('reserved_at')->count();

                    if ($initialJobCount === 0) {
                        Notification::make()
                            ->title('No Jobs to Process')
                            ->warning()
                            ->body('There are no pending jobs in the queue.')
                            ->send();
                        return;
                    }

                    try {
                        // Store all pending jobs data before processing
                        $pendingJobs = Job::whereNull('reserved_at')->get()->map(function ($job) {
                            return [
                                'id' => $job->id,
                                'queue' => $job->queue,
                                'payload' => $job->payload,
                                'created_at' => $job->created_at,
                            ];
                        })->keyBy('id')->toArray();

                        // Process all jobs in the queue
                        Artisan::call('queue:work', [
                            '--stop-when-empty' => true,
                        ]);

                        // Check which jobs completed successfully and move them
                        $completed = 0;
                        foreach ($pendingJobs as $jobId => $jobData) {
                            $jobStillExists = Job::find($jobId);

                            if (!$jobStillExists) {
                                // Job was processed successfully, move to completed_jobs
                                CompletedJob::create([
                                    'queue' => $jobData['queue'],
                                    'payload' => $jobData['payload'],
                                    'created_at' => $jobData['created_at'],
                                    'completed_at' => time(),
                                ]);
                                $completed++;
                            }
                        }

                        $remainingJobs = Job::count();
                        $failed = $initialJobCount - $completed;

                        $message = "Processed {$initialJobCount} job(s) from the queue.";
                        if ($completed > 0) {
                            $message .= " {$completed} moved to completed jobs.";
                        }
                        if ($failed > 0) {
                            $message .= " {$failed} may have failed (check failed jobs).";
                        }

                        Notification::make()
                            ->title('Queue Processing Complete')
                            ->success()
                            ->body($message)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Queue Processing Failed')
                            ->danger()
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn () => Job::whereNull('reserved_at')->count() > 0),
        ];
    }
}
