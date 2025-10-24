<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FailedJobResource\Pages;
use App\Models\FailedJob;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Failed Jobs';

    protected static ?string $modelLabel = 'Failed Job';

    protected static ?string $pluralModelLabel = 'Failed Jobs';

    protected static ?string $navigationGroup = 'System Resources';

    protected static ?int $navigationSort = 4;

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
        return $count > 0 ? 'danger' : 'gray';
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
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->uuid)
                    ->copyable(),
                Tables\Columns\TextColumn::make('queue')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_name')
                    ->label('Job')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->job_name),
                Tables\Columns\TextColumn::make('exception_message')
                    ->label('Exception')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->exception_message)
                    ->color('danger'),
                Tables\Columns\TextColumn::make('failed_at')
                    ->label('Failed At')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->failed_at->format('Y-m-d H:i:s')),
            ])
            ->defaultSort('failed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('queue')
                    ->options(fn () => FailedJob::query()->distinct()->pluck('queue', 'queue')->toArray())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Job Information')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('id')
                                    ->label('Job ID'),
                                \Filament\Infolists\Components\TextEntry::make('uuid')
                                    ->label('UUID')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('connection')
                                    ->badge(),
                                \Filament\Infolists\Components\TextEntry::make('queue')
                                    ->badge(),
                                \Filament\Infolists\Components\TextEntry::make('job_name')
                                    ->label('Job Name'),
                                \Filament\Infolists\Components\TextEntry::make('failed_at')
                                    ->label('Failed At')
                                    ->dateTime(config('datetime.format'))
                                    ->since(),
                            ])
                            ->columns(2),

                        \Filament\Infolists\Components\Section::make('Exception Details')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('exception')
                                    ->label('Full Exception')
                                    ->columnSpanFull()
                                    ->copyable()
                                    ->formatStateUsing(fn ($state) => $state)
                                    ->html()
                                    ->prose(),
                            ])
                            ->collapsible(),

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
                Tables\Actions\Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Retry Failed Job')
                    ->modalDescription('Are you sure you want to retry this failed job?')
                    ->action(function (FailedJob $record) {
                        Artisan::call('queue:retry', ['id' => [$record->uuid]]);

                        Notification::make()
                            ->success()
                            ->title('Job queued for retry')
                            ->body('The job has been added back to the queue.')
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->modalHeading('Delete Failed Job')
                    ->modalDescription('Are you sure you want to permanently delete this failed job record?')
                    ->successNotificationTitle('Failed job deleted'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('retry')
                        ->label('Retry Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Retry Failed Jobs')
                        ->modalDescription('Are you sure you want to retry the selected failed jobs?')
                        ->action(function ($records) {
                            $uuids = $records->pluck('uuid')->toArray();
                            Artisan::call('queue:retry', ['id' => $uuids]);

                            Notification::make()
                                ->success()
                                ->title('Jobs queued for retry')
                                ->body(count($uuids) . ' job(s) have been added back to the queue.')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->modalHeading('Delete Failed Jobs')
                        ->modalDescription('Are you sure you want to permanently delete the selected failed job records?')
                        ->successNotificationTitle('Failed jobs deleted'),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('retry_all')
                    ->label('Retry All')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Retry All Failed Jobs')
                    ->modalDescription('Are you sure you want to retry all failed jobs?')
                    ->action(function () {
                        Artisan::call('queue:retry', ['id' => ['all']]);

                        Notification::make()
                            ->success()
                            ->title('All failed jobs queued for retry')
                            ->send();
                    }),
                Tables\Actions\Action::make('flush')
                    ->label('Flush All')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Flush All Failed Jobs')
                    ->modalDescription('Are you sure you want to permanently delete all failed job records? This action cannot be undone.')
                    ->action(function () {
                        Artisan::call('queue:flush');

                        Notification::make()
                            ->success()
                            ->title('All failed jobs deleted')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFailedJobs::route('/'),
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
