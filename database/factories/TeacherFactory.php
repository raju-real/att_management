<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = \App\Models\Teacher::class;

    public function definition(): array
    {
        //$serial_no = Teacher::getTeacherSlNo(); // get next serial
        $serial_no = Teacher::getTeacherNo();
        return [
            // Unique teacher number like T1001, T1002, etc.
            'teacher_sl_no' => $serial_no,
            'teacher_no' => 'T' . $serial_no,
            'name'       => $this->faker->name(),
            'email'      => $this->faker->unique()->safeEmail(),
            'mobile'     => $this->faker->phoneNumber(),
            'designation'=> $this->faker->randomElement(['Lecturer', 'Assistant Professor', 'Professor', 'HOD']),
        ];
    }
}
