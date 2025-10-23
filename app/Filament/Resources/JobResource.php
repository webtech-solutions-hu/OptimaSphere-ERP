<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => date('Y-m-d H:i:s', $record->created_at)),
                Tables\Columns\TextColumn::make('available_at')
                    ->label('Available')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable()
                    ->description(fn ($record) => date('Y-m-d H:i:s', $record->available_at)),
                Tables\Columns\TextColumn::make('reserved_at')
                    ->label('Reserved')
                    ->dateTime()
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
                                    ->dateTime()
                                    ->since(),
                                \Filament\Infolists\Components\TextEntry::make('available_at')
                                    ->label('Available At')
                                    ->dateTime()
                                    ->since(),
                                \Filament\Infolists\Components\TextEntry::make('reserved_at')
                                    ->label('Reserved At')
                                    ->dateTime()
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
