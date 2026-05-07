<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkFileFactory extends Factory
{
    protected $model = WorkFile::class;

    public function definition(): array
    {
        $fileType = fake()->randomElement([
            'cover_image',
            'copyright_page',
        ]);

        $isImage = $fileType === 'cover_image';

        return [
            'work_id' => Work::factory(),
            'file_type' => $fileType,
            'file_path' => 'works/' . fake()->uuid() . '.' . ($isImage ? 'jpg' : 'pdf'),
            'file_name' => fake()->word() . '.' . ($isImage ? 'jpg' : 'pdf'),
            'mime_type' => $isImage ? 'image/jpeg' : 'application/pdf',
            'file_size' => fake()->numberBetween(50000, 500000),
            'uploaded_by_user_id' => User::factory(),
        ];
    }

    public function forWork(Work $work): static
    {
        return $this->state(fn () => [
            'work_id' => $work->id,
        ]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(fn () => [
            'uploaded_by_user_id' => $user->id,
        ]);
    }

    public function cover(): static
    {
        return $this->state(fn () => [
            'file_type' => 'cover_image',
            'file_path' => 'works/' . fake()->uuid() . '.jpg',
            'file_name' => fake()->word() . '.jpg',
            'mime_type' => 'image/jpeg',
        ]);
    }

    public function copyright(): static
    {
        return $this->state(fn () => [
            'file_type' => 'copyright_page',
            'file_path' => 'works/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->word() . '.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}