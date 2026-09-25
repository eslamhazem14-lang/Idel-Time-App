<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_attachments_are_stored_privately_with_random_names(): void
    {
        Storage::fake('local');
        $requester = $this->requester();

        $this->actingAs($requester)->post(route('requester.tasks.store'), $this->taskData([
            'attachments' => [UploadedFile::fake()->createWithContent('spec.txt', 'hello spec')],
        ]))->assertSessionHasNoErrors();

        $attachment = Attachment::query()->firstOrFail();
        $this->assertSame('spec.txt', $attachment->original_name);
        $this->assertStringNotContainsString('spec', $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);

        // developers cannot download attachments of a task that is not live
        $this->actingAs($this->developer())->get(route('attachments.show', $attachment))->assertForbidden();
        $this->actingAs($requester)->get(route('attachments.show', $attachment))->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
    }

    public function test_dangerous_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs($this->requester())->post(route('requester.tasks.store'), $this->taskData([
            'attachments' => [UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]);')],
        ]))->assertSessionHasErrors('attachments.0');

        $this->actingAs($this->requester())->post(route('requester.tasks.store'), $this->taskData([
            'attachments' => [UploadedFile::fake()->create('huge.pdf', 10000, 'application/pdf')],
        ]))->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_signed_urls_expire(): void
    {
        Storage::fake('local');
        $requester = $this->requester();
        $this->actingAs($requester)->post(route('requester.tasks.store'), $this->taskData([
            'attachments' => [UploadedFile::fake()->createWithContent('notes.md', '# notes')],
        ]));
        $attachment = Attachment::query()->firstOrFail();
        $url = URL::temporarySignedRoute('attachments.signed', now()->addMinutes(5), ['attachment' => $attachment->id]);

        $this->get($url)->assertOk();
        $this->get(route('attachments.signed', $attachment))->assertForbidden();
        $this->travel(10)->minutes();
        $this->get($url)->assertForbidden();
    }
}
