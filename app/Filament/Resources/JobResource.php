<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\CompletedJob;
use App\Models\Job;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Queue Jobs';

    protected static ?string $modelLabel = 'Queue Job';

    protected static ?string $pluralModelLabel = 'Queue Jobs';

    protected static ?string $navigationGroup = 'System Resources';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Auth::user()?->canAccessSystemResources() ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count > 0 ? 'warning' : 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('queue')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_name')
                    ->label('Job')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->job_name),
                Tables\Columns\TextColumn::make('attempts')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === 0 => 'gray',
                        $state <= 2 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => date('Y-m-d H:i:s', $record->created_at)),
                Tables\Columns\TextColumn::make('available_at')
                    ->label('Available')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since()
                    ->toggleable()
                    ->description(fn ($record) => date('Y-m-d H:i:s', $record->available_at)),
                Tables\Columns\TextColumn::make('reserved_at')
                    ->label('Reserved')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not reserved'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('queue')
                    ->options(fn () => Job::query()->distinct()->pluck('queue', 'queue')->toArray())
                    ->searchable(),
                Tables\Filters\Filter::make('reserved')
                    ->label('Reserved Jobs')
                    ->query(fn ($query) => $query->whereNotNull('reserved_at'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('run_now')
                        ->label('Run Now')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Run Job Now')
                        ->modalDescription('Are you sure you want to execute this job immediately? This will process the job outside the normal queue.')
                        ->action(function ($record) {
                            try {
                                // Store job data before execution
                                $jobData = [
                                    'queue' => $record->queue,
                                    'payload' => $record->payload,
                                    'created_at' => $record->created_at,
                                ];

                                $startTime = time();

                                // Run the queue worker for just this job
                                $exitCode = Artisan::call('queue:work', [
                                    '--once' => true,
                                    '--queue' => $record->queue,
                                ]);

                                // Check if job was processed successfully (removed from queue)
                                $jobStillExists = Job::find($record->id);

                                if (!$jobStillExists) {
                                    // Job completed successfully, move to completed_jobs
                                    CompletedJob::create([
                                        'queue' => $jobData['queue'],
                                        'payload' => $jobData['payload'],
                                        'created_at' => $jobData['created_at'],
                                        'completed_at' => time(),
                                    ]);

                                    Notification::make()
                                        ->title('Job Executed Successfully')
                                        ->success()
                                        ->body('The job has been processed and moved to completed jobs.')
                                        ->send();
                                } else {
                                    // Job still exists, might have failed or been reserved by another process
                                    Notification::make()
                                        ->title('Job Processing Issue')
                                        ->warning()
                                        ->body('The job may have failed or is being processed. Check failed jobs if needed.')
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Job Execution Failed')
                                    ->danger()
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();
                            }
                        })
                        ->visible(fn ($record) => $record->reserved_at === null),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->infolist([
                            \Filament\Infolists\Components\Section::make('Job Information')
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('id')
                                        ->label('Job ID'),
                                    \Filament\Infolists\Components\TextEntry::make('queue')
                                        ->badge(),
                                    \Filament\Infolists\Components\TextEntry::make('job_name')
                                        ->label('Job Name'),
                                    \Filament\Infolists\Components\TextEntry::make('attempts')
                                        ->badge(),
                                    \Filament\Infolists\Components\TextEntry::make('created_at')
                                        ->label('Created At')
                                        ->dateTime(config('datetime.format'))
                                        ->since(),
                                    \Filament\Infolists\Components\TextEntry::make('available_at')
                                        ->label('Available At')
                                        ->dateTime(config('datetime.format'))
                                        ->since(),
                                    \Filament\Infolists\Components\TextEntry::make('reserved_at')
                                        ->label('Reserved At')
                                        ->dateTime(config('datetime.format'))
                                        ->placeholder('Not reserved'),
                                ])
                                ->columns(2),

                            \Filament\Infolists\Components\Section::make('Job Payload')
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('payload')
                                        ->label('Full Payload')
                                        ->columnSpanFull()
                                        ->formatStateUsing(fn ($state) => json_encode(json_decode($state), JSON_PRETTY_PRINT))
                                        ->copyable(),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ]),

                    Tables\Actions\DeleteAction::make()
                        ->label('Remove')
                        ->modalHeading('Remove Job')
                        ->modalDescription('Are you sure you want to remove this job from the queue?')
                        ->successNotificationTitle('Job removed'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('run_now_bulk')
                        ->label('Run Selected Jobs')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Run Selected Jobs')
                        ->modalDescription('Are you sure you want to execute all selected jobs immediately?')
                        ->action(function ($records) {
                            $processed = 0;
                            $failed = 0;
                            $completed = 0;

                            foreach ($records as $record) {
                                if ($record->reserved_at !== null) {
                                    continue; // Skip reserved jobs
                                }

                                try {
                                    // Store job data before execution
                                    $jobData = [
                                        'queue' => $record->queue,
                                        'payload' => $record->payload,
                                        'created_at' => $record->created_at,
                                    ];

                                    // Run the queue worker
                                    Artisan::call('queue:work', [
                                        '--once' => true,
                                        '--queue' => $record->queue,
                                    ]);

                                    // Check if job was processed successfully
                                    $jobStillExists = Job::find($record->id);

                                    if (!$jobStillExists) {
                                        // Job completed successfully, move to completed_jobs
                                        CompletedJob::create([
                                            'queue' => $jobData['queue'],
                                            'payload' => $jobData['payload'],
                                            'created_at' => $jobData['created_at'],
                                            'completed_at' => time(),
                                        ]);
                                        $completed++;
                                    }

                                    $processed++;
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            if ($processed > 0) {
                                $message = "{$processed} job(s) processed.";
                                if ($completed > 0) {
                                    $message .= " {$completed} moved to completed jobs.";
                                }
                                if ($failed > 0) {
                                    $message .= " {$failed} job(s) failed.";
                                }

                                Notification::make()
                                    ->title('Jobs Executed')
                                    ->success()
                                    ->body($message)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No Jobs Executed')
                                    ->warning()
                                    ->body('No jobs were available to execute.')
                                    ->send();
                            }
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Remove Selected')
                        ->modalHeading('Remove Jobs')
                        ->modalDescription('Are you sure you want to remove the selected jobs from the queue?')
                        ->successNotificationTitle('Jobs removed'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
