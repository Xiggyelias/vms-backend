<?php

namespace App\Http\Controllers;

use App\Models\RegistrationDraft;
use Illuminate\Http\Request;

class RegistrationDraftController extends Controller
{
    public function save(Request $request)
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            return $this->fail('Not authenticated.', 401);
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            return $this->fail('Invalid JSON data.', 400);
        }

        $draft = RegistrationDraft::updateOrCreate(
            ['applicant_id' => $userId],
            ['draft_data' => $payload]
        );

        return $this->ok([
            'data' => [
                'id' => $draft->id,
                'updated_at' => optional($draft->updated_at)->toDateTimeString(),
            ],
        ], 'Draft saved successfully.');
    }
}

