<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompletedJobResource\Pages;
use App\Models\CompletedJob;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompletedJobResource extends Resource
{
    protected static ?string $model = CompletedJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Completed Jobs';

    protected static ?string $modelLabel = 'Completed Job';

    protected static ?string $pluralModelLabel = 'Completed Jobs';

    protected static ?string $navigationGroup = 'System Resources';

    protected static ?int $navigationSort = 3;

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
        return 'success';
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
                    ->color('success')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_name')
                    ->label('Job')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->job_name),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : 'N/A')
                    ->sortable()
                    ->tooltip('Time taken to complete the job'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Started')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => date('Y-m-d H:i:s', $record->created_at)),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => date('Y-m-d H:i:s', $record->completed_at)),
            ])
            ->defaultSort('completed_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('queue')
                    ->options(fn () => CompletedJob::query()->distinct()->pluck('queue', 'queue')->toArray())
                    ->searchable(),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->where('completed_at', '>=', now()->startOfDay()->timestamp))
                    ->toggle(),
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->where('completed_at', '>=', now()->startOfWeek()->timestamp))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Job Information')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('id')
                                    ->label('Job ID'),
                                \Filament\Infolists\Components\TextEntry::make('queue')
                                    ->badge()
                                    ->color('success'),
                                \Filament\Infolists\Components\TextEntry::make('job_name')
                                    ->label('Job Name'),
                                \Filament\Infolists\Components\TextEntry::make('duration')
                                    ->label('Duration')
                                    ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : 'N/A'),
                                \Filament\Infolists\Components\TextEntry::make('created_at')
                                    ->label('Started At')
                                    ->dateTime(config('datetime.format'))
                                    ->since(),
                                \Filament\Infolists\Components\TextEntry::make('completed_at')
                                    ->label('Completed At')
                                    ->dateTime(config('datetime.format'))
                                    ->since(),
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
                    ->label('Delete')
                    ->modalHeading('Delete Completed Job')
                    ->modalDescription('Are you sure you want to delete this completed job record?')
                    ->successNotificationTitle('Completed job deleted'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->modalHeading('Delete Completed Jobs')
                        ->modalDescription('Are you sure you want to delete the selected completed job records?')
                        ->successNotificationTitle('Completed jobs deleted'),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('clear_old')
                    ->label('Clear Old Records')
                    ->icon('heroicon-o-trash')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('days')
                            ->label('Delete records older than')
                            ->options([
                                7 => '7 days',
                                30 => '30 days',
                                60 => '60 days',
                                90 => '90 days',
                            ])
                            ->default(30)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $timestamp = now()->subDays($data['days'])->timestamp;
                        $count = CompletedJob::where('completed_at', '<', $timestamp)->delete();

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Old records cleared')
                            ->body($count . ' completed job record(s) deleted.')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompletedJobs::route('/'),
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
