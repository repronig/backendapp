<?php

namespace Database\Factories;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notice>
 */
class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);
        $status = fake()->randomElement(['draft', 'published', 'archived']);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numerify('###'),
            'body' => fake()->paragraphs(3, true),
            'status' => $status,
            'published_at' => $status === 'published'
                ? fake()->dateTimeBetween('-30 days', 'now')
                : null,
            'created_by_user_id' => User::factory(),
        ];
    }
}