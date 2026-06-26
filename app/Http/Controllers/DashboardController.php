<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\OperationDataService;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard, OperationDataService $operations): View
    {
        return view('dashboard', [
            'stats' => $dashboard->stats(),
            'reportStatus' => $dashboard->reportStatus(),
            'roleDistribution' => $dashboard->roleDistribution(),
            'recentSchedules' => $dashboard->recentSchedules(),
            'recentReports' => $dashboard->recentReports(),
            'operations' => $operations->all(),
        ]);
    }
}
