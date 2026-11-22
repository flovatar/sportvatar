<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Jobs\AddMailSubscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'sportvatar' => 'required|boolean',
            'rcs' => 'required|boolean',
        ]);


        if($validated['sportvatar']) {
            $subscriber = Subscriber::updateOrCreate([
                'email' => $validated['email']
            ],
            [
                'sportvatar' => $validated['sportvatar'],
                'rcs' => $validated['rcs']
            ]);
        }

        AddMailSubscriber::dispatch($subscriber);

        return redirect('/')->with(['subscribed' => true]);
    }

}
