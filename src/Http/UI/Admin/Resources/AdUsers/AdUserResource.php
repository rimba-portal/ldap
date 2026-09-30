<?php

namespace Rimba\Ldap\Http\UI\Admin\Resources\AdUsers;

use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdUserResource extends Resource
{
    protected static ?string $model = \Rimba\Ldap\Models\AdUser::class;

    protected static string|UnitEnum|null $navigationGroup = 'Ldap';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-play';

    protected static ?int $navigationSort = 46;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema { return $schema->components([]); }

    public static function infolist(Schema $schema): Schema { return $schema->components([]); }

    public static function table(Table $table): Table { return $table->columns([]); }

    public static function getRelations(): array 
    { 
        return [ 
            // 
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Rimba\Ldap\Http\UI\Admin\Resources\AdUsers\Pages\ListAdUsers::route('/'),
            // 'create' => \Rimba\Ldap\Http\UI\Admin\Resources\AdUsers\Pages\CreateAdUser::route('/create'),
            // 'view' => \Rimba\Ldap\Http\UI\Admin\Resources\AdUsers\Pages\ViewAdUser::route('/{record}'),
            // 'edit' => \Rimba\Ldap\Http\UI\Admin\Resources\AdUsers\Pages\EditAdUser::route('/{record}/edit'),
            //
        ];
    }
}
