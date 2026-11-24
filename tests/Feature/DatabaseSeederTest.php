<?php

use App\Enums\PartnerTier;
use App\Models\AgendaItem;
use App\Models\Partner;
use App\Models\Speaker;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('stores agenda times as UTC converted from Warsaw time', function () {
    $this->seed();

    $talk = AgendaItem::where('title', 'like', 'Building an In-House CMS on Laravel Nova%')->sole();
    expect($talk->starts_at->toDateTimeString())->toBe('2026-11-26 15:30:00')
        ->and($talk->ends_at->toDateTimeString())->toBe('2026-11-26 15:55:00')
        ->and($talk->stage->name)->toBe('GROWTH');
});

it('links panel speakers in site order and flags the moderator', function () {
    $this->seed();

    $panel = AgendaItem::where('title', 'Engineering Productivity: Are We Measuring the Wrong Things?')->sole();
    expect($panel->speakers->map(fn (Speaker $speaker) => [$speaker->name, $speaker->pivot->is_moderator])->all())->toBe([
        ['Siim Viidu', 0],
        ['Vadym Vlasenko', 0],
        ['Julia Korytko, PhD', 1],
    ]);
    expect($panel->tags->pluck('name')->all())->toBe(['Engineering', 'Leadership']);
});

it('marks the Polish session and gives each stage its site colour', function () {
    $this->seed();

    $polish = AgendaItem::where('title', 'like', '%– PL')->sole();
    expect($polish->language)->toBe('pl')
        ->and(AgendaItem::where('language', 'pl')->count())->toBe(1)
        ->and(Stage::pluck('color', 'name')->all())->toBe([
            'MAIN' => '#9859FF',
            'ENGINEERING' => '#3C60FF',
            'GROWTH' => '#02FEB9',
            'STUDIO' => '#CCFF00',
            'PRO STAGE' => '#F501A4',
        ]);
});

it('notifies the test user that the content was imported', function () {
    Gate::define('viewNova', fn () => true);

    $this->seed();

    $user = User::where('email', 'test@example.com')->sole();
    $this->assertDatabaseHas('nova_notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
    ]);
});

it('copies speaker photos and partner logos to the public disk', function () {
    $this->seed();

    $speaker = Speaker::where('name', 'Daniel Stenberg')->sole();
    $partner = Partner::where('name', 'Laravel')->sole();
    expect($speaker->photo)->toBe('speakers/daniel-stenberg.webp')
        ->and($partner->logo)->toBe('partners/laravel.webp')
        ->and($partner->tier)->toBe(PartnerTier::Silver);
    Storage::disk('public')->assertExists([$speaker->photo, $partner->logo]);
});
