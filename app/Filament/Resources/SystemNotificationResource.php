<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemNotificationResource\Pages;
use App\Models\Role;
use App\Models\SystemNotification;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SystemNotificationResource extends Resource
{
    protected static ?string $model = SystemNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'OptimaSphere';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'System Notification';

    protected static ?string $pluralModelLabel = 'System Notifications';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Notification Content')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('body')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'info' => 'Info',
                                'success' => 'Success',
                                'warning' => 'Warning',
                                'danger' => 'Danger',
                            ])
                            ->required()
                            ->default('info')
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\Select::make('icon')
                            ->options([
                                'heroicon-o-bell' => 'Bell',
                                'heroicon-o-information-circle' => 'Information',
                                'heroicon-o-check-circle' => 'Check Circle',
                                'heroicon-o-exclamation-triangle' => 'Warning Triangle',
                                'heroicon-o-x-circle' => 'Error Circle',
                                'heroicon-o-megaphone' => 'Megaphone',
                                'heroicon-o-sparkles' => 'Sparkles',
                            ])
                            ->searchable()
                            ->columnSpan(1),

                        Forms\Components\Select::make('color')
                            ->options([
                                'primary' => 'Primary',
                                'info' => 'Info',
                                'success' => 'Success',
                                'warning' => 'Warning',
                                'danger' => 'Danger',
                                'gray' => 'Gray',
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Target Audience')
                    ->schema([
                        Forms\Components\Select::make('target_type')
                            ->label('Send To')
                            ->options([
                                'global' => 'All Users (Global)',
                                'role' => 'Specific Role',
                                'user' => 'Specific User',
                            ])
                            ->required()
                            ->default('global')
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => [
                                $set('target_role_id', null),
                                $set('target_id', null),
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Select::make('target_role_id')
                            ->label('Target Role')
                            ->options(Role::pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'role')
                            ->required(fn (Forms\Get $get) => $get('target_type') === 'role')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('target_id')
                            ->label('Target User')
                            ->options(User::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'user')
                            ->required(fn (Forms\Get $get) => $get('target_type') === 'user')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Send Immediately',
                                'scheduled' => 'Schedule for Later',
                            ])
                            ->required()
                            ->default('draft')
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule Date & Time')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'scheduled')
                            ->required(fn (Forms\Get $get) => $get('status') === 'scheduled')
                            ->minDate(now())
                            ->seconds(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('body')
                    ->searchable()
                    ->limit(60)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'info',
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('target_type')
                    ->label('Target')
                    ->formatStateUsing(fn ($state, $record) => match ($state) {
                        'global' => 'All Users',
                        'role' => 'Role: ' . ($record->targetRole?->name ?? 'N/A'),
                        'user' => 'User: ' . ($record->targetUser?->name ?? 'N/A'),
                        default => $state,
                    })
                    ->colors([
                        'success' => 'global',
                        'warning' => 'role',
                        'info' => 'user',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending',
                        'info' => 'scheduled',
                        'success' => 'sent',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->since(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'scheduled' => 'Scheduled',
                        'sent' => 'Sent',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'info' => 'Info',
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'danger' => 'Danger',
                    ]),

                Tables\Filters\SelectFilter::make('target_type')
                    ->label('Target')
                    ->options([
                        'global' => 'Global',
                        'role' => 'Role',
                        'user' => 'User',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'scheduled'])),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit')
                    ->visible(fn ($record) => $record->status !== 'sent'),

                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('View')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Notification Details')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('title')
                                    ->size('lg')
                                    ->weight('bold'),
                                \Filament\Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'info' => 'primary',
                                        'success' => 'success',
                                        'warning' => 'warning',
                                        'danger' => 'danger',
                                        default => 'gray',
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'draft' => 'gray',
                                        'pending' => 'warning',
                                        'scheduled' => 'info',
                                        'sent' => 'success',
                                        default => 'gray',
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('body')
                                    ->columnSpanFull()
                                    ->prose(),
                            ])
                            ->columns(3),

                        \Filament\Infolists\Components\Section::make('Target Audience')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('target_type')
                                    ->label('Send To')
                                    ->badge()
                                    ->formatStateUsing(fn ($state, $record) => match ($state) {
                                        'global' => 'All Users',
                                        'role' => 'Role: ' . ($record->targetRole?->name ?? 'N/A'),
                                        'user' => 'User: ' . ($record->targetUser?->name ?? 'N/A'),
                                        default => $state,
                                    })
                                    ->color(fn ($state) => match ($state) {
                                        'global' => 'success',
                                        'role' => 'warning',
                                        'user' => 'info',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(1),

                        \Filament\Infolists\Components\Section::make('Schedule & Status')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('scheduled_at')
                                    ->dateTime()
                                    ->placeholder('-')
                                    ->icon('heroicon-o-clock'),
                                \Filament\Infolists\Components\TextEntry::make('sent_at')
                                    ->dateTime()
                                    ->placeholder('-')
                                    ->since()
                                    ->icon('heroicon-o-paper-airplane'),
                                \Filament\Infolists\Components\TextEntry::make('createdBy.name')
                                    ->label('Created By')
                                    ->icon('heroicon-o-user'),
                                \Filament\Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-calendar'),
                            ])
                            ->columns(4),
                    ]),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Notifications')
            ->emptyStateDescription('Create your first system notification')
            ->emptyStateIcon('heroicon-o-bell-alert');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemNotifications::route('/'),
            'create' => Pages\CreateSystemNotification::route('/create'),
            'edit' => Pages\EditSystemNotification::route('/{record}/edit'),
            // Removed view page to enable slide-over modal
        ];
    }
}
