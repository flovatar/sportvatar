<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use QueueIT\KnownUserV3\SDK\Models;
use QueueIT\KnownUserV3\SDK\KnownUser;
use Storage;

class QueueIt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $configText = Storage::get('queueit-config.json');



        $queueittoken = $request->get('queueittoken', '');

        try
        {
            $fullUrl = $request->fullUrlWithoutQuery(['queueittoken']);
            //info('Queue it URL check '.$fullUrl);

            //Verify if the user has been through the queue
            $result = KnownUser::validateRequestByIntegrationConfig(
                $fullUrl, $queueittoken, $configText, config('queueit.customerID'), config('queueit.secretKey'));

            //info(print_r($result, true));
            if($result->doRedirect())
            {
                //info('DO REDIRECT');
                return redirect()->away($result->redirectUrl);
            }

            if(!empty($queueittoken) && $result->actionType == "Queue")
            {
                $request->request->remove('queueittoken');
            }
        }
        catch(\Exception $e)
        {
            info('Error validating queue it request');
            info(print_r($e, true));
        }




        return $next($request);
    }
}
