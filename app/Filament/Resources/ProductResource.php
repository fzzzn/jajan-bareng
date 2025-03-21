<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Forms\Form $form): Forms\Form
    {
        $user = Filament::auth()->user();
        $isSuperAdmin = $user->roles->contains('name', 'super_admin');

        $formSchema = [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->nullable()
                ->required(),
            Forms\Components\FileUpload::make('image')
                ->required()
                ->image(),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->required()
                ->prefix('IDR')
                ->inputMode('decimal'),
            Forms\Components\DatePicker::make('available_date')
                ->required(),
            Forms\Components\TextInput::make('stock')
                ->required()
                ->numeric()
                ->default(0),
        ];

        // Insert the appropriate organization field at the beginning based on user role
        if ($isSuperAdmin) {
            array_unshift($formSchema, 
                Forms\Components\Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required()
            );
        } else {
            array_unshift($formSchema, 
                Forms\Components\Hidden::make('organization_id')
                    ->default($user->organization_id)
            );
        }

        return $form->schema($formSchema);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        $user = Filament::auth()->user();

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image'),
                Tables\Columns\TextColumn::make('organization.name')
                    ->sortable()
                    ->visible(fn() => $user->roles->contains('name', 'super_admin')),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('price')->money('IDR', true),
                Tables\Columns\TextColumn::make('available_date')->date(),
                Tables\Columns\TextColumn::make('stock')->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->filters([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if ($user->roles->contains('name', 'organization_admin')) {
            return $query->where('organization_id', $user->organization_id);
        }

        return $query;
    }

    public static function canView($record): bool
    {
        $user = Filament::auth()->user();

        return $user->roles->contains('name', 'super_admin') ||
            ($user->roles->contains('name', 'organization_admin') &&
                $record->organization_id == $user->organization_id);
    }

    public static function canEdit($record): bool
    {
        $user = Filament::auth()->user();

        return $user->roles->contains('name', 'super_admin') ||
            ($user->roles->contains('name', 'organization_admin') &&
                $record->organization_id == $user->organization_id);
    }

    public static function canDelete($record): bool
    {
        $user = Filament::auth()->user();

        return $user->roles->contains('name', 'super_admin') ||
            ($user->roles->contains('name', 'organization_admin') &&
                $record->organization_id == $user->organization_id);
    }
}