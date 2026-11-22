<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Jobs\AddMailSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class SubscriberController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email:rfc|max:255',
            'sportvatar' => 'required|boolean',
            'rcs' => 'required|boolean',
        ]);

        $email = strtolower(trim($validated['email']));
        $sportvatar = (bool) $validated['sportvatar'];
        $rcs = (bool) $validated['rcs'];

        // Nothing to do without at least one consent (this used to fail with an undefined $subscriber).
        if(!$sportvatar && !$rcs){
            return redirect('/')->with(['subscribed' => false]);
        }

        // At most 3 submissions per e-mail address per day, on top of the per-IP route throttle.
        $key = 'subscribe:'.sha1($email);
        if(RateLimiter::tooManyAttempts($key, 3)){
            return response()->json(['message' => 'Too many requests'], 429);
        }
        RateLimiter::hit($key, 86400);

        $subscriber = Subscriber::whereEmail($email)->first();
        $alreadyRcs = $subscriber && $subscriber->rcs;

        $subscriber = Subscriber::updateOrCreate([
            'email' => $email
        ],
        [
            'sportvatar' => $sportvatar || ($subscriber && $subscriber->sportvatar),
            'rcs' => $rcs || $alreadyRcs
        ]);

        // Forward to RCS only when explicitly enabled and only once per address
        // (the RCS call creates an identity account for the address).
        $sendRcs = $rcs && !$alreadyRcs && config('sportvatar.rcsRegistrationEnabled');

        AddMailSubscriber::dispatch($subscriber, $sportvatar, $sendRcs);

        return redirect('/')->with(['subscribed' => true]);
    }

}
