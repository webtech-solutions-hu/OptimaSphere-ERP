<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\UserApprovedNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::whereNull('approved_at')->count();
        if ($pendingCount > 0) {
            return (string) $pendingCount;
        }
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::whereNull('approved_at')->count();
        return $pendingCount > 0 ? 'warning' : 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $pendingCount = static::getModel()::whereNull('approved_at')->count();
        if ($pendingCount > 0) {
            return "{$pendingCount} user(s) pending approval";
        }
        $totalCount = static::getModel()::count();
        return "{$totalCount} total user(s)";
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mobile')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('bio')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->revealable()
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('User must be active to login.')
                            ->default(false),
                        Forms\Components\Placeholder::make('approved_at')
                            ->label('Approved At')
                            ->content(fn ($record) => $record?->approved_at?->format('M d, Y H:i:s') ?? 'Not approved')
                            ->visible(fn ($context) => $context === 'edit'),
                        Forms\Components\Placeholder::make('approved_by_name')
                            ->label('Approved By')
                            ->content(fn ($record) => $record?->approvedBy?->name ?? 'Not approved')
                            ->visible(fn ($context) => $context === 'edit'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Roles')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->label('User Roles')
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('approved_at')
                    ->label('Approved')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable()
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('approved_at')
                    ->label('Approved')
                    ->nullable()
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending approval only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('View')
                    ->slideOver()
                    ->infolist([
                        Infolists\Components\Section::make('User Information')
                            ->schema([
                                Infolists\Components\ImageEntry::make('avatar')
                                    ->label('Avatar')
                                    ->disk('public')
                                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                                    ->circular()
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('name')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('phone')
                                    ->icon('heroicon-o-phone')
                                    ->default('N/A'),
                                Infolists\Components\TextEntry::make('mobile')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->default('N/A'),
                                Infolists\Components\TextEntry::make('location')
                                    ->icon('heroicon-o-map-pin')
                                    ->default('N/A'),
                                Infolists\Components\TextEntry::make('bio')
                                    ->columnSpanFull()
                                    ->default('N/A'),
                            ])
                            ->columns(2),

                        Infolists\Components\Section::make('Status')
                            ->schema([
                                Infolists\Components\IconEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                                Infolists\Components\IconEntry::make('approved_at')
                                    ->label('Approved')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                                Infolists\Components\TextEntry::make('approvedBy.name')
                                    ->label('Approved By')
                                    ->default('N/A'),
                            ])
                            ->columns(4),

                        Infolists\Components\Section::make('Roles')
                            ->schema([
                                Infolists\Components\TextEntry::make('roles.name')
                                    ->label('Assigned Roles')
                                    ->badge()
                                    ->color('primary')
                                    ->default('No roles assigned'),
                            ]),

                        Infolists\Components\Section::make('Timestamps')
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->dateTime(config('datetime.format'))
                                    ->since(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->dateTime(config('datetime.format'))
                                    ->since(),
                                Infolists\Components\TextEntry::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->dateTime(config('datetime.format'))
                                    ->default('Not verified'),
                                Infolists\Components\TextEntry::make('approved_at')
                                    ->label('Approved At')
                                    ->dateTime(config('datetime.format'))
                                    ->default('Not approved'),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->collapsed(),
                    ]),
                Tables\Actions\Action::make('verify_email')
                    ->icon('heroicon-o-envelope-open')
                    ->iconButton()
                    ->tooltip('Verify Email')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Verify Email')
                    ->modalDescription(fn ($record) => "Mark {$record->email} as verified?")
                    ->visible(fn ($record) => !$record->isEmailVerified() && Auth::user()?->canApproveUsers())
                    ->action(function ($record) {
                        $record->update([
                            'email_verified_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Email verified')
                            ->body("{$record->email} has been marked as verified.")
                            ->send();
                    }),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->iconButton()
                    ->tooltip('Approve User')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve User')
                    ->modalDescription(fn ($record) => "Are you sure you want to approve {$record->name}?")
                    ->visible(fn ($record) => !$record->isApproved() && Auth::user()?->canApproveUsers())
                    ->action(function ($record) {
                        $record->update([
                            'approved_at' => now(),
                            'approved_by' => Auth::id(),
                        ]);

                        // Log the approval
                        ActivityLog::log(
                            'approved',
                            "{$record->name} was approved by " . Auth::user()->name,
                            $record,
                            ['approved_by' => Auth::user()->name]
                        );

                        // Send approval notification to the user
                        $record->notify(new UserApprovedNotification(Auth::user()));

                        Notification::make()
                            ->success()
                            ->title('User approved')
                            ->body("{$record->name} has been approved successfully and notified via email.")
                            ->send();
                    }),
                Tables\Actions\Action::make('revoke_approval')
                    ->icon('heroicon-o-x-circle')
                    ->iconButton()
                    ->tooltip('Revoke Approval')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke Approval')
                    ->modalDescription(fn ($record) => "Are you sure you want to revoke approval for {$record->name}?")
                    ->visible(fn ($record) => $record->isApproved() && Auth::user()?->canApproveUsers())
                    ->action(function ($record) {
                        $record->update([
                            'approved_at' => null,
                            'approved_by' => null,
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Approval revoked')
                            ->body("{$record->name}'s approval has been revoked.")
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
