<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionResource\Pages;
use App\Models\Session;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SessionResource extends Resource
{
    protected static ?string $model = Session::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'System Resources';

    protected static ?int $navigationSort = 1;

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
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sessions are managed by the system, no form needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Session ID')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->id),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('Guest'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->searchable()
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->user_agent),
                Tables\Columns\TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => date('Y-m-d H:i:s', $record->last_activity)),
            ])
            ->defaultSort('last_activity', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('active')
                    ->label('Active Sessions')
                    ->query(fn ($query) => $query->where('last_activity', '>', now()->subMinutes(5)->timestamp))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Session Information')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('id')
                                    ->label('Session ID')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('user.name')
                                    ->label('User')
                                    ->default('Guest'),
                                \Filament\Infolists\Components\TextEntry::make('user.email')
                                    ->label('Email'),
                                \Filament\Infolists\Components\TextEntry::make('ip_address')
                                    ->label('IP Address')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('last_activity')
                                    ->label('Last Activity')
                                    ->dateTime()
                                    ->since(),
                            ])
                            ->columns(2),

                        \Filament\Infolists\Components\Section::make('User Agent Details')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('user_agent')
                                    ->label('User Agent String')
                                    ->columnSpanFull()
                                    ->copyable(),
                            ]),

                        \Filament\Infolists\Components\Section::make('Session Payload')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('payload')
                                    ->label('Session Data')
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn ($state) => base64_decode($state))
                                    ->copyable(),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ]),
                Tables\Actions\DeleteAction::make()
                    ->label('Terminate')
                    ->modalHeading('Terminate Session')
                    ->modalDescription('Are you sure you want to terminate this session? The user will be logged out immediately.')
                    ->successNotificationTitle('Session terminated'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Terminate Selected')
                        ->modalHeading('Terminate Sessions')
                        ->modalDescription('Are you sure you want to terminate the selected sessions? The users will be logged out immediately.')
                        ->successNotificationTitle('Sessions terminated'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessions::route('/'),
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
