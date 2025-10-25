<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'CRM & Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'company_name', 'first_name', 'last_name', 'email', 'phone', 'mobile'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->display_name;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'b2b' => 'B2B (Business)',
                                'b2c' => 'B2C (Individual)',
                            ])
                            ->required()
                            ->live()
                            ->default('b2b')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('code')
                            ->label('Customer Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->required(fn (Forms\Get $get) => $get('type') === 'b2b')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'b2b')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('first_name')
                            ->required(fn (Forms\Get $get) => $get('type') === 'b2c')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'b2c')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->required(fn (Forms\Get $get) => $get('type') === 'b2c')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'b2c')
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

                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->prefix('https://')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'b2b'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tax & Legal Information')
                    ->schema([
                        Forms\Components\TextInput::make('tax_id')
                            ->label('Tax ID / VAT Number')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('registration_number')
                            ->label('Company Registration Number')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'b2b'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address Information')
                    ->schema([
                        Forms\Components\Textarea::make('billing_address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('shipping_address')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Business Terms')
                    ->schema([
                        Forms\Components\TextInput::make('payment_terms')
                            ->label('Payment Terms (Days)')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->minValue(0)
                            ->maxValue(365)
                            ->suffix('days'),

                        Forms\Components\TextInput::make('credit_limit')
                            ->label('Credit Limit')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),

                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'credit_card' => 'Credit Card',
                                'check' => 'Check',
                                'other' => 'Other',
                            ])
                            ->default('bank_transfer')
                            ->required(),

                        Forms\Components\Select::make('price_list_id')
                            ->label('Price List')
                            ->relationship('priceList', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->unique()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->unique()
                                    ->maxLength(255),
                                Forms\Components\Select::make('currency')
                                    ->options([
                                        'USD' => 'USD',
                                        'EUR' => 'EUR',
                                        'GBP' => 'GBP',
                                        'HUF' => 'HUF',
                                    ])
                                    ->default('HUF')
                                    ->required(),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Categorization & Assignment')
                    ->schema([
                        Forms\Components\TextInput::make('region')
                            ->maxLength(255),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('slug')
                                    ->required(),
                            ]),

                        Forms\Components\TextInput::make('account_group')
                            ->maxLength(255),

                        Forms\Components\Select::make('assigned_sales_rep')
                            ->label('Assigned Sales Representative')
                            ->relationship('salesRep', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Notes')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Customer')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Customer')
                    ->searchable(['company_name', 'first_name', 'last_name', 'email'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'info' => 'b2b',
                        'success' => 'b2c',
                    ])
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-o-phone')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('salesRep.name')
                    ->label('Sales Rep')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('region')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'b2b' => 'B2B',
                        'b2c' => 'B2C',
                    ]),

                Tables\Filters\SelectFilter::make('assigned_sales_rep')
                    ->label('Sales Representative')
                    ->relationship('salesRep', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('region'),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('View')
                    ->slideOver()
                    ->infolist([
                        Infolists\Components\Section::make('Customer Information')
                            ->schema([
                                Infolists\Components\TextEntry::make('code')
                                    ->badge()
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                                Infolists\Components\TextEntry::make('display_name')
                                    ->label('Name')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('phone')
                                    ->icon('heroicon-o-phone'),
                                Infolists\Components\TextEntry::make('mobile')
                                    ->icon('heroicon-o-device-phone-mobile'),
                                Infolists\Components\TextEntry::make('website')
                                    ->url(fn ($record) => $record->website)
                                    ->openUrlInNewTab(),
                                Infolists\Components\TextEntry::make('tax_id')
                                    ->label('Tax ID'),
                            ])
                            ->columns(2),

                        Infolists\Components\Section::make('Address')
                            ->schema([
                                Infolists\Components\TextEntry::make('billing_address')
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('shipping_address')
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('city'),
                                Infolists\Components\TextEntry::make('country'),
                            ])
                            ->columns(2)
                            ->collapsible(),

                        Infolists\Components\Section::make('Business Terms')
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_terms')
                                    ->suffix(' days'),
                                Infolists\Components\TextEntry::make('credit_limit')
                                    ->money('USD'),
                                Infolists\Components\TextEntry::make('payment_method')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('priceList.name')
                                    ->label('Price List')
                                    ->badge(),
                            ])
                            ->columns(2),

                        Infolists\Components\Section::make('Assignment')
                            ->schema([
                                Infolists\Components\TextEntry::make('salesRep.name')
                                    ->label('Sales Representative'),
                                Infolists\Components\TextEntry::make('region')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('category.name')
                                    ->label('Category')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('account_group')
                                    ->badge(),
                            ])
                            ->columns(2),

                        Infolists\Components\Section::make('Status')
                            ->schema([
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                                Infolists\Components\TextEntry::make('notes')
                                    ->columnSpanFull(),
                            ]),
                    ]),

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
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
