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
            'ppob_status_mode'          => ['nullable', 'in:online,offline,maintenance,gangguan,restock'],
            'ppob_status_title'         => ['nullable', 'string', 'max:180'],
            'ppob_status_eta'           => ['nullable', 'string', 'max:120'],
            'ppob_cutoff_start'         => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'ppob_cutoff_end'           => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'ppob_cutoff_message'       => ['nullable', 'string', 'max:800'],
            'ppob_allow_catalog_browse' => ['nullable', 'boolean'],
        ]);

        if ($request->filled('ppob_status_mode')) {
            $statusMode = $request->input('ppob_status_mode');
            $maintenanceEnabled = $statusMode !== 'online' ? '1' : '0';
        } else {
            $maintenanceEnabled = $request->boolean('ppob_maintenance_enabled') ? '1' : '0';
            $statusMode = $maintenanceEnabled === '1' ? 'maintenance' : 'online';
        }

        $kiosbankEnabled = $statusMode === 'offline' ? '0' : '1';
        $cutoffEnabled = $request->boolean('ppob_cutoff_enabled') ? '1' : '0';
        $cutoffStart = trim($request->input('ppob_cutoff_start', '23:50'));
        $cutoffEnd = trim($request->input('ppob_cutoff_end', '00:10'));
        $statusTitle = trim((string) $request->input('ppob_status_title', ''));
        $cutoffMsg = trim((string) $request->input('ppob_cutoff_message', ''));
        $statusEta = trim((string) $request->input('ppob_status_eta', ''));
        $allowBrowse = $request->has('ppob_allow_catalog_browse')
            ? ($request->boolean('ppob_allow_catalog_browse') ? '1' : '0')
            : '1';

        DB::transaction(function () use ($statusMode, $maintenanceEnabled, $kiosbankEnabled, $cutoffEnabled, $cutoffStart, $cutoffEnd, $statusTitle, $cutoffMsg, $statusEta, $allowBrowse) {
            Setting::updateOrCreate(['key' => 'ppob_status_mode'], ['value' => $statusMode]);
            Setting::updateOrCreate(['key' => 'ppob_maintenance_enabled'], ['value' => $maintenanceEnabled]);
            Setting::updateOrCreate(['key' => 'kiosbank_enabled'], ['value' => $kiosbankEnabled]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_enabled'], ['value' => $cutoffEnabled]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_start'], ['value' => $cutoffStart]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_end'], ['value' => $cutoffEnd]);
            Setting::updateOrCreate(['key' => 'ppob_status_title'], ['value' => $statusTitle]);
            Setting::updateOrCreate(['key' => 'ppob_cutoff_message'], ['value' => $cutoffMsg]);
            Setting::updateOrCreate(['key' => 'ppob_status_eta'], ['value' => $statusEta]);
            Setting::updateOrCreate(['key' => 'ppob_allow_catalog_browse'], ['value' => $allowBrowse]);
        });

        $modeLabels = [
            'online'      => 'ONLINE / NORMAL',
            'offline'     => 'SEDANG OFFLINE',
            'maintenance' => 'SEDANG MAINTENANCE',
            'gangguan'    => 'GANGGUAN SERVER PUSAT',
            'restock'     => 'RESTOCK DEPOSIT / REKONSILIASI',
        ];
        $modeText = $modeLabels[$statusMode] ?? strtoupper($statusMode);

        $statusInfo = $statusMode !== 'online'
            ? "Status Layanan PPOB diatur ke: {$modeText}."
            : ($cutoffEnabled === '1' ? "Status PPOB ONLINE & Jadwal Cut-Off Otomatis ({$cutoffStart} - {$cutoffEnd} WIB) AKTIF." : 'Status PPOB ONLINE (Tanpa Cut-Off Otomatis).');

        return redirect()->route('admin.ppob.settings', ['section' => 'cutoff'])
            ->with('success', 'Pengaturan Status & Cut-Off PPOB berhasil disimpan! ' . $statusInfo);
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
            $isEnabled = $request->boolean('enabled');
            Setting::updateOrCreate(['key' => 'kiosbank_enabled'], ['value' => $isEnabled ? '1' : '0']);
            $currentStatusMode = Setting::where('key', 'ppob_status_mode')->value('value');
            if (!$isEnabled) {
                Setting::updateOrCreate(['key' => 'ppob_status_mode'], ['value' => 'offline']);
                Setting::updateOrCreate(['key' => 'ppob_maintenance_enabled'], ['value' => '1']);
            } elseif ($currentStatusMode === 'offline') {
                Setting::updateOrCreate(['key' => 'ppob_status_mode'], ['value' => 'online']);
                Setting::updateOrCreate(['key' => 'ppob_maintenance_enabled'], ['value' => '0']);
            }
        });

        return back()->with('success', 'Konfigurasi Kiosbank disimpan. Mode aktif: '.ucfirst($selected).'.');
    }
}
