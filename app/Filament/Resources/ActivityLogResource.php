<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 0 ? (string) $todayCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 0 ? "{$todayCount} activity log(s) today" : null;
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('System'),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'logout' => 'gray',
                        'email_confirmed' => 'info',
                        'approved' => 'success',
                        'password_reset_requested' => 'warning',
                        'password_changed' => 'warning',
                        'profile_updated' => 'info',
                        'role_changed' => 'warning',
                        'account_activated' => 'success',
                        'registration' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format(config('datetime.format'))),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'registration' => 'Registration',
                        'email_confirmed' => 'Email Confirmed',
                        'approved' => 'Approved',
                        'password_reset_requested' => 'Password Reset Requested',
                        'password_changed' => 'Password Changed',
                        'profile_updated' => 'Profile Updated',
                        'role_changed' => 'Role Changed',
                        'account_activated' => 'Account Activated',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Activity Details')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('id')
                                    ->label('Log ID'),
                                \Filament\Infolists\Components\TextEntry::make('user.name')
                                    ->label('User')
                                    ->default('System'),
                                \Filament\Infolists\Components\TextEntry::make('user.email')
                                    ->label('Email')
                                    ->default('N/A'),
                                \Filament\Infolists\Components\TextEntry::make('event')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'login' => 'success',
                                        'logout' => 'gray',
                                        'email_confirmed' => 'info',
                                        'approved' => 'success',
                                        'password_reset_requested' => 'warning',
                                        'password_changed' => 'warning',
                                        'profile_updated' => 'info',
                                        'role_changed' => 'warning',
                                        'account_activated' => 'success',
                                        'registration' => 'info',
                                        default => 'gray',
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('description')
                                    ->columnSpanFull(),
                                \Filament\Infolists\Components\TextEntry::make('ip_address')
                                    ->label('IP Address')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('created_at')
                                    ->label('Timestamp')
                                    ->dateTime(config('datetime.format')),
                            ])
                            ->columns(2),

                        \Filament\Infolists\Components\Section::make('User Agent')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('user_agent')
                                    ->columnSpanFull()
                                    ->copyable(),
                            ])
                            ->collapsible(),

                        \Filament\Infolists\Components\Section::make('Additional Properties')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('formatted_properties')
                                    ->label('Details')
                                    ->columnSpanFull()
                                    ->html()
                                    ->state(function ($record) {
                                        if (empty($record->properties)) {
                                            return '<span class="text-gray-500">No additional data</span>';
                                        }
                                        $json = json_encode($record->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                        return '<pre class="text-xs overflow-auto p-2 bg-gray-50 rounded">' . htmlspecialchars($json) . '</pre>';
                                    }),
                            ])
                            ->collapsible()
                            ->collapsed()
                            ->visible(fn ($record) => !empty($record->properties)),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
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
