<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Actions\DestructiveAction;
use App\Jobs\SendPack as SendPackJob;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class SendPack extends DestructiveAction
{

    public $name = 'Send Pack';
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $model) {
            if($model->flow_address == config('services.flow.address')) {
                SendPackJob::dispatch($model, $fields->address);
            } else {
                return Action::danger('The pack does not belong to the main account!');
            }
        }
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Text::make('Address')
                ->rules('required'),
        ];
    }
}
