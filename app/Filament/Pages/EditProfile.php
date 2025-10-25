<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.pages.edit-profile';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $profileData = [];

    public ?array $passwordData = [];

    public function mount(): void
    {
        $this->fillForms();
    }

    protected function fillForms(): void
    {
        $user = Auth::user();

        $this->profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar ? [$user->avatar] : null,
            'phone' => $user->phone,
            'mobile' => $user->mobile,
            'location' => $user->location,
            'bio' => $user->bio,
        ];

        $this->passwordData = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profile Information')
                    ->description('Update your account\'s profile information and email address.')
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('mobile')
                            ->label('Mobile')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('location')
                            ->label('Location')
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('bio')
                            ->label('Bio')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('profileData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Update Password')
                    ->description('Ensure your account is using a long, random password to stay secure.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->required()
                            ->currentPassword(),
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->confirmed()
                            ->revealable(),
                        TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required()
                            ->revealable(),
                    ]),
            ])
            ->statePath('passwordData');
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'passwordForm',
        ];
    }

    public function updateProfile(): void
    {
        $data = $this->profileForm->getState();

        $user = Auth::user();
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => is_array($data['avatar']) ? ($data['avatar'][0] ?? null) : $data['avatar'],
            'phone' => $data['phone'],
            'mobile' => $data['mobile'],
            'location' => $data['location'],
            'bio' => $data['bio'],
        ]);

        // Log profile update
        ActivityLog::log(
            'profile_updated',
            "{$user->name} updated their profile",
            $user
        );

        Notification::make()
            ->success()
            ->title('Profile updated')
            ->body('Your profile information has been updated successfully.')
            ->send();
    }

    public function updatePassword(): void
    {
        $data = $this->passwordForm->getState();

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->passwordForm->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        Notification::make()
            ->success()
            ->title('Password updated')
            ->body('Your password has been updated successfully.')
            ->send();
    }

    public function getTitle(): string
    {
        return 'Edit Profile';
    }

    public static function getNavigationLabel(): string
    {
        return 'Profile';
    }
}
