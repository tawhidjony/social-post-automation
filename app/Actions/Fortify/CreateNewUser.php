<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Plan;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Str;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $user->ensureCurrentWorkspace();

        $freePlan = Plan::where('slug', 'free')->first();

        $workspace = Workspace::create([
            'owner_id' => $user->id,
            'name' => $user->name . "'s Workspace",
            'slug' => Str::slug($user->name . '-' . Str::random(5)),
            'current_plan_id' => $freePlan->id,
        ]);

        $user->workspaces()->attach($workspace->id, ['role' => 'owner']);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user->refresh();
    }
}
