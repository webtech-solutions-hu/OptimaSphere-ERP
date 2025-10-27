<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConfigurationResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'OptimaSphere';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Configuration';

    protected static ?string $modelLabel = 'Setting';

    protected static ?string $pluralModelLabel = 'Settings';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Information')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for this setting (e.g., app.name, system.timezone)')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Human-readable label for this setting')
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->options([
                                'string' => 'String',
                                'text' => 'Text (Long)',
                                'boolean' => 'Boolean (Yes/No)',
                                'number' => 'Number',
                                'json' => 'JSON',
                            ])
                            ->required()
                            ->default('string')
                            ->reactive()
                            ->columnSpan(1),

                        Forms\Components\Select::make('group')
                            ->options([
                                'general' => 'General',
                                'company' => 'Company',
                                'system' => 'System',
                                'appearance' => 'Appearance',
                                'email' => 'Email',
                                'notification' => 'Notification',
                                'security' => 'Security',
                                'api' => 'API',
                                'integration' => 'Integration',
                            ])
                            ->required()
                            ->default('general')
                            ->searchable()
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Value')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['string', 'number']))
                            ->required(fn (Forms\Get $get) => in_array($get('type'), ['string', 'number']))
                            ->dehydrated(fn (Forms\Get $get) => in_array($get('type'), ['string', 'number']))
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && !in_array($record->type, ['string', 'number'])) {
                                    $component->state(null);
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['text', 'json']))
                            ->required(fn (Forms\Get $get) => in_array($get('type'), ['text', 'json']))
                            ->dehydrated(fn (Forms\Get $get) => in_array($get('type'), ['text', 'json']))
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && !in_array($record->type, ['text', 'json'])) {
                                    $component->state(null);
                                }
                            })
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'boolean')
                            ->dehydrated(fn (Forms\Get $get) => $get('type') === 'boolean')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && $record->type !== 'boolean') {
                                    $component->state(null);
                                } else {
                                    // Properly convert stored value to boolean
                                    $component->state($state === '1' || $state === 'true' || $state === true || $state === 1);
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->helperText('Optional description to explain what this setting does')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Setting')
                            ->helperText('Can this setting be accessed without authentication?')
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'system' => 'info',
                        'appearance' => 'success',
                        'email' => 'warning',
                        'notification' => 'primary',
                        'security' => 'danger',
                        'api' => 'indigo',
                        'integration' => 'purple',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->limit(50)
                    ->wrap()
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'boolean') {
                            return $state ? 'Yes' : 'No';
                        }
                        if ($record->type === 'json') {
                            return 'JSON Data';
                        }
                        return $state;
                    }),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'General',
                        'company' => 'Company',
                        'system' => 'System',
                        'appearance' => 'Appearance',
                        'email' => 'Email',
                        'notification' => 'Notification',
                        'security' => 'Security',
                        'api' => 'API',
                        'integration' => 'Integration',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'string' => 'String',
                        'text' => 'Text',
                        'boolean' => 'Boolean',
                        'number' => 'Number',
                        'json' => 'JSON',
                    ]),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public Settings'),
            ])
            ->actions([
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
            ])
            ->emptyStateHeading('No Settings')
            ->emptyStateDescription('Create your first application setting to get started')
            ->emptyStateIcon('heroicon-o-cog-6-tooth');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfigurations::route('/'),
            'create' => Pages\CreateConfiguration::route('/create'),
            'edit' => Pages\EditConfiguration::route('/{record}/edit'),
        ];
    }
}
