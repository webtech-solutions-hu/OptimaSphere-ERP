<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->approved()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supplier Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'manufacturer' => 'Manufacturer',
                                'distributor' => 'Distributor',
                                'service' => 'Service Provider',
                            ])
                            ->required()
                            ->default('manufacturer'),

                        Forms\Components\TextInput::make('code')
                            ->label('Supplier Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

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
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_person')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_title')
                            ->maxLength(255),
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
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address Information')
                    ->schema([
                        Forms\Components\Textarea::make('address')
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

                Forms\Components\Section::make('Banking Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_account_number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_swift_code')
                            ->label('SWIFT/BIC Code')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bank_iban')
                            ->label('IBAN')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Payment Terms')
                    ->schema([
                        Forms\Components\TextInput::make('payment_terms')
                            ->label('Payment Terms (Days)')
                            ->numeric()
                            ->default(30)
                            ->required()
                            ->minValue(0)
                            ->maxValue(365)
                            ->suffix('days'),

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

                        Forms\Components\TextInput::make('credit_limit')
                            ->label('Credit Limit')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->minValue(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Contract Details')
                    ->schema([
                        Forms\Components\TextInput::make('contract_number')
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('contract_start_date'),

                        Forms\Components\DatePicker::make('contract_end_date'),

                        Forms\Components\Textarea::make('contract_terms')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Categorization')
                    ->schema([
                        Forms\Components\Select::make('primary_category_id')
                            ->label('Primary Category')
                            ->relationship('primaryCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\TextInput::make('slug')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('productCategories')
                            ->label('Product Categories')
                            ->relationship('productCategories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Select multiple categories that this supplier provides')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('assigned_procurement_officer')
                            ->label('Assigned Procurement Officer')
                            ->relationship('procurementOfficer', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Performance Tracking')
                    ->schema([
                        Forms\Components\TextInput::make('performance_rating')
                            ->label('Performance Rating (0-5)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1)
                            ->suffix('/ 5'),

                        Forms\Components\TextInput::make('total_transactions')
                            ->label('Total Transactions')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('total_purchase_amount')
                            ->label('Total Purchase Amount')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DateTimePicker::make('last_transaction_date')
                            ->label('Last Transaction Date')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Status & Notes')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approved')
                            ->default(false)
                            ->inline(false)
                            ->disabled(fn (?Supplier $record) => $record && $record->is_approved),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'manufacturer',
                        'success' => 'distributor',
                        'info' => 'service',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('primaryCategory.name')
                    ->label('Primary Category')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('performance_rating')
                    ->label('Rating')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ★')
                    ->color(fn ($state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('procurementOfficer.name')
                    ->label('Procurement Officer')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Approved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_transaction_date')
                    ->label('Last Transaction')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'manufacturer' => 'Manufacturer',
                        'distributor' => 'Distributor',
                        'service' => 'Service Provider',
                    ]),

                Tables\Filters\SelectFilter::make('primary_category_id')
                    ->label('Primary Category')
                    ->relationship('primaryCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('assigned_procurement_officer')
                    ->label('Procurement Officer')
                    ->relationship('procurementOfficer', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->boolean()
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending approval')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\Filter::make('performance_rating')
                    ->form([
                        Forms\Components\Select::make('rating')
                            ->label('Minimum Rating')
                            ->options([
                                '4' => '4+ Stars (Excellent)',
                                '3' => '3+ Stars (Good)',
                                '2' => '2+ Stars (Fair)',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['rating'],
                            fn ($query, $rating) => $query->where('performance_rating', '>=', $rating)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('View')
                    ->slideOver()
                    ->infolist([
                        Infolists\Components\Section::make('Supplier Information')
                            ->schema([
                                Infolists\Components\TextEntry::make('code')
                                    ->badge()
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                                Infolists\Components\TextEntry::make('company_name')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('phone')
                                    ->icon('heroicon-o-phone'),
                                Infolists\Components\TextEntry::make('contact_person'),
                                Infolists\Components\TextEntry::make('performance_rating')
                                    ->label('Performance')
                                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ★')
                                    ->badge()
                                    ->color(fn ($state): string => match (true) {
                                        $state >= 4 => 'success',
                                        $state >= 3 => 'warning',
                                        default => 'danger',
                                    }),
                                Infolists\Components\TextEntry::make('total_transactions')
                                    ->label('Transactions')
                                    ->badge(),
                            ])
                            ->columns(2),

                        Infolists\Components\Section::make('Banking Information')
                            ->schema([
                                Infolists\Components\TextEntry::make('bank_name'),
                                Infolists\Components\TextEntry::make('bank_account_number')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('bank_swift_code')
                                    ->label('SWIFT/BIC'),
                                Infolists\Components\TextEntry::make('bank_iban')
                                    ->label('IBAN')
                                    ->copyable(),
                            ])
                            ->columns(2)
                            ->collapsible(),

                        Infolists\Components\Section::make('Contract')
                            ->schema([
                                Infolists\Components\TextEntry::make('contract_number'),
                                Infolists\Components\TextEntry::make('contract_start_date')
                                    ->date(),
                                Infolists\Components\TextEntry::make('contract_end_date')
                                    ->date(),
                                Infolists\Components\TextEntry::make('contract_terms')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->collapsed(),

                        Infolists\Components\Section::make('Status')
                            ->schema([
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                                Infolists\Components\IconEntry::make('is_approved')
                                    ->label('Approved')
                                    ->boolean(),
                                Infolists\Components\TextEntry::make('approver.name')
                                    ->label('Approved By'),
                                Infolists\Components\TextEntry::make('approved_at')
                                    ->dateTime(config('datetime.format')),
                            ])
                            ->columns(2),
                    ]),

                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->iconButton()
                    ->tooltip('Approve Supplier')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Supplier')
                    ->modalDescription(fn ($record) => "Are you sure you want to approve {$record->company_name}?")
                    ->visible(fn ($record) => !$record->is_approved && Auth::user()?->canApproveUsers())
                    ->action(function ($record) {
                        $record->update([
                            'is_approved' => true,
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);

                        ActivityLog::log(
                            'supplier_approved',
                            "{$record->company_name} was approved by " . Auth::user()->name,
                            Auth::user(),
                            ['supplier_code' => $record->code, 'supplier_id' => $record->id]
                        );

                        Notification::make()
                            ->success()
                            ->title('Supplier Approved')
                            ->body("{$record->company_name} has been approved successfully.")
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
