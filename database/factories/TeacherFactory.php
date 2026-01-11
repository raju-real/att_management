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
        $teacher_no = Teacher::getTeacherNo();
        return [
            'teacher_no' => $teacher_no,
            'name'       => $this->faker->name(),
            'email'      => $this->faker->unique()->safeEmail(),
            'mobile'     => $this->faker->phoneNumber(),
            'designation'=> $this->faker->randomElement(['Lecturer', 'Assistant Professor', 'Professor', 'HOD']),
        ];
    }
}
