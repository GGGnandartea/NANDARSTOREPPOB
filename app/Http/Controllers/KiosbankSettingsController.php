<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\KiosbankConfiguration;
use App\Services\KiosbankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KiosbankSettingsController extends Controller
{
    public function edit(KiosbankConfiguration $configuration)
    {
        $profiles = [];
        foreach (array_keys(KiosbankConfiguration::ENDPOINTS) as $mode) {
            $profile = $configuration->profile($mode);
            $profile['password_configured'] = $profile['password'] !== '';
            $profile['private_key_configured'] = $profile['private_key'] !== '';
            unset($profile['password'], $profile['private_key']);
            $profiles[$mode] = $profile;
        }

        $kiosbankService = new KiosbankService();

        return view('admin.ppob.settings', [
            'profiles'     => $profiles,
            'mode'         => $configuration->mode(),
            'enabled'      => Setting::where('key', 'kiosbank_enabled')->value('value') !== '0',
            'cutoffStatus' => $kiosbankService->getCutoffStatus(),
        ]);
    }

    public function updateCutoff(Request $request)
    {
        $request->validate([
            'ppob_cutoff_start'   => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'ppob_cutoff_end'     => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'ppob_cutoff_message' => 'nullable|string|max:500',
        ]);

        $maintenanceEnabled = $request->boolean('ppob_maintenance_enabled') ? '1' : '0';
        $cutoffEnabled = $request->boolean('ppob_cutoff_enabled') ? '1' : '0';
        $cutoffStart = trim($request->input('ppob_cutoff_start', '23:50'));
        $cutoffEnd = trim($request->input('ppob_cutoff_end', '00:10'));
        $cutoffMsg = trim((string) $request->input('ppob_cutoff_message', ''));

        DB::transaction(function () use ($maintenanceEnabled, $cutoffEnabled, $cutoffStart, $cutoffEnd, $cutoffMsg) {
            Setting::updateOrCreate(['key' => 'ppob_maintenance_enabled'], ['value' => $maintenanceEnabled]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_enabled'], ['value' => $cutoffEnabled]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_start'], ['value' => $cutoffStart]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_end'], ['value' => $cutoffEnd]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_message'], ['value' => $cutoffMsg]);
        });

        $statusInfo = $maintenanceEnabled === '1'
            ? 'Mode Maintenance Manual PPOB DIAKTIFKAN.'
            : ($cutoffEnabled === '1' ? "Jadwal Cut-Off Otomatis ({$cutoffStart} - {$cutoffEnd} WIB) AKTIF." : 'Jadwal Cut-Off Otomatis DINONAKTIFKAN.');

        return redirect()->route('admin.ppob.settings', ['section' => 'cutoff'])
            ->with('success', 'Pengaturan Jam Cut-Off & Maintenance PPOB berhasil disimpan! ' . $statusInfo);
    }

    public function update(Request $request, KiosbankConfiguration $configuration)
    {
        $rules = ['mode' => 'required|in:development,production', 'enabled' => 'sometimes|boolean'];
        foreach (array_keys(KiosbankConfiguration::ENDPOINTS) as $mode) {
            foreach (['username', 'mitra', 'account_id', 'merchant_id', 'merchant_name', 'counter_id'] as $field) {
                $rules["profiles.{$mode}.{$field}"] = 'required|string|max:255';
            }
            $rules["profiles.{$mode}.password"] = 'nullable|string|max:255';
            $rules["profiles.{$mode}.private_key"] = 'nullable|string|max:10000';
            $rules["profiles.{$mode}.public_key"] = 'nullable|string|max:10000';
        }
        $validated = $request->validate($rules);
        $selected = $validated['mode'];

        $existing = $configuration->profile($selected);
        if (empty($validated['profiles'][$selected]['password']) && $existing['password'] === '') {
            throw ValidationException::withMessages(["profiles.{$selected}.password" => 'Isi password untuk mode yang akan diaktifkan.']);
        }

        DB::transaction(function () use ($configuration, $validated, $request) {
            // Capture legacy profiles before changing the active mode.
            foreach ($validated['profiles'] as $mode => $values) {
                $configuration->saveProfile($mode, array_replace($configuration->profile($mode), array_filter(
                    $values, fn ($value, $field) => !in_array($field, ['password', 'private_key'], true) || filled($value), ARRAY_FILTER_USE_BOTH
                )));
            }
            Setting::updateOrCreate(['key' => 'kiosbank_mode'], ['value' => $validated['mode']]);
            Setting::updateOrCreate(['key' => 'kiosbank_enabled'], ['value' => $request->boolean('enabled') ? '1' : '0']);
        });

        return back()->with('success', 'Konfigurasi Kiosbank disimpan. Mode aktif: '.ucfirst($selected).'.');
    }
}
