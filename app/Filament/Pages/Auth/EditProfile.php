<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),

            TextInput::make('phone')
                ->tel()
                ->maxLength(30),

            FileUpload::make('avatar')
                ->image()
                ->disk('public')
                ->directory('avatars')
                ->visibility('public')
                ->multiple(false),

            TextInput::make('password')
                ->password()
                ->revealable()
                ->rule(Password::default())
                ->dehydrated(false),

            TextInput::make('passwordConfirmation')
                ->label('Confirm password')
                ->password()
                ->revealable()
                ->dehydrated(false)
                ->same('password'),
        ]);
    }
}
