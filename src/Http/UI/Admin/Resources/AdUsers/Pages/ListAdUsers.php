<?php

declare(strict_types=1);

namespace Rimba\Ldap\Http\UI\Admin\Resources\AdUsers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Rimba\Ldap\Http\UI\Admin\Resources\AdUsers\AdUserResource;

class ListAdUsers extends ListRecords
{
    protected static string $resource = AdUserResource::class;

    protected static ?string $title = 'Active Directory Users';

    protected ?string $subheading = 'Synchronize and audit active directory/LDAP corporate user nodes.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
