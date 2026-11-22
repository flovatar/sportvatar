import Alpine from "alpinejs";
import party from "party-js";

Alpine.data('builderData', (templates, series, flameSales) => ({
    templates: templates,
    series: series,
    flameSales: flameSales,
    components: [],
    name: '',
    showMintBtn: true,
    useOwned: false,
    lastCheckResult: null,
    activeLayer: 2,
    useBasicOnly: true,
    showModalSportvatar: false,
    templateIds: {},
    componentIds: {},
    showAvailableResults: false,
    showSportbits: false,
    showSportvatarCreated: false,
    displayPreviewCss: 'block',
    showOnlyAvailable: false,
    childModeEnabled: false,
    subgroupFilter: 'all',
    checkoutCart: {},
    showCheckoutCompleted: false,
    filterColor: 'all',
    filterSport: 'all',
    showCustomizer: false,
    sportvatarIds: [],
    newSportvatarId: null,
    firstLoad: true,
    customizerParams: {
        'Baseball': [
            {class: 'vest-base-color',name: 'Vest Base Color',value: null},
            {class: 'vest-pinstripes',name: 'Vest Pinstripes',value: null},
            {class: 'neck-stripe',name: 'Neck Stripe',value: null},
            {class: 'Y1',name: 'Y1',value: null},
            {class: 'Y2 path',name: 'Y2',value: null},
            {class: 'Y3 path',name: 'Y3',value: null},
            {class: 'arm-stripe1 path',name: 'Arm Stripe 1',value: null},
            {class: 'arm-stripe2 path',name: 'Arm Stripe 2',value: null},
            {class: 'arm-stripe3 path',name: 'Arm Stripe 3',value: null},
            {class: 'arm-stripe4 path',name: 'Arm Stripe 4',value: null},
            {class: 'sleeves path',name: 'Sleeves',value: null},
            {class: 'team-name path, .team-name polygon',name: 'Team Name',value: null},
            {class: 'legs-base-color',name: 'Legs Base Color',value: null},
            {class: 'shorts-color',name: 'Shorts',value: null},
            {class: 'shorts-stripe-inner',name: 'Shorts Stripe Inner',value: null},
            {class: 'shorts-stripe-outer',name: 'Shorts Stripe Outer',value: null},
            {class: 'shorts-pinstripes',name: 'Shorts Pinstripes',value: null},
            {class: 'legs1 path',name: 'Legs 1',value: null},
            {class: 'legs2 path',name: 'Legs 2',value: null},
            {class: 'legs3 path',name: 'Legs 3',value: null},
            {class: 'legs4 path',name: 'Legs 4',value: null},
            {class: 'legs5 path',name: 'Legs 5',value: null},
            {class: 'belt-color path',name: 'Belt',value: null},
            {class: 'belt-buckle',name: 'Belt Buckle',value: null},
            {class: 'number',name: 'Number',value: null},
            {class: 'shoe-base path',name: 'Shoe Base',value: null},
            {class: 'shoelaces path',name: 'Shoelaces',value: null}
        ],
        'Basketball': [
            {class: 'vest-color',name: 'Vest',value: null},
            {class: 'shorts-color',name: 'Shorts',value: null},
            {class: 'stripe-outer',name: 'Stripe Outer',value: null},
            {class: 'stripe-inner ',name: 'Stripe Inner ',value: null},
            {class: 'arm-band',name: 'Arm Band',value: null},
            {class: 'socks-color',name: 'Socks',value: null},
            {class: 'shoe-brand',name: 'Shoe Brand',value: null},
            {class: 'shoelaces path',name: 'Shoe Laces',value: null},
            {class: 'shoe-base',name: 'Shoe Base',value: null},
            {class: 'shoe-highlight',name: 'Shoe Highlight',value: null},
            {class: 'shoe-sole',name: 'Shoe Sole',value: null},
            {class: 'number',name: 'Number',value: null}
        ],
        'Rugby': [
            {class: 'shirt-color path',name: 'Shirt',value: null},
            {class: 'shirt-two-stripes path',name: 'Shirt Two Stripes',value: null},
            {class: 'shirt-three-stripes path',name: 'Shirt Triple Stripes',value: null},
            {class: 'shirt-four-stripes path',name: 'Shirt Four Stripes',value: null},
            {class: 'shirt-thin-stripes path',name: 'Shirt Thin Stripes',value: null},
            {class: 'shirt-diagonal-stripe path',name: 'Shirt Diagonal Stripe',value: null},
            {class: 'shirt-single-side-stripe-inner',name: 'Shirt Single Stripe',value: null},
            {class: 'shirt-single-side-stripe-outer',name: 'Shirt Single Stripe Outer',value: null},
            {class: 'shirt-blocks path',name: 'Shirt Blocks',value: null},
            {class: 'shirt-stripe-underarm',name: 'Shirt Stripe Underarm',value: null},
            {class: 'shirt-single-center-stripe-inner',name: 'Shirt Single Center Stripe Inner',value: null},
            {class: 'shirt-single-center-stripe-outer',name: 'Shirt Single Center Stripe Outer',value: null},
            {class: 'shirt-horizontal-stripes path',name: 'Shirt Horizontal Stripes',value: null},
            {class: 'shirt-horizontal-pinstripes',name: 'Shirt Horizontal Pinstripes',value: null},
            {class: 'shirt-horizontal-single-stripe',name: 'Shirt Horizontal Single Stripe',value: null},
            {class: 'shirt-stripes-custom',name: 'Shirt Stripes Custom',value: null},
            {class: 'shirt-shoulder-stripe1',name: 'Shirt Shoulder Stripe 1',value: null},
            {class: 'shirt-shoulder-stripe2',name: 'Shirt Shoulder Stripe 2',value: null},
            {class: 'shirt-shoulder-stripe3',name: 'Shirt Shoulder Stripe 3',value: null},
            {class: 'shirt-shoulder-full path',name: 'Shirt Shoulder Full',value: null},
            {class: 'shirt-shoulders-color path',name: 'Shirt Shoulder',value: null},
            {class: 'shirt-sleeve-trim-outer',name: 'Shirt Sleeve Trim Outer',value: null},
            {class: 'shirt-sleeve-trim-inner',name: 'Shirt Sleeve Trim Inner',value: null},
            {class: 'shirt-sleeve-full path',name: 'Shirt Sleeve Full',value: null},
            {class: 'shirt-neck-trim-outer',name: 'Shirt Neck Trim Outer',value: null},
            {class: 'shirt-neck-trim-inner',name: 'Shirt Neck Trim Inner',value: null},
            {class: 'shirt-badge',name: 'Shirt Badge',value: null},
            {class: 'shorts-color path',name: 'Shorts',value: null},
            {class: 'shorts-stripe1',name: 'Shorts Stripe 1',value: null},
            {class: 'shorts-stripe2',name: 'Shorts Stripe 2',value: null},
            {class: 'shorts-stripe3',name: 'Shorts Stripe 3',value: null},
            {class: 'sock-color',name: 'Sock',value: null},
            {class: 'sock-stripe-top',name: 'Sock Stripe',value: null},
            {class: 'sock-stripe-middle',name: 'Sock Stripe Middle',value: null},
            {class: 'sock-stripe-bottom',name: 'Sock Stripe Bottom',value: null},
            {class: '.numbers-shorts .number ',name: 'Number',value: null}
        ],
        'Ice Hockey': [
            {class: 'legs-color path',name: 'Legs',value: null},
            {class: 'leg-stripe-top',name: 'Legs Stripe',value: null},
            {class: 'leg-stripe-middle',name: 'Legs Stripe Middle',value: null},
            {class: 'leg-stripe-bottom',name: 'Legs Stripe Bottom',value: null},
            {class: 'shorts-color',name: 'Shorts',value: null},
            {class: 'shorts-stripe-inner',name: 'Shorts Stripe Inner',value: null},
            {class: 'shorts-stripe-outer',name: 'Shorts Stripe Outer',value: null},
            {class: 'shirt-color',name: 'Shirt',value: null},
            {class: 'shoulder-stripe1',name: 'Shoulder Stripe 1',value: null},
            {class: 'shoulder-stripe2',name: 'Shoulder Stripe 2',value: null},
            {class: 'shoulder-stripe3',name: 'Shoulder Stripe 3',value: null},
            {class: 'shoulder-full',name: 'Shoulder Full',value: null},
            {class: 'shoulder-outer',name: 'Shoulder Outer',value: null},
            {class: 'shoulder-outer-trim',name: 'Shoulder Outer Trim',value: null},
            {class: 'body-stripe1',name: 'Body Stripe 1',value: null},
            {class: 'body-stripe2',name: 'Body Stripe 2',value: null},
            {class: 'body-stripe3',name: 'Body Stripe 3',value: null},
            {class: 'body-stripe4',name: 'Body Stripe 4',value: null},
            {class: 'body-stripe5',name: 'Body Stripe 5',value: null},
            {class: 'body-stripe6',name: 'Body Stripe 6',value: null},
            {class: 'chevron-inner',name: 'Chevron Inner',value: null},
            {class: 'chevron-middle path',name: 'Chevron Middle',value: null},
            {class: 'chevron-outer path',name: 'Chevron Outer',value: null},
            {class: 'chevron-underarm path',name: 'Chevron Underarm',value: null},
            {class: 'sleeves-cuffs path',name: 'Sleeves Cuffs',value: null},
            {class: 'sleeve-stripe1 path',name: 'Sleeve Stripe 1',value: null},
            {class: 'sleeve-stripe2 path',name: 'Sleeve Stripe 2',value: null},
            {class: 'sleeve-stripe3 path',name: 'Sleeve Stripe 3',value: null},
            {class: 'sleeve-stripe4 path',name: 'Sleeve Stripe 4',value: null},
            {class: 'sleeve-stripe5 path',name: 'Sleeve Stripe 5',value: null},
            {class: 'sleeve-stripe6 path',name: 'Sleeve Stripe 6',value: null},
            {class: 'neck-outer',name: 'Neck Outer',value: null},
            {class: 'neck-middle',name: 'Neck Middle',value: null},
            {class: 'neck-inner',name: 'Neck Inner',value: null},
            {class: 'skate-color',name: 'Skate',value: null},
            {class: 'shoelaces',name: 'Shoelaces',value: null},
            {class: 'shirt-badge',name: 'Shirt Badge',value: null},
            {class: 'flag-horizontal-stripes',name: 'Flag Horizontal Stripes',value: null},
            {class: 'flag-vertical-stripes',name: 'Flag Vertical Stripes',value: null},
            {class: 'flag-stripe1',name: 'Flag Stripe 1',value: null},
            {class: 'flag-stripe2',name: 'Flag Stripe 2',value: null},
            {class: 'flag-stripe3',name: 'Flag Stripe 3',value: null},
            {class: 'flag-center-symbol',name: 'Flag Center Symbol',value: null},
            {class: 'numbers-arm .number',name: 'Number',value: null}
        ],
        'Football': [
            {class: 'shirt-color path',name: 'Shirt',value: null, optional: false},
            {class: 'shirt-three-stripes path',name: 'Shirt Triple Stripes',value: null},
            {class: 'shirt-four-stripes path',name: 'Shirt Four Stripes',value: null},
            {class: 'shirt-thin-stripes path',name: 'Shirt Thin Stripes',value: null},
            {class: 'shirt-diagonal-stripe path',name: 'Shirt Diagonal Stripe',value: null},
            {class: 'shirt-single-side-stripe-inner',name: 'Shirt Single Side Stripe Inner',value: null},
            {class: 'shirt-single-side-stripe-outer',name: 'Shirt Single Side Stripe Outer',value: null},
            {class: 'shirt-horizontal-stripes path',name: 'Shirt Horizontal Stripes',value: null},
            {class: 'shirt-blocks path',name: 'Shirt Blocks',value: null},
            {class: 'shirt-stripe-underarm',name: 'Shirt Stripe Underarm',value: null},
            {class: 'shirt-single-center-stripe-inner',name: 'Shirt Single Center Stripe Inner',value: null},
            {class: 'shirt-single-center-stripe-outer',name: 'Shirt Single Center Stripe Outer',value: null},
            {class: 'shirt-horizontal-pinstripes',name: 'Shirt Horizontal Pinstripes',value: null},
            {class: 'shirt-horizontal-single-stripe',name: 'Shirt Horizontal Single Stripe',value: null},
            {class: 'shirt-horizontal-large-stripes path',name: 'Shirt Horizontal Large Stripe',value: null},
            {class: 'shirt-pattern-custom',name: 'Shirt Pattern',value: null},
            {class: 'shirt-shoulder-stripe1',name: 'Shirt Shoulder Stripe 1',value: null},
            {class: 'shirt-shoulder-stripe2',name: 'Shirt Shoulder Stripe 2',value: null},
            {class: 'shirt-shoulder-stripe3',name: 'Shirt Shoulder Stripe 3',value: null},
            {class: 'shirt-shoulder-full path',name: 'Shirt Shoulder Full',value: null},
            {class: 'shirt-sleeve-trim-outer',name: 'Shirt Sleeve Trim Outer',value: null},
            {class: 'shirt-sleeve-trim-inner',name: 'Shirt Sleeve Trim Inner',value: null},
            {class: 'shirt-sleeve-full path',name: 'Shirt Sleeve Full',value: null},
            {class: 'shirt-neck-trim-outer',name: 'Shirt Neck Trim Outer',value: null},
            {class: 'shirt-neck-trim-inner',name: 'Shirt Neck Trim Inner',value: null},
            {class: 'shirt-badge',name: 'Shirt Badge',value: null},
            {class: 'shorts-color path',name: 'Shorts',value: null},
            {class: 'shorts-stripe1',name: 'Shorts Stripe 1',value: null},
            {class: 'shorts-stripe2',name: 'Shorts Stripe 2',value: null},
            {class: 'shorts-stripe3',name: 'Shorts Stripe 3',value: null},
            {class: 'sock-color',name: 'Socks',value: null},
            {class: 'sock-stripe-top',name: 'Sock Stripe Top',value: null},
            {class: 'sock-stripe-middle',name: 'Sock Stripe Middle',value: null},
            {class: 'sock-stripe-bottom',name: 'Sock Stripe Bottom',value: null},
            {class: 'shirt',name: 'Shirt',value: null},
            {class: 'number',name: 'Number',value: null}
        ],
        'Volleyball': [
            {class: 'vest-color',name: 'Vest',value: null},
            {class: 'vest-shoulders-color path',name: 'Vest Shoulders',value: null},
            {class: 'vest-trim-inner path',name: 'Vest Trim Inner',value: null},
            {class: 'vest-trim-outer path',name: 'Vest Trim Outer',value: null},
            {class: 'vest-stripe-inner',name: 'Vest Stripe Inner',value: null},
            {class: 'vest-stripe-outer',name: 'Vest Stripe Outer',value: null},
            {class: 'neck-stripe-inner',name: 'Neck Stripe Inner',value: null},
            {class: 'neck-stripe-outer',name: 'Neck Stripe Outer',value: null},
            {class: 'shorts-color',name: 'Shorts',value: null},
            {class: 'shorts-stripe-inner',name: 'Shorts Stripe Inner',value: null},
            {class: 'shorts-stripe-outer',name: 'Shorts Stripe Outer',value: null},
            {class: 'shorts-trim path',name: 'Shorts Trim',value: null},
            {class: 'sock-color',name: 'Socks',value: null},
            {class: 'sock-stripe-top',name: 'Socks Stripe',value: null},
            {class: 'sock-stripe-middle',name: 'Socks Stripe Middle',value: null},
            {class: 'sock-stripe-bottom',name: 'Socks Stripe Bottom',value: null},
            {class: 'shoe-uppers path',name: 'Shoe Upper',value: null},
            {class: 'shoe-brand',name: 'Shoe Brand',value: null},
            {class: 'shoe-ankle',name: 'Shoe Ankle',value: null},
            {class: 'shoe-toecap',name: 'Shoe Toecap',value: null},
            {class: 'shoe-sole-upper',name: 'Shoe Sole Upper',value: null},
            {class: 'shoe-sole',name: 'Shoe Sole',value: null},
            {class: 'shoelaces path',name: 'Shoelaces',value: null},
            {class: 'numbers-shirt .number',name: 'Number Shirt',value: null},
            {class: 'numbers-shorts .number',name: 'Number Shorts',value: null}
        ],
        'Boxing': [
            {class: 'boot-color path',name: 'Boot',value: null},
            {class: 'boot-trim path',name: 'Boot Trim',value: null},
            {class: 'boxing-color path',name: 'Boxing',value: null},
            {class: 'boxing-trim path',name: 'Boxing Trim',value: null}
        ],
        'Alpine Skiing': [
            {class: 'ski-color',name: 'Ski Color',value: null},
            {class: 'ski-pattern',name: 'Ski Pattern',value: null},
            {class: 'boots-color path',name: 'Boots',value: null},
            {class: 'flag-stripe1',name: 'Flag Stripe 1',value: null},
            {class: 'flag-stripe2',name: 'Flag Stripe 2',value: null},
            {class: 'flag-stripe3',name: 'Flag Stripe 3',value: null},
            {class: 'number',name: 'Number',value: null}
        ],
        'Athletics': [
            {class: 'vest-color',name: 'Vest',value: null},
            {class: 'vest-trim path',name: 'Vest trim',value: null},
            {class: 'number',name: 'Number',value: null},
            {class: 'shoe-color path',name: 'Shoe',value: null},
            {class: 'shoe-tongue',name: 'Shoe Tongue',value: null},
            {class: 'shoe-toecap',name: 'Shoe Toecap',value: null},
            {class: 'shoe-sole',name: 'Shoe Sole',value: null},
            {class: 'shoelaces path',name: 'Shoe Laces',value: null}
        ],
        'American Football': [
            {class: 'vest-color',name: 'Vest',value: null},
            {class: 'shorts-color',name: 'Shorts',value: null},
            {class: 'leggings-color path',name: 'Leggings',value: null},
            {class: 'stripe-inner',name: 'Stripe Inner',value: null},
            {class: 'shorts .stripe-outer',name: 'Stripe Outer',value: null},
            {class: 'neck-stripe',name: 'Neck Stripe',value: null},
            {class: 'stripe1',name: 'Stripe 1',value: null},
            {class: 'stripe2',name: 'Stripe 2',value: null},
            {class: 'stripe3',name: 'Stripe 3',value: null},
            {class: 'stripe4',name: 'Stripe 4',value: null},
            {class: 'shoe-base path',name: 'Shoe Base',value: null},
            {class: 'shoelaces path',name: 'Shoe Laces',value: null},
            {class: 'number',name: 'Number',value: null}
        ],
        'Cricket': [
            {class: 'shirt-base-color',name: 'Shirt',value: null},
            {class: 'shirt-chest-color',name: 'Shirt Chest',value: null},
            {class: 'shoulder-stripes path',name: 'Shoulder Stripes',value: null},
            {class: 'shirt-chest-stripe path',name: 'Shirt Chest Stripe',value: null},
            {class: 'shirt-shoulders path',name: 'Shirt Shoulders',value: null},
            {class: 'shirt-underarm',name: 'Shirt Underarm',value: null},
            {class: 'shirt-body-side-stripe',name: 'Shirt Side Stripe',value: null},
            {class: 'sleeve-stripe-inner path',name: 'Sleeve Stripe Inner',value: null},
            {class: 'sleeve-stripe-outer path',name: 'Sleeve Stripe Outer',value: null},
            {class: 'shirt-collars path',name: 'Shirt Collars',value: null},
            {class: 'arm-band-color',name: 'Arm Band',value: null},
            {class: 'trousers-color',name: 'Trousers',value: null},
            {class: 'trouser-stripe-inner',name: 'Trouser Stripe',value: null},
            {class: 'trouser-stripe-outer',name: 'Trouser Stripe Outer',value: null}
        ],
        'Cycling': [
            {class: 'vest-color',name: 'Vest',value: null},
            {class: 'vest-trim path',name: 'Vest Trim',value: null},
            {class: 'vest-side-stripe',name: 'Vest Side Stripe',value: null},
            {class: 'shoulder-trim path',name: 'Shoulder Trim',value: null},
            {class: 'shoulder-color path',name: 'Shoulder Color',value: null},
            {class: 'neck-trim',name: 'Neck Trim',value: null},
            {class: 'sleeve-stripes',name: 'Sleeves Stripes',value: null},
            {class: 'sleeve-labels',name: 'Sleeves Labels',value: null},
            {class: 'chest-horizontal-stripe',name: 'Chest Horizontal Stripe',value: null},
            {class: 'chest-vertical-stripe',name: 'Chest Vertical Stripe',value: null},
            {class: 'pattern-custom path, .pattern-custom ellipse',name: 'Pattern',value: null},
            {class: 'shorts-color',name: 'Shorts',value: null},
            {class: 'shorts-sides path',name: 'Shorts Sides',value: null},
            {class: 'shorts-trim path',name: 'Shorts Trim',value: null},
            {class: 'socks-color',name: 'Socks',value: null},
            {class: 'badge',name: 'Badge',value: null},
            {class: 'shoe-color',name: 'Shoe',value: null},
            {class: 'shoelaces',name: 'Shoe Laces',value: null},
            {class: 'sole',name: 'Sole',value: null}
        ]
    },
    init(){
        let self = this;
        this.loadHashParams();


        if(this.series[0] && this.series[0].layers && this.series[0].layers.length > 2){
            self.activeLayer = this.series[0].layers[2].id;
        }

        this.$watch('templateIds', () => { self.loadCombination(); });

        this.$watch('customizerParams', () => { console.log(self.customizerParams); });

        this.loadCombination();

    },
    getNumberName(template){
        return template ? template.name.replace('Number ', '') : 'N/A';
    },
    showCustomizerSport(sport){
        let tempName = this.getTempName(this.activeLayer);
        return tempName.includes(sport);
    },
    customizerActiveSportName(){
        let tempName = this.getTempName(this.activeLayer);
        let res = _.findKey(this.customizerParams, (val, key) => {
            return tempName.includes(key);
        });
        return res ? res : null;
    },
    customizerParamsList(){
        return this.customizerActiveSportName() ?  this.customizerParams[this.customizerActiveSportName()] : [];
    },
    showCustomizerButton(){
        if(this.showCustomizer){
            return false;
        }
        let custSport = this.customizerActiveSportName();
        return custSport ? true : false;
    },

    getCurrentSeries(){
        return this.series.length > 0 ? this.series[0] : null;
    },
    seriesLayers(){
        return this.series[0] ? this.series[0].layers : [];
    },
    traitsLayers(){
        return _.filter(this.seriesLayers(), {isAccessory: false});
    },
    traitsTemplatesIds(){
        let self = this;
        let traitsTemplates = {};
        _.each(this.traitsLayers(), layer => {
            if(self.templateIds[layer.id]){
                traitsTemplates[layer.id] = self.templateIds[layer.id];
            }
        });
        return traitsTemplates;
    },
    sportbitTemplateIds(){
        let self = this;
        let traitsTemplates = [];
        _.each(this.sportbitLayers(), layer => {
            if(self.templateIds[layer.id] && self.templateIds[layer.id] != '0'){
                let comp = _.find(this.components, (item) => { return parseInt(item.templateId,10) == self.templateIds[layer.id];});
                if(comp){
                    traitsTemplates.push(comp.id);
                }
            }
        });
        return traitsTemplates;
    },
    layersTemplatesIds(){
        let self = this;
        let traitsTemplates = {};
        _.each(this.seriesLayers(), layer => {
            if(self.templateIds[layer.id] && self.templateIds[layer.id] != '0'){
                traitsTemplates[layer.id] = self.templateIds[layer.id];
            } else {
                traitsTemplates[layer.id] = null;
            }
        });
        return traitsTemplates;
    },
    sportbitLayers(){
        return _.filter(this.seriesLayers(), {isAccessory: true});
    },
    visibleSportbitLayers(){
        let self = this;
        return _.filter(this.sportbitLayers(), (item) => {return !self.hideEmptyLayer(item);});
    },
    getActiveLayer(){
        let self = this;
        return this.activeLayer ? _.find(this.seriesLayers(), {id: self.activeLayer}) : null;
    },
    showLayerFilter(){
        let layer = this.getActiveLayer();
        return (layer && (layer.name == 'hair' || layer.name == 'facialHair' || layer.name == 'clothing')) ? true : false;
    },
    showColorFilter(){
        let layer = this.getActiveLayer();
        return (layer && (layer.name == 'hair' || layer.name == 'facialHair')) ? true : false;
    },
    showSportFilter(){
        let layer = this.getActiveLayer();
        return (layer && (layer.name == 'clothing')) ? true : false;
    },
    getCurrentRarity(){
        let rarity = 'common';
        if(this.rarityCount('rare') > 0){
            rarity = 'rare';
        }
        if(this.rarityCount('epic') > 0){
            rarity = 'epic';
        }
        if(this.rarityCount('legendary') > 0){
            rarity = 'legendary';
        }
        return rarity;
    },
    getCurrentRarityClass(){
        return this.getCurrentRarity();
    },
    getTemplateIdByCatRarity(category, rarity){
        let template = _.find(this.templates, function(temp){ return temp.category == category && temp.rarity == rarity; });
        return template ? template.flow_id : null;
    },
    builderScoreClass(){
        if(this.rarityCount('legendary') > 0){
            return 'legendary';
        } else if(this.rarityCount('epic') > 0){
            return 'epic';
        } else if(this.rarityCount('rare') > 0){
            return 'rare';
        }
        return 'common';
    },
    getTempUrl(layer){
        let temp = _.find(this.templates, { 'flow_id': parseInt(this.templateIds[layer],10)});
        return (temp) ? '/api/image/template/'+temp.flow_id : '';
    },
    getTempName(layer){
        if(this.templateIds[layer]) {
            let temp = _.find(this.templates, { 'flow_id': parseInt(this.templateIds[layer],10)});
            return temp ? temp.name : 'N/A';
        }
        return 'N/A';
    },
    getTempRarity(layer){
        if(this.templateIds[layer]) {
            let temp = _.find(this.templates, { 'flow_id': parseInt(this.templateIds[layer], 10)});
            let maxMint = (temp && this.isLayerOptional(layer)) ? ' (max. '+temp.max_mintable+')' : '';
            return temp ? temp.rarity+'<small>'+maxMint+'</small>' : 'N/A';
        }
        return 'N/A';
    },
    getTempBurned(layer){
        if(!this.isLayerSportbit(layer) && this.templateIds[layer]) {
            let temp = _.find(this.templates, { 'flow_id': parseInt(this.templateIds[layer],10)});
            return temp ? temp.minted : 0;
        }
        return 'N/A';
    },
    getTempMaxMintable(layer){
        if(!this.isLayerSportbit(layer) && this.templateIds[layer]) {
            let temp = _.find(this.templates, { 'flow_id': parseInt(this.templateIds[layer],10)});
            return temp ? temp.max_mintable : 0;
        }
        return 'N/A';
    },
    getLayerClass(layer){
        let output = this.getTempRarityClass(layer.id);
        output += (this.activeLayer == layer.id) ? ' active' : '';
        return output;
    },
    getTempRarityClass(layer){
        if(this.templateIds[layer]) {
            let temp = _.find(this.templates, { 'flow_id': parseInt(this.templateIds[layer],10)});
            return temp ? temp.rarity : '';
        }
        return '';
    },
    infoBoxClass(){
        let output = this.getTempRarityClass(this.activeLayer);
        //output += this.showUnavailable() ? ' border-red' : '';
        return output;
    },
    loadHashParams(){
        let self = this;
        if(window.location.hash && window.location.hash != '#') {
            let hash = window.location.hash;
            hash = hash.replace('#', '');

            self.templateIds = window.getLayersFromCombination(hash);
            //console.log('load from hash');
            //console.log(self.templateIds);

        } else {
            this.initCombination();
        }
    },
    getCombination(templateIds){
        if(Object.keys(templateIds).length == 0){
            return null;
        } else {
            let combination = 'S1';
            _.each(this.templateIds, function(val, key){
                let valString = val ? val : '0';
                combination += '-L'+key+'_'+valString;
            });
            return combination;
        }
    },
    loadCombination(){
        let combination = this.getCombination(this.templateIds);
        if(combination && window.location.hash != '#'+combination){
            window.history.pushState({}, null, '#'+combination);
        }
        this.checkAvailabilityClick();
    },
    selectActiveLayer(layer) {
        this.activeLayer = layer.id;
        this.filterColor = 'all';
        this.filterSport = 'all';
        this.showCustomizer = false;
    },
    isLayerOptional(layer){
        let findLayer = _.find(this.seriesLayers(), (item) => { return item.isAccessory == true && item.flow_id == layer;});
        return findLayer ? true : false;
    },
    checkAvailabilityClick(){
        let series = this.getCurrentSeries();
        if(series && this.traitsTemplatesIds()){
            window.checkSportvatarAvailability(series.flow_id, this.traitsTemplatesIds());
        }
    },
    getOptionalSportbit(category) {
        return (this.templateIds[category] && this.componentIds[category]) ? this.componentIds[category] : null;
    },
    createSportvatarClick(){
        if(!(this.showMintBtn && this.hasAllComponents())){
            return false;
        }
        let series = this.getCurrentSeries();
        let flame = this.getFlame();
        if(series && this.traitsTemplatesIds() && flame){
            window.createSportvatar(series.flow_id, this.layersTemplatesIds(), flame.id);
        }
    },
    createSportvatarWithSportbitClick(){
        if(!(this.showMintBtn && this.hasAllComponents())){
            return false;
        }
        let series = this.getCurrentSeries();
        let flame = this.getFlame();
        if(series && this.traitsTemplatesIds() && flame && this.sportbitTemplateIds()){
            window.createSportvatarWithSportbit(series.flow_id, this.layersTemplatesIds(), flame.id, this.sportbitTemplateIds());
        }
    },
    getFlame(){
        let self = this;
        let rarity = this.getCurrentRarity();
        let flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == rarity; });

        if(!flameComp){
            flameComp =  self.getLowestFlame(rarity);
        } else if(Array.isArray(flameComp)){
            if(flameComp.length == 0){
                flameComp =  self.getLowestFlame(rarity);
            }
        }
        return (flameComp && Array.isArray(flameComp) && flameComp.length > 0) ? flameComp[0] : null;
    },
    getFlameRarity(){
        return this.getFlame() ? this.getFlame().rarity : null;
    },
    getAbilityScore(){
        if(this.getFlameRarity() == 'common'){
            return '0.5-2.0'
        } else if(this.getFlameRarity() == 'rare'){
            return '2.0-3.0'
        } else if(this.getFlameRarity() == 'epic'){
            return '3.0-4.0'
        } else if(this.getFlameRarity() == 'legendary'){
            return '4.0-5.0'
        }
        return '';
    },
    getFlamePrice(){
        return this.getFlameSale() ? '$'+window.formatPrice(this.getFlameSale().price) : 'N/A';
    },
    buyFlameSale(){
        let sale = this.getFlameSale();
        if(sale && this.loggedIn()){
            console.log('buy flame');
            console.log(sale);
            window.buyComponentSale(sale.flow_address, sale.listing_id, parseFloat(sale.price));
        } else if(!this.loggedIn()){
            this.connectWallet();
        }
    },
    getFlameSale(){
        if(this.builderScoreClass() && this.flameSales){
            return this.flameSales[this.builderScoreClass()];
        }
        return null;
    },
    getFlameCount(rarity){
        let self = this;
        let flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == rarity; });
        if(!flameComp){
            flameComp =  self.getLowestFlame(rarity);
        } else if(Array.isArray(flameComp)){
            if(flameComp.length == 0){
                flameComp =  self.getLowestFlame(rarity);
            }
        }
        return (flameComp && Array.isArray(flameComp)) ? flameComp.length : 0;
    },
    getLowestFlame(rarity){
        let flameComp = null;
        let self = this;
        if(rarity == 'common'){
            flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'common'; });
            if(!flameComp || (Array.isArray(flameComp) && flameComp.length == 0)) {
                flameComp = _.filter(this.components, function (comp) { return self.isFlame(comp) && comp.rarity == 'rare'; });
                if (!flameComp || (Array.isArray(flameComp) && flameComp.length == 0)) {
                    flameComp = _.filter(this.components, function (comp) { return self.isFlame(comp) && comp.rarity == 'epic'; });
                    if (!flameComp || (Array.isArray(flameComp) && flameComp.length == 0)) {
                        flameComp = _.filter(this.components, function (comp) { return self.isFlame(comp) && comp.rarity == 'legendary'; });
                    }
                }
            }
        } else if(rarity == 'rare'){
            flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'rare'; });
            if(!flameComp || (Array.isArray(flameComp) && flameComp.length == 0)){
                flameComp = _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'epic'; });
                if(!flameComp || (Array.isArray(flameComp) && flameComp.length == 0)){
                    flameComp = _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'legendary'; });
                }
            }
        } else if(rarity == 'epic'){
            flameComp = _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'epic'; });
            if(!flameComp || (Array.isArray(flameComp) && flameComp.length == 0)){
                flameComp = _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'legendary'; });
            }
        } else if(rarity == 'legendary'){
            flameComp = _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'legendary'; });
        }
        return flameComp;
    },
    getLowestFlameRarity(rarity){
        let flames = this.getLowestFlame(rarity);
        return flames && flames.length > 0 ? flames[0] : null;
    },
    getHighestFlame(){
        let self = this;
        let flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'legendary'; });
        if(flameComp && flameComp.length > 0){
            return 'legendary';
        } else {
            flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'epic'; });
            if(flameComp && flameComp.length > 0){
                return 'epic';
            } else {
                flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'rare'; });
                if(flameComp && flameComp.length > 0){
                    return 'rare';
                } else {
                    flameComp =  _.filter(this.components, function(comp){ return self.isFlame(comp) && comp.rarity == 'common'; });
                    if(flameComp && flameComp.length > 0){
                        return 'common';
                    }
                }
            }
        }
        return '';
    },
    isFlame(component){
        return component.layer == '0' && component.name == 'Sport Flame';
    },
    isRarityEnough(comp, rarity){
        if(rarity == 'legendary'){
            return comp.rarity == 'legendary';
        } else if(rarity == 'epic'){
            return comp.rarity == 'legendary' || comp.rarity == 'epic';
        } else if(rarity == 'rare'){
            return comp.rarity == 'legendary' || comp.rarity == 'epic'  || comp.rarity == 'rare';
        }
        return  true
    },

    isTraitAvailable(rarity){
        return this.isRaritySufficient(rarity, this.getHighestFlame());
    },

    getTemplateId(id){
        let temp = this.getTemplate(id);
        return temp ? temp.flow_id : null;
    },
    getTemplate(id){
        let comp = _.find(this.components, function(item) { return item.id == id; });
        return (comp) ? _.find(this.templates, { 'flow_id': parseInt(comp.templateId, 10)}) : null;
    },
    filterTemp(layer){
        let templates = [];
        let self = this;

        templates = _.filter(this.templates, function(temp){

            if(temp.layer != layer){
                return false;
            } else if(self.filterColor != 'all'){

                if(self.filterColor == 'Other'){
                    return temp.name && !temp.name.includes('Black') && !temp.name.includes('Blonde') && !temp.name.includes('Brown');
                } else {
                    return temp.name && temp.name.includes(self.filterColor);
                }
            } else if(self.filterSport != 'all'){

                let sport = self.filterSport;
                if(sport == 'American Football' || sport == 'Basketball'){
                    return temp.name && temp.name.includes(sport);
                } else if(sport == 'Football'){
                    return temp.name && temp.name.includes(sport) && !temp.name.includes('American Football');
                } else if(sport == 'Combat') {
                    return temp.name && (temp.name.includes('Boxing') || temp.name.includes('MMA') || temp.name.includes('Wrestling') || temp.name.includes('Martial Arts'));
                } else if(sport == 'Motorsports') {
                    return temp.name && (temp.name.includes('Moto Racing') || temp.name.includes('Auto Racing'));
                } else if(sport == 'Summer') {
                    return temp.name && (temp.name.includes('Athletics') || temp.name.includes('Skateboarding') || temp.name.includes('Gymnastic') || temp.name.includes('Tennis')
                        || temp.name.includes('Swimming') || temp.name.includes('Padel') || temp.name.includes('Fencing') || temp.name.includes('Cycling') || temp.name.includes('Climbing')
                        || temp.name.includes('Sailing') || temp.name.includes('Equestrian') || temp.name.includes('Golf'));
                } else if(sport == 'Team') {
                    return temp.name && (temp.name.includes('Cricket') || temp.name.includes('Volleyball') || temp.name.includes('Rugby') || temp.name.includes('Baseball') || temp.name.includes('Ice Hockey'));
                } else if(sport == 'Winter') {
                    return temp.name && (temp.name.includes('Alpine Skiing') || temp.name.includes('Snowboarding') || temp.name.includes('Figure Skating') || temp.name.includes('Curling')
                        || temp.name.includes('Bobsleigh'));
                } else if(sport == 'Other') {
                    return temp.name && (temp.name.includes('Style') || temp.name.includes('Fishing') || temp.name.includes('Scuba') || temp.name.includes('Fitness')
                        || temp.name.includes('Referee') || temp.name.includes('Running'));
                } else {
                    return false;
                }
            } else {
                return true;
            }
        });


        return templates.sort(function (l,r){
            let lRarity = self.getRarityByName(l.rarity);
            let rRarity = self.getRarityByName(r.rarity);

            if(r.layer == 6 && r.name == 'None'){
                return 1;
            }if(l.layer == 6 && l.name == 'None'){
                return -1;
            }
            if(l.rarity == r.rarity){
                if(l.name > r.name){
                    return 1;
                } else if(l.name < r.name){
                    return -1;
                } else {
                    return 0;
                }
            } else {
                if(lRarity.order > rRarity.order){
                    return 1;
                } else {
                    return -1;
                }
            }
        });
    },
    countFilteredTemp(layer){
        let templates = _.filter(this.templates, function(temp){
            return temp.layer == layer.id;
        });
        return templates.length;
    },
    hideEmptyLayer(layer){
        return this.countFilteredTemp(layer) == 0 ? true : false;
    },
    randomComp(category){
        let filterList = this.filterComp(category);

        if(!filterList){
            return null;
        }
        let sample = _.sample(filterList);
        return sample ? sample.id : null;
    },
    copyCss(){
        navigator.permissions.query({name: "clipboard-write"}).then((result) => {
            if (result.state === "granted" || result.state === "prompt") {
                if(navigator.clipboard){
                    //$clipboard(getCustomizerCss())

                    navigator.clipboard.writeText(this.getCustomizerCss()).then(function() {
                        console.log("Copied to clipboard successfully!");
                    }, function() {
                        console.error("Unable to write to clipboard. :-(");
                    });
                }
            }
        });
    },

    isRaritySufficient(rarityTrait, rarityFlame){
        if(rarityFlame == 'legendary'){
            return true;
        } else if(rarityFlame == 'epic' && (rarityTrait == 'epic' || rarityTrait == 'rare' || rarityTrait == 'common')){
            return true;
        } else if(rarityFlame == 'rare' && (rarityTrait == 'rare' || rarityTrait == 'common')){
            return true;
        } else if(rarityFlame == 'common' && rarityTrait == 'common'){
            return true;
        }
        return false;
    },
    randomTemp(category){
        let filterList = this.filterTemp(category);
        let self = this;

        if(!filterList){
            return null;
        }

        if(this.loggedIn() && this.getHighestFlame()){
            let highestFlameRarity = self.getHighestFlame();
            filterList = _.filter(filterList, item => {
                return self.isRaritySufficient(item.rarity, highestFlameRarity);
            });
        }

        let sample = _.sample(filterList);
        return sample ? sample.flow_id : null;
    },
    getFullSvg(){
        let svg = ''
        //console.log(this.seriesLayers());
        _.each(this.seriesLayers(), (layer) => {
            //console.log(layer);
            if(this.templateIds[layer.id]) {
                let temp = _.find(this.templates, {'flow_id': parseInt(this.templateIds[layer.id],10)});
                svg += temp ? temp.svg : '';
            }
        });

        //let svgOpen = this.getCurrentSeries() ? this.getCurrentSeries().svg_prefix : window.svgOpen;
        //let svgClose = this.getCurrentSeries() ? this.getCurrentSeries().svg_suffix : window.svgClose;
        //return svgOpen + svg + svgClose;

        svg += this.getCustomizerCss();
        svg = window.wrapSvg(svg);

        return svg;

    },
    getCustomizerCss(){
        if(this.showCustomizer){
            let svg = '<defs><style>';
            _.each(this.customizerParamsList(), (param) => {
                if(param.value) {
                    svg += ' .' + param.class + ' { fill: ' + param.value + ' !important; display: block; } ';
                }
            });
            svg += '</style></defs>';

            return svg;
        }
        return '';
    },
    getLayerName(layerId){
        //l(this.seriesLayers());
        let layer = _.find(this.seriesLayers(), item => {
            return item.id == layerId;
        });
        return layer ? layer.name : 'Layer '+layerId;
    },
    handleCheckResults(data){
        this.lastCheckResult = data;
        this.showMintBtn = data.result;
        this.showAvailableResults = true;
    },
    shotConfetti(){
        party.confetti(document.getElementById('sportvatar-builder-box'), {
            count: party.variation.range(70, 100),
            size: party.variation.range(0.6, 1.4),
            spread: party.variation.range(10, 60),
        });
    },
    showConfetti(){
        let self = this;
        self.shotConfetti();
        setTimeout(function(){ self.shotConfetti(); }, 500);
        setTimeout(function(){ self.shotConfetti(); }, 1000);
        setTimeout(function(){ self.shotConfetti(); }, 2000);
        setTimeout(function(){ self.shotConfetti(); }, 3000);
    },
    handleSportvatarCreated(){
        //alert('Woohoo! You created a brand new unique Sportvatar. Check it out in your collection');
        let self = this;
        setTimeout(function(){
            window.getSportvatarIds(self.address());
        },1000);

        //Defer these to after checking the Sportvatar ID
        //this.showConfetti();
        //this.showSportvatarCreated = true;
    },
    loadComponents(components){
        this.components = components;
    },
    loadSportvatarIds(sportvatarIds){

        if(!this.firstLoad){
            let oldIds = [];
            _.each(this.sportvatarIds, item => {
                oldIds.push(parseInt(item,10));
            });
            let newIds = [];
            _.each(sportvatarIds.sportvatars, item => {
                newIds.push(parseInt(item,10));
            });
            let maxOldVal = _.max(oldIds);
            let maxNewVal = _.max(newIds);

            if(!maxOldVal || maxNewVal > maxOldVal) {
                this.newSportvatarId = maxNewVal;
            } else {
                this.newSportvatarId = null;
            }

            this.showConfetti();
            this.showSportvatarCreated = true;
        }
        this.firstLoad = false;
        this.sportvatarIds = sportvatarIds.sportvatars;
    },
    initCombination(){
        _.each(this.seriesLayers(), (category) => {
            if(category.isAccessory){
                this.templateIds[category.id] = null;
                this.componentIds[category.id] = null;
            } else {
                let rand = Math.random();
                this.templateIds[category.id] = (category.id == 6 && rand < 0.7) ? this.getNoFacialId() : this.randomTemp(category.id);

                let comp = _.find(this.components, {templateId: this.templateIds[category]});
                this.componentIds[category.id] = (!comp) ? null : comp.id;
            }
        });
    },
    getNoFacialId(){
        let filterList = this.filterTemp(6);

        if(!filterList){
            return null;
        }
        let res = _.find(filterList, {name: 'None', rarity: 'common'});
        return res ? res.flow_id : null;

    },
    randomizeCombination(){
        //console.log('randomize combination');
        this.filterColor = 'all';
        this.filterSport = 'all';
        _.each(this.seriesLayers(), (category) => {
            if(category.isAccessory){
                this.templateIds[category.id] = null;
                this.componentIds[category.id] = null;
            } else {
                let rand = Math.random();
                this.templateIds[category.id] = (category.id == 6 && rand < 0.7) ? this.getNoFacialId() : this.randomTemp(category.id);

                let comp = _.find(this.components, {templateId: this.templateIds[category]});
                this.componentIds[category.id] = (!comp) ? null : comp.id;
            }
        });
    },
    rarityCount(rarity){
        let rareCount = 0;
        let self = this;

        _.each(this.traitsLayers(), (category) => {
            let template = _.find(this.templates, function(item){ return item.flow_id == parseInt(self.templateIds[category.id],10);});
            if(template && template.rarity == rarity){
                rareCount++;
            }
        });

        return rareCount;
    },
    rarityFullCount(rarity){
        return this.rarityCount(rarity)+'/'+this.filterRarityComp(rarity).length;
    },
    rarityCountColor(rarity){
        return (this.rarityCount(rarity) <= this.filterRarityComp(rarity).length) ? '' : 'empty';
    },
    flameCountColor(){
        return (this.getSparkCount() > 0) ? 'text-green-500' : 'text-red-400';
    },
    hasAllComponents(){
        return this.getFlame();
    },
    cssClassCategory(category){
        let css = '';
        if(category == this.activeLayer){
            css += ' ring-2 ring-offset-2 ring-indigo-500';
        }
        if(this.filterComp(category).length > 0 && (this.isLayerOptional(category) || this.componentIds[category])){
            if(this.isLayerOptional(category)){
                if(this.componentIds[category]) {
                    // All good to do
                    css += ' bg-indigo-400 border-transparent text-white hover:bg-indigo-500';
                }
            } else {

                css += ' bg-indigo-600 border-transparent text-white hover:bg-indigo-700';
            }
        } else {
            css += ' bg-white border-gray-200 text-gray-900 hover:bg-gray-50';
        }
        return css;
    },
    cssClassTemplate(templateId){
        let css = '';
        if(templateId == this.templateIds[this.activeLayer]){
            css += ' ring-2 ring-offset-2 ring-indigo-500';
        }
        let comp = _.find(this.components, {templateId: templateId});
        if(comp){
            css += ' bg-indigo-600 border-transparent text-white hover:bg-indigo-700';
        } else {
            css += ' bg-white border-gray-200 text-gray-900 hover:bg-gray-50';
        }
        return css;
    },
    selectComponent(templateId){
        let comp = _.find(this.components, (item) => { return parseInt(item.templateId,10) == parseInt(templateId,10);});
        this.componentIds[this.activeLayer] = comp ? comp.id : null;
        this.templateIds[this.activeLayer] = templateId;
        //this.showMintBtn = false;
    },
    isLayerSportbit(layer){
        let findLayer = _.find(this.seriesLayers(), (item) => { return item.isAccessory == true && item.id == layer;});
        return findLayer ? true : false;
    },
    getComponentClass(template){
        let cssClass = (template.flow_id === this.templateIds[this.activeLayer]) ? 'active ' : '';
        let bgClass = template.rarity+' ';
        let opacityClass = '';
        if(this.isLayerSportbit(this.activeLayer) && this.profile){
            let comp = _.find(this.components, (item) => { return parseInt(item.templateId,10) == template.flow_id;});
            if(!comp){
                opacityClass = 'unavailable ';
            }
        } else if(!this.isLayerSportbit(this.activeLayer) && this.profile){
            if(!this.isTraitAvailable(template.rarity) && this.getHighestFlame()){
                opacityClass = 'unavailable ';
            }
        }
        return cssClass + bgClass + opacityClass;
    },
    getComponentParentClass(template){
        if(this.profile && this.showOnlyAvailable){
            if(this.isLayerSportbit(this.activeLayer)){
                let comp = _.find(this.components, (item) => { return parseInt(item.templateId,10) == template.flow_id;});
                if(!comp){
                    return 'hide-unavailable';
                }
            } else {
                if(template.rarity !== 'common' && this.filterRarityComp(template.rarity).length <= 0){
                    return 'hide-unavailable';
                }
            }
        }
        return '';
    },
    saveSvg() {
        let svg = '';
        let self = this;
        _.each(this.layersTemplatesIds(), function(templateId){
            if(templateId) {
                let temp = _.find(self.templates, { 'flow_id': parseInt(templateId,10)});
                svg += temp ? temp.svg : '';
            }
        });
        svg += this.getCustomizerCss();
        svg += window.watermarkSvg;
        svg = window.wrapSvg(svg);
        svg = svg.replace('width="100%"', 'width="3000"').replace('height="100%"', 'height="3000"');
        const base64 = window.btoa(unescape(encodeURIComponent(svg)));
        const src = "data:image/svg+xml;base64," + base64;

        const evt = new MouseEvent("click", {
            view: window,
            bubbles: false,
            cancelable: true
        });
        const a = document.createElement("a");
        a.setAttribute("download", `sportvatar-${ (new Date().getTime()).toString(10) }.svg`);
        a.setAttribute("href", src);
        a.setAttribute("target", '_blank');
        a.dispatchEvent(evt);
        a.remove();
    },
    savePng() {
        let svg = '';
        let self = this;
        _.each(this.layersTemplatesIds(), function(templateId){
            if(templateId) {
                let temp = _.find(self.templates, { 'flow_id': parseInt(templateId,10)});
                svg += temp ? temp.svg : '';
            }
        });
        svg += window.watermarkSvg;
        svg += this.getCustomizerCss();
        svg = window.wrapSvg(svg);
        const img = new Image(3000, 3000);
        const canvas = document.createElement("canvas");
        canvas.width = 3000;
        canvas.height = 3000;
        const ctx = canvas.getContext("2d");
        svg = svg.replace('width="100%"', 'width="3000"').replace('height="100%"', 'height="3000"');
        const base64 = window.btoa(unescape(encodeURIComponent(svg)));
        const src = "data:image/svg+xml;base64," + base64;

        img.onload = () => {
            ctx.drawImage(img, 0, 0, 3000, 3000);

            let imgURI = canvas
                .toDataURL("image/png")
                .replace("image/png", "image/octet-stream");

            const evt = new MouseEvent("click", {
                view: window,
                bubbles: false,
                cancelable: true
            });
            const a = document.createElement("a");
            a.setAttribute("download", `sportvatar-${ (new Date().getTime()).toString(10) }.png`);
            a.setAttribute("href", imgURI);
            a.setAttribute("target", '_blank');
            a.dispatchEvent(evt);
            canvas.remove();
            a.remove();
            img.remove();
        }
        img.src = src;
    }
}));

window.initBuilder = function(dispatch){
    window.dispatch = dispatch;
}
