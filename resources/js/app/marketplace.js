import Alpine from "alpinejs";

Alpine.data('marketplaceData', (templates, stats) => ({
    sales: null,
    stats: stats,
    templates: templates,
    nextPage: 1,
    totalResults: null,
    minPrice: null,
    maxPrice: null,
    sortBy: 'datenew',
    searchCat: 'all',
    searchRarity: 'all',
    searchCat2: 'all',
    searchTemplate: 'all',
    searchCurrency: 'all',
    showFilterPanel: false,
    init(){
        let self = this;
        this.loadSearchParams();

        this.$watch('sortBy', () => { self.searchResults(); });
        this.$watch('searchCat', () => {
            self.searchCat2 = 'all';
            self.searchTemplate = 'all';
            if(self.searchCat == 'spark'){
                self.searchRarity = 'all';
            } else if(self.searchCat == 'boost' && self.searchRarity == 'common'){
                self.searchRarity = 'all';
            }
            self.searchResults();
        });
        this.$watch('searchRarity', () => { self.searchResults(); });
        this.$watch('searchCurrency', () => { self.searchResults(); });
        this.$watch('searchCat2', () => { self.searchTemplate = 'all'; self.searchResults(); });
        this.$watch('searchTemplate', () => {
            if(this.searchTemplate != 'all'){
                this.searchRarity = 'all';
            }
            self.searchResults();
        });


        this.searchResults();

    },
    resetFilters(){
        this.minPrice = null;
        this.maxPrice = null;
        this.sortBy = 'datenew';
        this.searchCat = 'all';
        this.searchRarity = 'all';
        this.searchCat2 = 'all';
        this.searchTemplate = 'all';
        this.searchCurrency = 'all';
    },
    loadSearchParams(){
        let self = this;
        if(window.location.hash) {
            let hash = window.location.hash;
            hash = hash.replace('#', '');
            let params = _.split(hash, '&');
            _.each(params, function(param){
                let paramArr = _.split(param, '=');
                if(paramArr.length == 2){
                    if(paramArr[0] == 'templatecat'){
                        self.searchCat = paramArr[1];
                    } else if(paramArr[0] == 'sportbitcat'){
                        self.searchCat2 = paramArr[1];
                    } else if(paramArr[0] == 'templateid'){
                        self.searchTemplate = paramArr[1];
                    } else if(paramArr[0] == 'currency'){
                        self.searchCurrency = paramArr[1];
                    } else if(paramArr[0] == 'rarity'){
                        self.searchRarity = paramArr[1];
                    } else if(paramArr[0] == 'sortby'){
                        self.sortBy = paramArr[1];
                    } else if(paramArr[0] == 'pricemin'){
                        self.minPrice = (paramArr[1] == 'null') ? null : parseInt(paramArr[1],10);
                    } else if(paramArr[0] == 'pricemax'){
                        self.maxPrice = (paramArr[1] == 'null') ? null : parseInt(paramArr[1],10);
                    } else if(paramArr[0] == 'page'){
                        self.nextPage = paramArr[1];
                    }
                }
            });
        }
    },
    getSearchParams(){
        let params = {
            templatecat: this.searchCat,
            sportbitcat: this.searchCat2,
            templateid: this.searchTemplate,
            currency: this.searchCurrency,
            rarity: this.searchRarity,
            sortby: this.sortBy,
            pricemin: this.minPrice,
            pricemax: this.maxPrice,
            page: this.nextPage
        };
        let hashParams = this.getHashParams(params);
        if(Object.keys(hashParams).length > 0 || window.location.hash){
            window.writeHashParams(hashParams);
        }
        return params;
    },
    getHashParams(params){
        let hashParams = {};
        if(params.templatecat != 'all'){
            hashParams.templatecat = params.templatecat;
        }
        if(params.sportbitcat != 'all'){
            hashParams.sportbitcat = params.sportbitcat;
        }
        if(params.currency && params.currency != 'all'){
            hashParams.currency = params.currency;
        }
        if(params.templateid != 'all'){
            hashParams.templateid = params.templateid;
        }
        if(params.rarity != 'all'){
            hashParams.rarity = params.rarity;
        }
        if(params.sortby != 'datenew'){
            hashParams.sortby = params.sortby;
        }
        if(params.pricemin != null){
            hashParams.pricemin = params.pricemin;
        }
        if(params.pricemax != null){
            hashParams.pricemax = params.pricemax;
        }
        if(params.dapper === true){
            hashParams.dapper = params.dapper;
        }
        return hashParams;
    },
    getFiltersCount() {
        let params = this.getSearchParams();
        params.sortby = 'datenew';
        params.page = 1;
        let hashParams = this.getHashParams(params);
        return (Object.keys(hashParams).length > 0) ? Object.keys(hashParams).length - 1 : Object.keys(hashParams).length;
    },
    filteredTemplates(){
        if(this.searchCat == 'flobits' && this.searchCat2 != 'all'){
            return _.filter(this.templates, (template) => {
                return template.category == this.searchCat2;
            }).sort(function(l,r){
                return (l.name > r.name) ? 1 : ((l.name < r.name) ? -1 : 0);
            });
        }
        return {};
    },
    getTemplateName(templateId){
        if(templateId == 'all' || !templateId){
            return '';
        }
        let template = _.find(this.templates, (template) => {
            return template.flow_id == parseInt(templateId, 10);
        });
        return template ? template.name : '';
    },
    getPriceRange() {
        let output = '';
        if(this.minPrice && this.maxPrice){
            output = this.minPrice + ' - ' + this.maxPrice;
        } else if(this.minPrice){
            output = this.minPrice + ' - ∞';
        } else if(this.maxPrice){
            output = '0 - ' + this.maxPrice;
        } else {
            return '';
        }

        return output + (this.searchCurrency == 'FLOW' ? ' FLOW' : (this.searchCurrency == 'DUC' ? ' USD' : ''));
    },
    searchResults(){
        let self = this;
        this.nextPage = 1;
        if(this.minPrice && this.maxPrice && this.minPrice > this.maxPrice){
            this.minPrice = this.maxPrice;
        }
        window.searchApi('/search', this.getSearchParams()).then(function (response) {
            self.handleSearchResults(response.data);
        })
            .catch(function (error) {
                console.log(error);
            });
    },
    handleSearchResults(sales){
        this.sales = sales.data;
        this.nextPage = (sales.current_page < sales.last_page) ? sales.current_page + 1 : null;
        this.totalResults = sales.total;
    },
    loadNextPage(){
        if(this.nextPage) {
            let self = this;
            window.searchApi('/search', this.getSearchParams()).then(function (response) {
                self.handleNextResults(response.data);
            })
                .catch(function (error) {
                    console.log(error);
                });
        }
    },
    handleNextResults(sales){
        let newSales = _.concat(this.sales, sales.data);
        this.sales = newSales;
        this.nextPage = (sales.current_page < sales.last_page) ? sales.current_page + 1 : null;
    },
    isSportvatar(sale){
        return (sale.item_type == 'App\\Models\\Sportvatar');
    },
    isSportbit(sale){
        return !this.isSportvatar(sale);
    },
    isFlame(sale){
        let item = this.getItem(sale);
        return this.isSportvatar(sale) ? false : (item.layer == '0');
    },
    isNumber(sale){
        let item = this.getItem(sale);
        return this.isSportvatar(sale) ? false : (item.layer == '13');
    },
    getItem(sale){
        return this.isSportvatar(sale) ? sale.item : _.find(this.templates, {  'id': sale.item.sportvatar_template_id });
    },

    getSvg(sale){
        let item = this.getItem(sale);
        if(!this.isSportvatar(sale)) {
            return item ? wrapSvg(item.svg) : '';
        } else {
            let meta = item.flow_metadata;

            if(!meta){
                return '';
            }
            let accessory = '';
            let hat ='';
            let eyeglasses = '';
            let background ='';

            if(meta.accessoryId){
                accessory = _.find(window.templates, function(item){return item.flow_id == parseInt(meta.accessoryId, 10);});
                accessory = accessory ? accessory.svg : '';
            }
            if(meta.hatId){
                hat = _.find(window.templates, function(item){return item.flow_id == parseInt(meta.hatId, 10);});
                hat = hat ? hat.svg : '';
            }
            if(meta.eyeglassesId){
                eyeglasses = _.find(window.templates, function(item){return item.flow_id == parseInt(meta.eyeglassesId, 10);});
                eyeglasses = eyeglasses ? eyeglasses.svg : '';
            }
            if(meta.backgroundId){
                background = _.find(window.templates, function(item){return item.flow_id == parseInt(meta.backgroundId, 10);});
                background = background ? background.svg : '';
            }

            return window.wrapSvg(item.svg+accessory+eyeglasses+hat)
        }

    },

    getSvgUrl(sale){
        let item = this.getItem(sale);
        return this.isSportvatar(sale) ? '/api/image/'+item.flow_id : '/api/image/template/'+item.flow_id;
    },

    svg(){
        let meta = this.meta();
        if(!meta){
            return '';
        }



        return window.wrapSvg(meta.metadata.svg+accessory+eyeglasses+hat)
    },

    getUrl(sale){
        return this.isSportvatar(sale) ? window.itemUrl('sportvatars', sale.flow_id, sale.flow_address) : '/components/'+sale.flow_id+'/'+sale.flow_address;
    },
    getName(sale){
        let item = this.getItem(sale);
        return item ? item.name : '';
    },
    getType(sale){
        return this.isSportvatar(sale) ? 'Sportvatar' : 'Component';
    },
    getRarityById(id){
        let template = _.find(this.templates, (el) => { return el.id == parseInt(id, 10); });
        return template ? template.rarity : '';
    },
    getRarityClassById(id){
        let template = _.find(this.templates, (el) => { return el.id == parseInt(id,10); });
        return template ? template.rarity : '';
    },
    getRarityScoreNumber(sale){
        return (this.isSportvatar(sale)) ? window.getRarityScore(sale.item.flow_metadata) : 0;
    },
    getRarityText(sale){
        if(!this.isSportvatar(sale) && sale.item.sportvatar_template_id){
            return this.getRarityById(sale.item.sportvatar_template_id);
        } else if(this.isSportvatar(sale) && sale.item.flow_metadata && sale.item.flow_metadata.rarity){
            return sale.item.flow_metadata.rarity;
        } else {
            return '';
        }
    },
    hasMint(sale){
        let item = this.getItem(sale);
        if(this.isSportvatar(sale)){
            return true;
        } else {
            return sale.item.mint != null;
        }
    },
    getMint(sale){
        let item = this.getItem(sale);
        if(this.isSportvatar(sale)){
            return '#' + item.flow_id;
        } else if(this.isFlame(sale)){
            return '#' + sale.item.mint;
        } else {
            return this.hasMint(sale) ? '#' + sale.item.mint + ' / '+ item.max_mintable : item.max_mintable;
        }
    },
    getBurnText(sale){
        if(this.isSportvatar(sale)){
            return '';
        }
        let template = _.find(this.templates, (el) => { return el.flow_id == sale.flow_metadata.metadata.templateId; });
        return template ? template.burned + '/' + template.max_mintable : '';
    },
    getCategory(sale){
        if(this.isSportvatar(sale)){
            return 'sportvatar';
        }
        let item = this.getItem(sale);
        return item ? item.name : '';
    },
    getRarityColor(sale){
        return this.isSportvatar(sale) ? this.getRarityText(sale) : this.getRarityClassById(sale.item.sportvatar_template_id);
    },
    getSportvatarRarityImages(sale){
        let item = this.getItem(sale);
        return this.isSportvatar(sale) ? window.getSportvatarRarityImagesFromMeta(item.flow_metadata.metadata) : '';
    },
    getSearchCatName(cat){
        if(cat == 10){
            return 'Hat';
        } else if(cat == 13){
            return 'Number';
        }
        return 'Accessories';

    }
}));

window.initMarketplace = function(dispatch){
    window.dispatch = dispatch;
}
