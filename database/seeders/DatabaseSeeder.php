<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\Speaker;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Nova\Notifications\NovaNotification;
use Laravel\Nova\URL;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SpeakerSeeder::class,
            AgendaItemSeeder::class,
            PartnerSeeder::class,
            FaqSeeder::class,
        ]);

        // The demo accounts have a known password, so production only gets the content.
        // Create the real admin there with `php artisan nova:user`.
        if (app()->isProduction()) {
            return;
        }

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // A second account so Nova's "Impersonate" action has someone to impersonate.
        User::factory()->create([
            'name' => 'Content Editor',
            'email' => 'editor@example.com',
        ]);

        $user->notify(NovaNotification::make()
            ->message(sprintf('Imported %d speakers and %d sessions from wawtech.io.', Speaker::count(), AgendaItem::count()))
            ->action('Open agenda', URL::make('/resources/agenda-items'))
            ->icon('arrow-down-tray')
            ->type('success'));
    }
}
