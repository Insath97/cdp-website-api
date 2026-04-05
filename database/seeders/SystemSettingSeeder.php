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
            'contact_numbers' => '+94 11 234 5678',
            'official_email' => 'hello@demo-api.com',
            'digital_presence' => 'www.demo-api.com',
            'facebook_url' => "",
            'instagram_url' => "",
            'youtube_url' => "",
            'twitter_url' => "",
            'linkedin_url' => "",
            'office_address' => "",
            'contact_notification_email' => 'hello@demo-api.com',
            'enable_contact_notification' => '1',
            'lms_url' => 'https://lms.demo-api.com/',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
