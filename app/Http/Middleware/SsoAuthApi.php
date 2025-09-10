<?php

namespace App\Http\Middleware;

use DB;
use Closure;
use App\Models\AuthUser;
use PeterPetrus\Auth\PassportToken;
class SsoAuthApi
{

	/**
	 * The SSO Auth API implementation.
	 * @author Ashish Chauhan.
	 * @var PassportToken
	 */



	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{

        $token = request()->bearerToken();
		if (!$token) {
			return response()->json(['message' => 'Auth token not found.'], 401);
		}

		$tokenRow = PassportToken::dirtyDecode($token);

		if (empty($tokenRow) || empty($tokenRow['user_id'])) {
			return response()->json(['message' => 'Invalid auth token.'], 401);
		}

		$authUser = AuthUser::find($tokenRow['user_id']);
		if (!$authUser) {
			return response()->json(['message' => 'Invalid auth user token.'], 401);
		}
		$dbName = $authUser->db_name;
		$authType = $authUser->user_type;

		if ($authUser->db_name) {
			config(['database.connections.mysql.database' => $dbName]);
			$connect = DB::reconnect('mysql');
		}

        $request->setUserResolver(fn() => $authUser);


		return $next($request);
	}

}
