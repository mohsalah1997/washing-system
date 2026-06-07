<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PurchasesAndNotes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'المشتريات والملاحظات';

    protected static ?string $navigationGroup = 'المشتريات والملاحظات';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.purchases-and-notes';

    protected static ?string $title = 'المشتريات والملاحظات';

    public string $activeTab = 'purchases';

    public static function canAccess(): bool
    {
        return Auth::check()
            && (auth()->user()->can('view_any_shop::purchase') || auth()->user()->can('view_any_team::note'));
    }

    public function setActiveTab(string $tab): void
    {
        if (in_array($tab, ['purchases', 'notes'], true)) {
            $this->activeTab = $tab;
        }
    }
}
