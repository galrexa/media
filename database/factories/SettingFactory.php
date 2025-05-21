<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $categories = ['modal', 'general', 'notification', 'email'];
        
        return [
            'key' => $this->faker->unique()->word . '_' . $this->faker->numberBetween(1, 100),
            'value' => $this->faker->paragraph(1),
            'category' => $this->faker->randomElement($categories),
        ];
    }
    
    /**
     * Factory state untuk pengaturan modal
     */
    public function modal()
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'modal',
            ];
        });
    }
    
    /**
     * Factory state untuk pengaturan general
     */
    public function general()
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'general',
            ];
        });
    }
}