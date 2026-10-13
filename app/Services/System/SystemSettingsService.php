<?php

namespace App\Services\System;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingsService
{
    private const CACHE_PREFIX = 'sys_setting_';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(self::CACHE_PREFIX . $key, 3600, function () use ($key, $default) {
            return SystemSetting::getVal($key, $default);
        });
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): void
    {
        SystemSetting::setVal($key, $value, $type, $group, $description);
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public function isFeatureEnabled(string $featureKey, bool $default = true): bool
    {
        return (bool) $this->get($featureKey, $default);
    }

    public function isMaintenanceMode(): bool
    {
        return (bool) $this->get('maintenance_mode_enabled', false);
    }

    public function getMaintenanceMessage(): string
    {
        return (string) $this->get('maintenance_message', 'المنصة تخضع حالياً لأعمال صيانة وتحسين دورية مجدولة. سنعود للعمل قريباً جداً.');
    }

    public function getAllGrouped(): array
    {
        $all = SystemSetting::all();
        $grouped = [];
        foreach ($all as $setting) {
            $grouped[$setting->group][] = $setting;
        }
        return $grouped;
    }

    public function seedDefaultsIfMissing(): void
    {
        $defaults = [
            // Features
            ['key' => 'allow_new_registrations', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'description' => 'السماح بتسجيل سانديك وإقامات جديدة في المنصة'],
            ['key' => 'enable_online_payments', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'description' => 'تفعيل بوابات الدفع الإلكتروني (CMI / بطاقات بنكية)'],
            ['key' => 'enable_resident_polls', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'description' => 'تفعيل نظام التصويت واستطلاعات الرأي للملاك'],
            ['key' => 'enable_incident_reporting', 'value' => '1', 'type' => 'boolean', 'group' => 'features', 'description' => 'تفعيل إرسال الشكاوى والمقترحات من السكان'],
            
            // Maintenance
            ['key' => 'maintenance_mode_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'maintenance', 'description' => 'تفعيل وضع الصيانة العام على مستوى كافة البوابات'],
            ['key' => 'maintenance_message', 'value' => 'المنصة تخضع حالياً لأعمال صيانة دورية مجدولة. سنعود للعمل قريباً جداً.', 'type' => 'string', 'group' => 'maintenance', 'description' => 'رسالة الصيانة التي تظهر للمستخدمين'],
            ['key' => 'maintenance_bypass_secret', 'value' => 'smart_syndic_bypass_2026', 'type' => 'string', 'group' => 'maintenance', 'description' => 'رمز المرور لتجاوز وضع الصيانة'],

            // Support & Contact
            ['key' => 'support_phone', 'value' => '+212 5 22 00 00 00', 'type' => 'string', 'group' => 'support', 'description' => 'رقم هاتف الدعم الفني المركزي'],
            ['key' => 'support_whatsapp', 'value' => '+212 6 00 00 00 00', 'type' => 'string', 'group' => 'support', 'description' => 'رقم الواتساب الرسمي للمساعدة الفورية'],
            ['key' => 'support_email', 'value' => 'support@smartsyndic.ma', 'type' => 'string', 'group' => 'support', 'description' => 'البريد الإلكتروني المخصص للدعم الفني'],
            ['key' => 'platform_notice_footer', 'value' => 'منصة Smart Syndic — الإدارة الذكية والشفافة للإقامات المشتركة بالمغرب.', 'type' => 'string', 'group' => 'support', 'description' => 'إشعار التذييل العام للبوابة'],
        ];

        foreach ($defaults as $d) {
            if (!SystemSetting::where('key', $d['key'])->exists()) {
                SystemSetting::create($d);
            }
        }
    }
}
