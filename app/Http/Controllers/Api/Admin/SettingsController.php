<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private array $schema = [
        'commission_rate'        => ['type' => 'float',   'default' => 15],
        'deposit_rate'           => ['type' => 'float',   'default' => 30],
        'featured_listing_price' => ['type' => 'float',   'default' => 2500],
        'auto_approve_enabled'   => ['type' => 'boolean', 'default' => false],
        'maintenance_mode'       => ['type' => 'boolean', 'default' => false],
        'notifications_enabled'  => ['type' => 'boolean', 'default' => true],
    ];


    public function index()
    {
        $stored = Setting::all()->pluck('value', 'key');

        $settings = [];
        foreach ($this->schema as $key => $meta) {
            $raw = $stored->get($key, $meta['default']);
            $settings[$key] = $meta['type'] === 'boolean'
                ? filter_var($raw, FILTER_VALIDATE_BOOLEAN)
                : (float) $raw;
        }

        return response()->json($settings);
    }


    public function update(Request $request)
    {
        $request->validate([
            'commission_rate'        => 'sometimes|numeric|min:0|max:100',
            'deposit_rate'           => 'sometimes|numeric|min:0|max:100',
            'featured_listing_price' => 'sometimes|numeric|min:0',
            'auto_approve_enabled'   => 'sometimes|boolean',
            'maintenance_mode'       => 'sometimes|boolean',
            'notifications_enabled'  => 'sometimes|boolean',
        ]);

        foreach ($this->schema as $key => $meta) {
            if ($request->has($key)) {
                Setting::setValue($key, $request->input($key));
            }
        }


        if ($request->has('commission_rate')) {
            $rate = $request->input('commission_rate');
            \App\Models\Notification::sendToAdmins(
                'system',
                'Commission Rate Updated',
                "Platform commission rate changed to {$rate}%.",
                '/settings'
            );
        }

        return $this->index();
    }
}
