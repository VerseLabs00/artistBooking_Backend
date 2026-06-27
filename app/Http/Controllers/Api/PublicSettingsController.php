<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class PublicSettingsController extends Controller
{
    /**
     * Returns only the maintenance_mode flag — no authentication required.
     * Called by the frontend DevGate on every page load to decide whether
     * to show the developer login screen or render the site normally.
     */
    public function index()
    {
        $raw = Setting::getValue('maintenance_mode', false);
        $maintenanceMode = filter_var($raw, FILTER_VALIDATE_BOOLEAN);

        return response()->json([
            'maintenance_mode' => $maintenanceMode,
        ]);
    }
}
