<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companySettings = [
            [
                'key' => 'company.name',
                'value' => 'OptimaSphere ERP',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Company Name',
                'description' => 'The official name of your company',
                'is_public' => true,
            ],
            [
                'key' => 'company.email',
                'value' => 'info@optimasphere.com',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Company Email',
                'description' => 'Primary contact email address',
                'is_public' => true,
            ],
            [
                'key' => 'company.phone',
                'value' => '+1 (555) 123-4567',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Company Phone',
                'description' => 'Primary contact phone number',
                'is_public' => true,
            ],
            [
                'key' => 'company.address',
                'value' => '123 Business Street, Suite 100',
                'type' => 'text',
                'group' => 'company',
                'label' => 'Company Address',
                'description' => 'Full company address',
                'is_public' => true,
            ],
            [
                'key' => 'company.city',
                'value' => 'New York',
                'type' => 'string',
                'group' => 'company',
                'label' => 'City',
                'description' => 'City where company is located',
                'is_public' => true,
            ],
            [
                'key' => 'company.state',
                'value' => 'NY',
                'type' => 'string',
                'group' => 'company',
                'label' => 'State/Province',
                'description' => 'State or province',
                'is_public' => true,
            ],
            [
                'key' => 'company.postal_code',
                'value' => '10001',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Postal Code',
                'description' => 'ZIP or postal code',
                'is_public' => true,
            ],
            [
                'key' => 'company.country',
                'value' => 'United States',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Country',
                'description' => 'Country where company is located',
                'is_public' => true,
            ],
            [
                'key' => 'company.logo',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Company Logo',
                'description' => 'Path to company logo image',
                'is_public' => true,
            ],
            [
                'key' => 'company.website',
                'value' => 'https://optimasphere.com',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Website',
                'description' => 'Company website URL',
                'is_public' => true,
            ],
            [
                'key' => 'company.tax_id',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Tax ID',
                'description' => 'Company tax identification number',
                'is_public' => false,
            ],
            [
                'key' => 'company.registration_number',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'label' => 'Registration Number',
                'description' => 'Company registration or business license number',
                'is_public' => false,
            ],
        ];

        foreach ($companySettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
