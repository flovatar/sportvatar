<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Pack extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Pack::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make('Name')
                ->rules('required', 'max:255'),
            Number::make('Series')
                ->min(1)
                ->rules('required'),
            Number::make('Flame Count')
                ->min(1)
                ->rules('required'),
            Number::make('Flow Id')->sortable(),
            Text::make('Flow Address')
                ->sortable()
                ->rules('max:255'),
            Code::make('Flow Metadata')
                ->hideFromIndex()
                ->json(),
            Text::make('Secret')
                ->hideFromIndex()
                ->rules('max:255'),
            Text::make('Signature')
                ->hideFromIndex()
                ->rules('max:255'),
            Boolean::make('Opened')->sortable(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            (new Actions\SendPack)
                ->confirmText('Are you sure you want to send a Pack to this address?')
                ->confirmButtonText('Yes, send it!')
                ->cancelButtonText("Nope, go back"),
            (new Actions\SignPack)
                ->confirmText('Are you sure you want to sign this Pack?')
                ->confirmButtonText('Yes, sign it!')
                ->cancelButtonText("Nope, go back")
        ];
    }
}
