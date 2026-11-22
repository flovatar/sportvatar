<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Http\Requests\NovaRequest;

class Sportbit extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Sportbit::class;

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
     *
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Number::make('Flow Id')->sortable(),
            Text::make('Flow Address')
                ->hideFromIndex()
                ->rules('max:255'),
            Code::make('Flow Metadata')
                ->hideFromIndex()
                ->json(),
            Boolean::make('Destroyed')->sortable(),
            Boolean::make('Packed')->sortable(),
            BelongsTo::make('Sportvatar Template', 'sportvatarTemplate')->sortable()
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
            (new Actions\MintPack)
                ->confirmText('Are you sure you want to mint a new pack?')
                ->confirmButtonText('Yes, mint!')
                ->cancelButtonText("Nope, go back"),
            (new Actions\MintPackBatch)
                ->confirmText('Are you sure you want to BATCH mint new packs?')
                ->confirmButtonText('Yes, mint!')
                ->cancelButtonText("Nope, go back")
        ];
    }
}
