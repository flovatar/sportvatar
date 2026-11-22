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
use App\Jobs\MintPack as MintPackJob;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class MintPackBatch extends DestructiveAction
{

    public $name = 'Mint Pack Batch';
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
            MintPackJob::dispatch($fields->quantity, $fields->flames, $fields->series, $fields->name);
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
            Text::make('Name')
                ->rules('required', 'max:255'),
            Number::make('Series')
                ->min(1)
                ->rules('required'),
            Number::make('Flames')
                ->min(1)
                ->rules('required'),
            Number::make('Quantity')
                ->min(1)
                ->rules('required'),
        ];
    }
}
