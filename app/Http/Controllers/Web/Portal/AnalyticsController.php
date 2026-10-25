<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('portal.reports.index', array_merge(['tab' => 'analytics'], $request->all()));
    }
}
