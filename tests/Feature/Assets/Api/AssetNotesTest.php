<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class AssetNotesTest extends TestCase
{
    public function testThatANonExistentAssetIdReturnsError()
    {   
        $this->actingAsForApi(User::factory()->editAssets()->create())
            ->postJson(route('api.asset.note', 123456789))
            ->assertStatusMessageIs('error');
    }

    public function testRequiresPermissionToAddNoteToAssetAsset()
    {
        $asset = Asset::factory()->create();

        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.asset.note', $asset->id), [
                'note' => 'test'
            ])
            ->assertForbidden();
    }

    public function testAssetNoteIsSaved()
    {
        $asset = Asset::factory()->create();

        $this->actingAsForApi(User::factory()->editAssets()->create())
            ->postJson(route('api.asset.note', ['asset_id' => $asset->id]), [
                'note' => 'This is a test note.'
            ])
            ->assertStatusMessageIs('success')
            ->assertJson([
                'messages' => trans('general.note_added'),
                'payload' => [
                    'id' => e($asset->id),
                    'note' => 'This is a test note.',
                ],
            ])
            ->assertStatus(200);


            $note = ActionLog::where('item_id', $asset->id)
                ->where('action_type', 'manual_note')
                ->first();

            $this->assertNotNull($note, 'The note was not saved in the database.');
            $this->assertEquals('This is a test note.', $note->note, 'The note content does not match.');
    }

    public function testAssetNotesAreRetrievable()
    {
        $asset = Asset::factory()->create();

        $user = User::factory()->viewAssets()->create();

        $manualNote = Actionlog::factory()
            ->manualNote($user)
            ->create([
                'item_id' => $asset->id,
                'note' => 'This is a test note.',
            ]);



        $this->actingAsForApi($user)
            ->getJson(route('api.assets.notes', ['asset_id' => $asset->id]))
            ->assertOk()
            ->assertJson([
                'messages' => null,
                'payload' => [
                    'notes' => [
                        [
                            'note' => 'This is a test note.',
                            'created_by' => $manualNote->created_by,
                            'username' => $user->username,
                            'item_id' => $manualNote->item_id,
                            'item_type' => Asset::class,
                            'action_type' => 'manual_note',
                        ]
                    ]
                ],
            ]);
    }
}
