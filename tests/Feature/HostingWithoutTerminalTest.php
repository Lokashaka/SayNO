<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Hosting cPanel paket terendah: tanpa SSH, tanpa symlink, tanpa proc_open.
 */
class HostingWithoutTerminalTest extends TestCase
{
    use RefreshDatabase;

    private const SETUP_TOKEN = 'setup-token-yang-panjangnya-minimal-32-karakter';

    private const CRON_TOKEN = 'cron-token-yang-panjangnya-minimal-32-karakter';

    #[Test]
    public function scheduled_tasks_run_in_process_without_proc_open(): void
    {
        $events = app(Schedule::class)->events();

        $this->assertNotEmpty($events);

        foreach ($events as $event) {
            $this->assertInstanceOf(CallbackEvent::class, $event, "Tugas \"{$event->getSummaryForDisplay()}\" akan memanggil proc_open.");
        }
    }

    #[Test]
    public function setup_is_hidden_until_a_long_token_is_configured(): void
    {
        config(['sayno.setup_token' => null]);
        $this->get('/_setup')->assertNotFound();

        config(['sayno.setup_token' => 'pendek']);
        $this->get('/_setup')->assertNotFound();
        $this->post('/_setup/masuk', ['token' => 'pendek'])->assertNotFound();
    }

    #[Test]
    public function setup_requires_the_token_before_running_anything(): void
    {
        config(['sayno.setup_token' => self::SETUP_TOKEN]);

        $this->get('/_setup')->assertOk()->assertSee('Token setup')->assertDontSee('Kesiapan server');
        $this->post('/_setup/jalankan', ['action' => 'migrate'])->assertForbidden();
        $this->post('/_setup/masuk', ['token' => 'salah'])->assertSessionHasErrors('token');
    }

    #[Test]
    public function setup_shows_server_checks_and_runs_seeding(): void
    {
        config(['sayno.setup_token' => self::SETUP_TOKEN, 'sayno.demo_brand_token' => null]);

        $this->post('/_setup/masuk', ['token' => self::SETUP_TOKEN])->assertRedirect('/_setup');

        $this->get('/_setup')->assertOk()
            ->assertSee('Kesiapan server')
            ->assertSee('Ekstensi intl')
            ->assertSee('Cron berjalan');

        $this->post('/_setup/jalankan', ['action' => 'seed'])->assertRedirect('/_setup');

        $this->assertSame(9, Brand::count());
        $this->get('/_setup')->assertSee('Token dashboard /org/bca');
    }

    #[Test]
    public function setup_works_before_the_sessions_and_cache_tables_exist(): void
    {
        config(['session.driver' => 'database', 'cache.default' => 'database']);
        $this->app->instance('request', Request::create('/_setup/masuk', 'POST'));

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('file', config('session.driver'));
        $this->assertSame('file', config('cache.default'));
        $this->assertSame('database', config('sayno.primary_cache_store'));

        config(['session.driver' => 'database', 'cache.default' => 'database']);
        $this->app->instance('request', Request::create('/'));
        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('database', config('session.driver'), 'Halaman lain tetap memakai sesi database.');
    }

    #[Test]
    public function setup_only_accepts_known_actions(): void
    {
        config(['sayno.setup_token' => self::SETUP_TOKEN]);
        $this->post('/_setup/masuk', ['token' => self::SETUP_TOKEN]);

        $this->post('/_setup/jalankan', ['action' => 'db:wipe'])->assertSessionHasErrors('action');
    }

    #[Test]
    public function web_cron_is_hidden_without_the_right_token(): void
    {
        config(['sayno.cron_token' => null]);
        $this->get('/_cron?token=apa-saja')->assertNotFound();

        config(['sayno.cron_token' => self::CRON_TOKEN]);
        $this->get('/_cron?token=salah')->assertNotFound();
    }

    #[Test]
    public function web_cron_runs_the_scheduler(): void
    {
        config(['sayno.cron_token' => self::CRON_TOKEN]);

        $this->get('/_cron?token='.self::CRON_TOKEN)->assertOk()->assertSeeText('ok');

        $this->assertNotNull(Cache::get('scheduler:last_run'));
    }
}
