<?php

namespace App\Filament\Syndic\Widgets;

use App\Filament\Syndic\Pages\ResidenceSettings;
use App\Filament\Syndic\Resources\ApartmentResource;
use App\Filament\Syndic\Resources\ExpenseResource;
use App\Filament\Syndic\Resources\PaymentResource;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Filament\Widgets\Widget;

class ResidenceHeaderWidget extends Widget
{
    protected static string $view = 'filament.syndic.widgets.residence-header-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user   = auth()->user();
        $tenant = $user?->tenant;

        if (! $tenant) {
            return [];
        }

        $totalBuildings  = Building::where('tenant_id', $tenant->id)->count();
        $totalApartments = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenant->id))->count();
        $totalResidents  = User::where('tenant_id', $tenant->id)->where('role', 'RESIDENT')->where('status', 'ACTIVE')->count();

        $propertyTypeLabel = match ($tenant->property_type) {
            'SINGLE_BUILDING'        => 'عمارة منفردة',
            'RESIDENTIAL_COMPLEX'    => 'مجمع سكني مغلق',
            'HOUSING_ESTATE_VILLAS' => 'تجزئة فيلات',
            'COMMERCIAL_RESIDENTIAL' => 'مجمع تجاري وسكني',
            default                  => 'إقامة سكنية',
        };

        return [
            'tenant'               => $tenant,
            'user'                 => $user,
            'propertyTypeLabel'    => $propertyTypeLabel,
            'totalBuildings'       => $totalBuildings,
            'totalApartments'      => $totalApartments,
            'totalResidents'       => $totalResidents,
            'paymentCreateUrl'     => PaymentResource::getUrl('create'),
            'expenseCreateUrl'     => ExpenseResource::getUrl('create'),
            'apartmentsIndexUrl'   => ApartmentResource::getUrl('index'),
            'residenceSettingsUrl' => ResidenceSettings::getUrl(),
        ];
    }
}
