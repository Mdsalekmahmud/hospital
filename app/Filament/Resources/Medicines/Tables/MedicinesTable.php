<?php

namespace App\Filament\Resources\Medicines\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class MedicinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand_name')->label('Trade Name')->sortable()->searchable(),
                
                TextColumn::make('generic')->label('Generic')->sortable()->searchable(),
                TextColumn::make('manufacturer')->label('Manufacturer')->sortable()->searchable(),
                TextColumn::make('type')->label('Type')->sortable()->searchable(),
                TextColumn::make('dosage_form')->label('Dosage Form')->sortable()->searchable(),
                TextColumn::make('strength')->label('Strength')->sortable()->searchable(),
                TextColumn::make('package_container')->label('Package Container')->sortable()->searchable(),
                TextColumn::make('package_size')->label('Package Size')->sortable()->searchable(),
                BooleanColumn::make('is_active')->label('Active')->sortable(),
                TextColumn::make('created_at')->label('Created At')->dateTime()->sortable(),
                TextColumn::make('updated_at')->label('Updated At')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
