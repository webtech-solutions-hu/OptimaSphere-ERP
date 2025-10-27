<?php

namespace App\Filament\Resources\SystemNotificationResource\Pages;

use App\Filament\Resources\SystemNotificationResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSystemNotification extends ViewRecord
{
    protected static string $resource = SystemNotificationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Notification Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('title'),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'info' => 'primary',
                                'success' => 'success',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'draft' => 'gray',
                                'pending' => 'warning',
                                'scheduled' => 'info',
                                'sent' => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('body')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Target Audience')
                    ->schema([
                        Infolists\Components\TextEntry::make('target_type')
                            ->label('Send To')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'global' => 'All Users (Global)',
                                'role' => 'Specific Role',
                                'user' => 'Specific User',
                                default => $state,
                            }),
                        Infolists\Components\TextEntry::make('targetRole.name')
                            ->label('Role')
                            ->visible(fn ($record) => $record->target_type === 'role'),
                        Infolists\Components\TextEntry::make('targetUser.name')
                            ->label('User')
                            ->visible(fn ($record) => $record->target_type === 'user'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Schedule & Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('sent_at')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('createdBy.name')
                            ->label('Created By'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(4),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send')
                ->label('Send Now')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                })
                ->visible(fn () => in_array($this->record->status, ['draft', 'scheduled'])),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status !== 'sent'),

            Actions\DeleteAction::make(),
        ];
    }
}
