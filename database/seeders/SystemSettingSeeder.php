<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => 'Demo API Starter Kit',
            'official_email' => 'hello@demo-api.com',
            'digital_presence' => 'www.demo-api.com',
            'facebook_url' => "",
            'instagram_url' => "",
            'youtube_url' => "",
            'twitter_url' => "",
            'linkedin_url' => "",
            'office_address' => "",
            'contact_notification_email' => 'piranya105@gmail.com',
            'enable_contact_notification' => '1',
            'mobile_number' => "",
            'whatsapp_number' => "",
            'head_office_address' => "",
            'company_registration_number' => "",
            'career_mail' => 'piranya105@gmail.com',
            'enable_job_alert_notification' => '1'
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
