<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class NigerianStatesSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['name' => 'Abia', 'code' => 'AB'], ['name' => 'Adamawa', 'code' => 'AD'], ['name' => 'Akwa Ibom', 'code' => 'AK'],
            ['name' => 'Anambra', 'code' => 'AN'], ['name' => 'Bauchi', 'code' => 'BA'], ['name' => 'Bayelsa', 'code' => 'BY'],
            ['name' => 'Benue', 'code' => 'BE'], ['name' => 'Borno', 'code' => 'BO'], ['name' => 'Cross River', 'code' => 'CR'],
            ['name' => 'Delta', 'code' => 'DE'], ['name' => 'Ebonyi', 'code' => 'EB'], ['name' => 'Edo', 'code' => 'ED'],
            ['name' => 'Ekiti', 'code' => 'EK'], ['name' => 'Enugu', 'code' => 'EN'], ['name' => 'FCT', 'code' => 'FC'],
            ['name' => 'Gombe', 'code' => 'GO'], ['name' => 'Imo', 'code' => 'IM'], ['name' => 'Jigawa', 'code' => 'JI'],
            ['name' => 'Kaduna', 'code' => 'KD'], ['name' => 'Kano', 'code' => 'KN'], ['name' => 'Katsina', 'code' => 'KT'],
            ['name' => 'Kebbi', 'code' => 'KE'], ['name' => 'Kogi', 'code' => 'KO'], ['name' => 'Kwara', 'code' => 'KW'],
            ['name' => 'Lagos', 'code' => 'LA'], ['name' => 'Nasarawa', 'code' => 'NA'], ['name' => 'Niger', 'code' => 'NI'],
            ['name' => 'Ogun', 'code' => 'OG'], ['name' => 'Ondo', 'code' => 'ON'], ['name' => 'Osun', 'code' => 'OS'],
            ['name' => 'Oyo', 'code' => 'OY'], ['name' => 'Plateau', 'code' => 'PL'], ['name' => 'Rivers', 'code' => 'RI'],
            ['name' => 'Sokoto', 'code' => 'SO'], ['name' => 'Taraba', 'code' => 'TA'], ['name' => 'Yobe', 'code' => 'YO'], ['name' => 'Zamfara', 'code' => 'ZA'],
        ];

        foreach ($states as $state) {
            State::query()->updateOrCreate(['code' => $state['code']], ['name' => $state['name'], 'country_code' => 'NG']);
        }
    }
}
