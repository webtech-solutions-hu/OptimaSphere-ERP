<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product Information')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\Section::make('Product Details')
                                    ->schema([
                                        Forms\Components\TextInput::make('code')
                                            ->label('Product Code')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Auto-generated'),

                                        Forms\Components\TextInput::make('sku')
                                            ->label('SKU')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Auto-generated'),

                                        Forms\Components\TextInput::make('barcode')
                                            ->label('Barcode')
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('Auto-generated if empty'),

                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'physical' => 'Physical Product',
                                                'service' => 'Service',
                                                'digital' => 'Digital Product',
                                            ])
                                            ->required()
                                            ->default('physical'),

                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state)))
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('short_description')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->columnSpanFull(),

                                        Forms\Components\RichEditor::make('description')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'undo',
                                            ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Classification')
                            ->schema([
                                Forms\Components\Section::make('Product Classification')
                                    ->schema([
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

                                        Forms\Components\Select::make('unit_id')
                                            ->label('Unit of Measure')
                                            ->relationship('unit', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Forms\Components\TextInput::make('brand')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('manufacturer')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('manufacturer_part_number')
                                            ->label('Manufacturer Part #')
                                            ->maxLength(255),

                                        Forms\Components\Select::make('primary_supplier_id')
                                            ->label('Primary Supplier')
                                            ->relationship('primarySupplier', 'company_name')
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\TagsInput::make('tags')
                                            ->placeholder('Add tags')
                                            ->helperText('e.g., featured, new, seasonal, bestseller')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->schema([
                                Forms\Components\Section::make('Price Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('base_price')
                                            ->label('Base Price')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0),

                                        Forms\Components\TextInput::make('cost_price')
                                            ->label('Cost Price')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->prefix('$')
                                            ->minValue(0),

                                        Forms\Components\TextInput::make('min_price')
                                            ->label('Minimum Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->minValue(0),

                                        Forms\Components\TextInput::make('max_price')
                                            ->label('Maximum Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->minValue(0),

                                        Forms\Components\Select::make('currency')
                                            ->options([
                                                'USD' => 'USD - US Dollar',
                                                'EUR' => 'EUR - Euro',
                                                'GBP' => 'GBP - British Pound',
                                                'HUF' => 'HUF - Hungarian Forint',
                                            ])
                                            ->default('USD')
                                            ->required(),

                                        Forms\Components\Toggle::make('is_on_sale')
                                            ->label('On Sale')
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('sale_price')
                                            ->label('Sale Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->minValue(0)
                                            ->visible(fn (Forms\Get $get) => $get('is_on_sale')),

                                        Forms\Components\DatePicker::make('sale_start_date')
                                            ->visible(fn (Forms\Get $get) => $get('is_on_sale')),

                                        Forms\Components\DatePicker::make('sale_end_date')
                                            ->visible(fn (Forms\Get $get) => $get('is_on_sale')),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Tax Information')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_taxable')
                                            ->label('Taxable')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%')
                                            ->minValue(0)
                                            ->maxValue(100),

                                        Forms\Components\TextInput::make('tax_class')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('hs_code')
                                            ->label('HS Code')
                                            ->maxLength(255)
                                            ->helperText('Harmonized System code for customs'),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Inventory')
                            ->schema([
                                Forms\Components\Section::make('Stock Management')
                                    ->schema([
                                        Forms\Components\Toggle::make('track_inventory')
                                            ->label('Track Inventory')
                                            ->default(true)
                                            ->inline(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Current Stock')
                                            ->numeric()
                                            ->default(0)
                                            ->required()
                                            ->visible(fn (Forms\Get $get) => $get('track_inventory')),

                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Stock level at which to reorder')
                                            ->visible(fn (Forms\Get $get) => $get('track_inventory')),

                                        Forms\Components\TextInput::make('reorder_quantity')
                                            ->label('Reorder Quantity')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Default quantity to order')
                                            ->visible(fn (Forms\Get $get) => $get('track_inventory')),

                                        Forms\Components\TextInput::make('max_stock_level')
                                            ->label('Maximum Stock Level')
                                            ->numeric()
                                            ->helperText('Maximum storage capacity')
                                            ->visible(fn (Forms\Get $get) => $get('track_inventory')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Physical Attributes')
                                    ->schema([
                                        Forms\Components\TextInput::make('weight')
                                            ->numeric()
                                            ->suffix('kg'),

                                        Forms\Components\TextInput::make('length')
                                            ->numeric()
                                            ->suffix('cm'),

                                        Forms\Components\TextInput::make('width')
                                            ->numeric()
                                            ->suffix('cm'),

                                        Forms\Components\TextInput::make('height')
                                            ->numeric()
                                            ->suffix('cm'),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Media')
                            ->schema([
                                Forms\Components\FileUpload::make('image')
                                    ->label('Primary Image')
                                    ->image()
                                    ->directory('products')
                                    ->imageEditor()
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('images')
                                    ->label('Additional Images')
                                    ->image()
                                    ->directory('products')
                                    ->multiple()
                                    ->reorderable()
                                    ->maxFiles(5)
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('attachments')
                                    ->label('Documents & Manuals')
                                    ->directory('products/documents')
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('SEO & Marketing')
                            ->schema([
                                Forms\Components\Section::make('Marketing Flags')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured Product')
                                            ->inline(false),

                                        Forms\Components\Toggle::make('is_new')
                                            ->label('New Product')
                                            ->inline(false),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('SEO Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('meta_description')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->columnSpanFull(),

                                        Forms\Components\TagsInput::make('meta_keywords')
                                            ->placeholder('Add keywords')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Availability')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\Toggle::make('is_available_for_purchase')
                                            ->label('Available for Purchase')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\Toggle::make('is_available_online')
                                            ->label('Available Online')
                                            ->default(true)
                                            ->inline(false),

                                        Forms\Components\DatePicker::make('available_from')
                                            ->label('Available From'),

                                        Forms\Components\DatePicker::make('available_until')
                                            ->label('Available Until'),
                                    ])
                                    ->columns(3)
                                    ->collapsible(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Notes')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
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

                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'physical',
                        'success' => 'service',
                        'warning' => 'digital',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('base_price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record): string => match (true) {
                        !$record->track_inventory => 'gray',
                        $record->isOutOfStock() => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($record) => $record->track_inventory
                        ? number_format($record->current_stock, 0)
                        : 'N/A'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'physical' => 'Physical',
                        'service' => 'Service',
                        'digital' => 'Digital',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured')
                    ->native(false),

                Tables\Filters\Filter::make('low_stock')
                    ->query(fn ($query) => $query->lowStock())
                    ->label('Low Stock'),

                Tables\Filters\Filter::make('out_of_stock')
                    ->query(fn ($query) => $query->outOfStock())
                    ->label('Out of Stock'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('View')
                    ->slideOver()
                    ->infolist([
                        Infolists\Components\Section::make('Product Information')
                            ->schema([
                                Infolists\Components\ImageEntry::make('image')
                                    ->label('')
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('code')
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('sku')
                                    ->label('SKU')
                                    ->badge()
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('barcode')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('name')
                                    ->weight('bold')
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('type')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('category.name')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('unit.name'),

                                Infolists\Components\TextEntry::make('base_price')
                                    ->money('USD'),

                                Infolists\Components\TextEntry::make('current_stock')
                                    ->formatStateUsing(fn ($record) => $record->track_inventory
                                        ? $record->current_stock . ' ' . $record->unit->symbol
                                        : 'Not tracked'),

                                Infolists\Components\TextEntry::make('stock_status')
                                    ->badge()
                                    ->color(fn ($state): string => match ($state) {
                                        'In Stock' => 'success',
                                        'Low Stock' => 'warning',
                                        'Out of Stock' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(2),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
