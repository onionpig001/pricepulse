<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('vendor'),
                TextInput::make('homepage_url')
                    ->url()
                    ->required(),
                TextInput::make('pricing_url')
                    ->url()
                    ->required(),
                TextInput::make('logo_url')
                    ->url(),
                TextInput::make('affiliate_url')
                    ->url(),
                Textarea::make('tagline')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('last_scraped_at'),
            ]);
    }
}
