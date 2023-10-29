<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\MoveFileToCloud;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MoveFileToCloudTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_rate_limiter(): void
    {
        $file = File::factory()->isFile()->make();
        $middleware = (new MoveFileToCloud($file))->middleware();

        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(RateLimited::class, $middleware[0]);
    }

    public function test_failed_not_max_attempts_exception(): void
    {
        Queue::fake();

        $file = File::factory()->isFile()->make();
        $job = new MoveFileToCloud($file, 2, 1);

        $job->failed(new \RuntimeException());

        Queue::assertNothingPushed();
    }

    public function test_failed_max_attempts_exception(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = File::factory()->isFile()->create();
        $job = new MoveFileToCloud($file, 2, 1);

        for ($i = 0; $i <= 5; $i++) {
            $job->failed(new MaxAttemptsExceededException());
        }

        Queue::assertPushed(MoveFileToCloud::class, 2);
        $this->assertEquals(3, $job->currentRetryCount);
    }
}
