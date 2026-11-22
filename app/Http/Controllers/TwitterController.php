<?php

namespace App\Http\Controllers;

use App\Models\Sportvatar;
use Illuminate\Http\Request;
use Atymic\Twitter\Facade\Twitter;
use Redirect;
use Session;


class TwitterController extends Controller
{

    public function setProfileImage($sportvatarId, $address){

        if (Session::has('oauth_request_token')) {
            $sportvatar = Sportvatar::whereFlowAddress($address)->whereFlowId($sportvatarId)->first();
            if(!$sportvatar){
                return abort(404);
            }

            Twitter::postProfileImage(
                [
                    'image' => base64_encode($sportvatar->png)
                ]
            );

            return Redirect::to('/sportvatars/'.$sportvatarId.'/'.$address);
        } else {
            Session::put('twitter_redirect', request()->url());
            return $this->login();
        }
    }

    public function login() {
        $token = Twitter::getRequestToken(route('twitter.callback'));

        if (isset($token['oauth_token_secret'])) {
            $url = Twitter::getAuthenticateUrl($token['oauth_token']);

            Session::put('oauth_state', 'start');
            Session::put('oauth_request_token', $token['oauth_token']);
            Session::put('oauth_request_token_secret', $token['oauth_token_secret']);

            return Redirect::to($url);
        }

        return Redirect::route('twitter.error');
    }

    public function callback() {
        // You should set this route on your Twitter Application settings as the callback
        // https://apps.twitter.com/app/YOUR-APP-ID/settings
        if (Session::has('oauth_request_token')) {
            $twitter = Twitter::usingCredentials(session('oauth_request_token'), session('oauth_request_token_secret'));
            $token = $twitter->getAccessToken(request('oauth_verifier'));

            if (!isset($token['oauth_token_secret'])) {
                return Redirect::route('twitter.error')->with('flash_error', 'We could not log you in on Twitter.');
            }

            // use new tokens
            $twitter = Twitter::usingCredentials($token['oauth_token'], $token['oauth_token_secret']);
            $credentials = $twitter->getCredentials();

            if (is_object($credentials) && !isset($credentials->error)) {
                // $credentials contains the Twitter user object with all the info about the user.
                // Add here your own user logic, store profiles, create new users on your tables...you name it!
                // Typically you'll want to store at least, user id, name and access tokens
                // if you want to be able to call the API on behalf of your users.

                // This is also the moment to log in your users if you're using Laravel's Auth class
                // Auth::login($user) should do the trick.

                Session::put('access_token', $token);


                if(Session::has('twitter_redirect')){
                    $redirectUrl = session('twitter_redirect');
                    Session::forget('twitter_redirect');
                    return Redirect::to($redirectUrl);
                }
                return Redirect::to('/');
            }
        }

        return Redirect::route('twitter.error')
            ->with('error', 'Crab! Something went wrong while signing you up!');
    }

    public function error() {
        // Something went wrong, add your own error handling here
    }

    public function webhook(){
        if (request()->has('crc_token'))
            return response()->json(['response_token' => Twitter::crcHash(request()->crc_token)], 200);

        // Your webhook logic goes here
    }

}
