<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Stream;

class SchoolDataSeeder extends Seeder
{
    public function run()
    {
        $levels = [
            ['name' => 'O-Level', 'description' => 'Ordinary Level'],
            ['name' => 'A-Level', 'description' => 'Advanced Level'],
        ];

        foreach ($levels as $levelData) {
            Level::firstOrCreate(['name' => $levelData['name']], $levelData);
        }

        $oLevel = Level::where('name', 'O-Level')->first();
        $aLevel = Level::where('name', 'A-Level')->first();

        $classes = [
            ['name' => 'S1', 'level_id' => $oLevel->id],
            ['name' => 'S2', 'level_id' => $oLevel->id],
            ['name' => 'S3', 'level_id' => $oLevel->id],
            ['name' => 'S4', 'level_id' => $oLevel->id],
            ['name' => 'S5', 'level_id' => $aLevel->id],
            ['name' => 'S6', 'level_id' => $aLevel->id],
        ];

        foreach ($classes as $classData) {
            $class = SchoolClass::firstOrCreate(
                ['name' => $classData['name']],
                $classData
            );

            $streams = ['A', 'B', 'C'];
            foreach ($streams as $streamName) {
                Stream::firstOrCreate(
                    ['name' => $streamName, 'class_id' => $class->id],
                    ['capacity' => 40]
                );
            }
        }
    }
}
