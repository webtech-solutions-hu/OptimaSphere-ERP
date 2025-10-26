<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockAdjustmentResource\Pages;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Procurement & Inventory';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::pending()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Adjustment Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('draft')
                            ->disabled(fn ($record) => $record !== null)
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Warehouse')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if (!$state) {
                                    return;
                                }

                                $product = Product::find($state);
                                if ($product && $product->cost_price) {
                                    $set('unit_cost', $product->cost_price);
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->options([
                                'increase' => 'Increase Stock',
                                'decrease' => 'Decrease Stock',
                            ])
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\Select::make('reason')
                            ->options([
                                'damaged' => 'Damaged',
                                'expired' => 'Expired',
                                'lost' => 'Lost',
                                'found' => 'Found',
                                'theft' => 'Theft',
                                'audit_correction' => 'Audit Correction',
                                'quality_issue' => 'Quality Issue',
                                'returned' => 'Returned',
                                'sample' => 'Sample',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Quantity & Cost')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $quantity = floatval($state ?? 0);
                                $unitCost = floatval($get('unit_cost') ?? 0);
                                $set('total_cost', $quantity * $unitCost);
                            })
                            ->suffix(fn (Forms\Get $get) => $get('product_id') ? Product::find($get('product_id'))?->unit?->symbol : '')
                            ->helperText('Enter quantity (always positive, type determines if it\'s added or removed)'),

                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Unit Cost')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $quantity = floatval($get('quantity') ?? 0);
                                $unitCost = floatval($state ?? 0);
                                $set('total_cost', $quantity * $unitCost);
                            })
                            ->nullable(),

                        Forms\Components\TextInput::make('total_cost')
                            ->label('Total Cost')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Automatically calculated: Quantity × Unit Cost'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('adjustment_date')
                            ->label('Adjustment Date')
                            ->default(now())
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('attachments')
                            ->label('Supporting Documents')
                            ->multiple()
                            ->disk('public')
                            ->directory('adjustments')
                            ->helperText('Upload photos, reports, or other supporting documents')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Approval Information')
                    ->schema([
                        Forms\Components\Placeholder::make('requested_by_name')
                            ->label('Requested By')
                            ->content(fn ($record) => $record?->requestedBy?->name ?? Auth::user()->name),

                        Forms\Components\Placeholder::make('approved_by_name')
                            ->label('Approved By')
                            ->content(fn ($record) => $record?->approvedBy?->name ?? '-'),

                        Forms\Components\Placeholder::make('approved_at')
                            ->label('Approved At')
                            ->content(fn ($record) => $record?->approved_at?->format('Y-m-d H:i:s') ?? '-'),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(2)
                            ->disabled()
                            ->visible(fn ($record) => $record?->status === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'increase' => 'success',
                        'decrease' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('quantity')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('adjustment_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse')
                    ->relationship('warehouse', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'increase' => 'Increase',
                        'decrease' => 'Decrease',
                    ]),

                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'damaged' => 'Damaged',
                        'expired' => 'Expired',
                        'lost' => 'Lost',
                        'found' => 'Found',
                        'theft' => 'Theft',
                        'audit_correction' => 'Audit Correction',
                        'quality_issue' => 'Quality Issue',
                        'returned' => 'Returned',
                        'sample' => 'Sample',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'rejected'])),

                    Tables\Actions\Action::make('submit_for_approval')
                        ->label('Submit for Approval')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->status = 'pending_approval';
                            $record->save();
                        })
                        ->visible(fn ($record) => $record->status === 'draft'),

                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->approve(Auth::user());
                        })
                        ->visible(fn ($record) => $record->status === 'pending_approval'),

                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            $record->reject(Auth::user(), $data['rejection_reason']);
                        })
                        ->visible(fn ($record) => $record->status === 'pending_approval'),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'rejected'])),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Adjustment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Reference Number'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'pending_approval' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('adjustment_date')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('warehouse.name')
                            ->label('Warehouse'),
                        Infolists\Components\TextEntry::make('product.name')
                            ->label('Product'),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'increase' => 'success',
                                'decrease' => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('reason')
                            ->badge(),
                        Infolists\Components\TextEntry::make('quantity')
                            ->numeric(2),
                        Infolists\Components\TextEntry::make('unit_cost')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('total_cost')
                            ->money('USD'),
                        Infolists\Components\TextEntry::make('balance_before')
                            ->label('Balance Before')
                            ->numeric(2),
                        Infolists\Components\TextEntry::make('balance_after')
                            ->label('Balance After')
                            ->numeric(2),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Approval Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('requestedBy.name')
                            ->label('Requested By'),
                        Infolists\Components\TextEntry::make('approvedBy.name')
                            ->label('Approved By')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->status === 'rejected'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'view' => Pages\ViewStockAdjustment::route('/{record}'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
