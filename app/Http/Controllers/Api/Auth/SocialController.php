<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\SocialAccount;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Passport\Client;

class SocialAuthController extends Controller
{
    use IssueTokenTrait;
    private $client;

    /**
     * SocialAuthController constructor.
     */
    public function __construct()
    {
        $this->client = Client::find(1);
    }

    /**
     * Authorize from Social Network Account
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function socialAuth(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'nullable|email',
            'provider' => 'required|in:facebook,twitter,google',
            'provider_user_id' => 'required'
        ]);

        $socialAccount = SocialAccount::where('provider', $request->provider)
            ->where('provider_user_id', $request->provider_user_id)->first();

        if ($socialAccount)
            return $this->issueToken($request, 'social');

        $user = User::whereEmail($request->email)
            ->whereNotNull("email")
            ->first();

        if ($user)
            $this->addSocialAccountToUser($request, $user);
        else
            try {
                $this->createUserAccount($request);
            } catch (\Exception $e) {
                return response("An Error Occurred, please retry later", 422);
            }

        return $this->issueToken($request, 'social');
    }

    /**
     * Associate social account to user
     * @param Request $request
     * @param User $user
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function addSocialAccountToUser(Request $request, User $user)
    {
        $this->validate($request, [
            'provider' => [
                'required',
                Rule::unique('social_accounts')
                    ->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                }
            )],

            'provider_user_id' => 'required'
        ]);

        $user->socialAccounts()->create([
            'provider' => $request->provider,
            'provider_user_id' => $request->provider_user_id
        ]);
    }

    /**
     * Create user account and Social account
     * @param  Request $request
     */
    private function createUserAccount(Request $request)
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email
            ]);

            $this->addSocialAccountToUser($request, $user);
        });
    }
}
