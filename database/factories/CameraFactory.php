<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Camera>
 */
class CameraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'location' => $this->faker->city(),
            'connection_type' => $this->faker->randomElement(['USB', 'STREAM']),
            'usb_path' => $this->faker->optional()->filePath(),
            'stream_url' => $this->faker->optional()->url(),
            'is_active' => $this->faker->boolean(),
            'resolution' => $this->faker->randomElement(['640x480', '800x600', '1024x768', '1280x720', '1920x1080']),
            'frame_rate' => $this->faker->numberBetween(15, 60),
            'added_by' => $this->faker->randomElement(User::pluck('id')->toArray()),
            'stream_username' => $this->faker->optional()->userName(),
            'stream_password' => $this->faker->optional()->password(),
        ];
    }
}
