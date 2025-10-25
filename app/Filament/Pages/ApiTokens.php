<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;

class ApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.pages.api-tokens';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 25;

    protected static ?string $title = 'API Tokens';

    protected static ?string $navigationLabel = 'API Tokens';

    public ?string $newToken = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(Auth::user()->tokens()->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Token Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('abilities')
                    ->label('Abilities')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'All';
                        }

                        // Decode JSON if it's a string
                        $abilities = is_string($state) ? json_decode($state, true) : $state;

                        // Check if it's the wildcard permission
                        if ($abilities === ['*']) {
                            return 'All';
                        }

                        return is_array($abilities) ? implode(', ', $abilities) : 'All';
                    })
                    ->color('info'),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->since(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime(config('datetime.format'))
                    ->sortable()
                    ->placeholder('Never')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API Token')
                    ->modalDescription('Are you sure you want to revoke this API token? This action cannot be undone.')
                    ->successNotificationTitle('Token revoked successfully'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('Create New Token')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Token Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Give your token a descriptive name'),

                        Forms\Components\Select::make('abilities')
                            ->label('Abilities')
                            ->multiple()
                            ->options([
                                'products:read' => 'Products: Read',
                                'products:write' => 'Products: Write',
                                'customers:read' => 'Customers: Read',
                                'customers:write' => 'Customers: Write',
                                'suppliers:read' => 'Suppliers: Read',
                                'suppliers:write' => 'Suppliers: Write',
                                'categories:read' => 'Categories: Read',
                                'categories:write' => 'Categories: Write',
                                'units:read' => 'Units: Read',
                                'units:write' => 'Units: Write',
                            ])
                            ->helperText('Select specific abilities or leave empty for full access'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->nullable()
                            ->helperText('Leave empty for no expiration'),
                    ])
                    ->action(function (array $data) {
                        $token = Auth::user()->createToken(
                            $data['name'],
                            $data['abilities'] ?? ['*'],
                            $data['expires_at'] ?? null
                        );

                        $this->newToken = $token->plainTextToken;

                        Notification::make()
                            ->title('API Token Created')
                            ->success()
                            ->body('Make sure to copy your token now. You won\'t be able to see it again!')
                            ->send();
                    })
                    ->modalWidth('md'),
            ])
            ->emptyStateHeading('No API Tokens')
            ->emptyStateDescription('Create an API token to access the API programmatically')
            ->emptyStateIcon('heroicon-o-key');
    }

    public function getNewTokenProperty(): ?string
    {
        return $this->newToken;
    }
}
