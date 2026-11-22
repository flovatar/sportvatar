<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class SportvatarTemplate extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\SportvatarTemplate::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name'
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
            ID::make(__('ID'), 'id')
                ->sortable(),
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),
            Select::make('Category')
                ->sortable()
                ->options([
                    'spark' => 'Flame',
                    'boost' => 'Boost',
                    'body' => 'Body',
                    'hair' => 'Hair',
                    'eyes' => 'Eyes',
                    'nose' => 'Nose',
                    'mouth' => 'Mouth',
                    'clothing' => 'Clothing',
                    'facialHair' => 'Facial Hair',
                    'hat' => 'Hat',
                    'eyeglasses' => 'Eyeglasses',
                    'accessory' => 'Accessories',
                    'background' => 'Background'
                ])
                ->rules('required'),
            Select::make('Rarity')
                ->sortable()
                ->options([
                    'common' => 'Common',
                    'rare' => 'Rare',
                    'epic' => 'Epic',
                    'legendary' => 'Legendary'
                ])
                ->rules('required'),
            Text::make('Color')
                ->sortable()
                ->rules('required', 'max:255'),
            Number::make('Series')
                ->min(1)
                ->hideFromIndex()
                ->rules('required'),
            Code::make('Svg')
                ->language('htmlmixed'),
            Textarea::make('Description')
                ->hideFromIndex(),
            Number::make('Max Mintable')
                ->min(1)
                ->hideFromIndex()
                ->rules('required'),
            Number::make('Flow Id')->sortable(),
            Text::make('Flow Address')
                ->hideFromIndex()
                ->rules('max:255'),
            Code::make('Flow Metadata')
                ->hideFromIndex()
                ->json(),
            HasMany::make('Sportvatar Components'),
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
            (new Actions\MintTemplate)
                ->confirmText('Are you sure you want to mint this template?')
                ->confirmButtonText('Yes, mint!')
                ->cancelButtonText("Nope, go back"),
            (new Actions\MintComponent)
                ->confirmText('Are you sure you want to mint a new component from this template?')
                ->confirmButtonText('Yes, mint!')
                ->cancelButtonText("Nope, go back"),
            (new Actions\MintComponentBatch)
                ->confirmText('Are you sure you want to BATCH mint new components from this template?')
                ->confirmButtonText('Yes, mint!')
                ->cancelButtonText("Nope, go back"),
            ];
    }
}
