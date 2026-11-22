import Alpine from "alpinejs";

Alpine.data('showroomData', (templates) => ({
    sportvatars: null,
    templates: templates,
    nextPage: 1,
    totalResults: null,
    minMint: null,
    maxMint: null,
    sortBy: 'datenew',
    searchRarity: 'all',
    searchCat: 'all',
    searchTemplate: 'all',
    showFilterPanel: false,
    init(){
        let self = this;

        this.loadSearchParams();

        this.$watch('sortBy', () => { self.searchResults(); });
        this.$watch('searchRarity', () => { self.searchResults(); });
        this.$watch('searchCat', () => { self.searchTemplate = 'all'; self.searchTemplate = 'all'; self.searchResults(); });
        this.$watch('searchTemplate', () => { self.searchResults(); });

        this.searchResults();

    },
    resetFilters(){
        this.minMint = null;
        this.maxMint = null;
        this.sortBy = 'datenew';
        this.searchCat = 'all';
        this.searchRarity = 'all';
        this.searchTemplate = 'all';
    },
    filteredTemplates(){
        if(this.searchCat != 'all'){
            return _.filter(this.templates, (template) => {
                return template.category == this.searchCat;
            }).sort(function(l,r){
                return (l.name > r.name) ? 1 : ((l.name < r.name) ? -1 : 0);
            });
        }
        return {};
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
                    if(paramArr[0] == 'rarity'){
                        self.searchRarity = paramArr[1];
                    } else if(paramArr[0] == 'sortby'){
                        self.sortBy = paramArr[1];
                    } else if(paramArr[0] == 'mintmin'){
                        self.minMint = (paramArr[1] == 'null') ? null : parseInt(paramArr[1],10);
                    } else if(paramArr[0] == 'mintmax'){
                        self.maxMint = (paramArr[1] == 'null') ? null : parseInt(paramArr[1],10);
                    } else if(paramArr[0] == 'page'){
                        self.nextPage = paramArr[1];
                    } else if(paramArr[0] == 'category'){
                        self.searchCat = paramArr[1];
                    } else if(paramArr[0] == 'template'){
                        self.searchTemplate = paramArr[1];
                    }
                }
            });
        }
    },
    getSearchParams(){
        let params = {
            rarity: this.searchRarity,
            sortby: this.sortBy,
            mintmin: this.minMint,
            mintmax: this.maxMint,
            page: this.nextPage,
            category: this.searchCat,
            template: this.searchTemplate
        };
        let hashParams = this.getHashParams(params);

        if(Object.keys(hashParams).length > 0 || window.location.hash){
            window.writeHashParams(hashParams);
        }
        return params;
    },
    getHashParams(params){
        let hashParams = {};
        if(params.rarity != 'all'){
            hashParams.rarity = params.rarity;
        }
        if(params.sortby != 'datenew'){
            hashParams.sortby = params.sortby;
        }
        if(params.mintmin != null){
            hashParams.mintmin = params.mintmin;
        }
        if(params.mintmax != null){
            hashParams.mintmax = params.mintmax;
        }
        if(params.category != 'all' && params.template != 'all'){
            hashParams.category = params.category;
            hashParams.template = params.template;
        }
        return hashParams;
    },
    getFiltersCount() {
        let params = this.getSearchParams();
        params.sortby = 'datenew';
        params.page = 1;
        let hashParams = this.getHashParams(params);
        return Object.keys(hashParams).length;
    },
    searchResults(){
        let self = this;
        this.nextPage = 1;
        if(this.minMint && this.maxMint && this.minMint > this.maxMint){
            this.minMint = this.maxMint;
        }
        axios.post('/locker-room/search', this.getSearchParams()).then(function (response) {
            self.handleSearchResults(response.data);
        })
            .catch(function (error) {
                console.log(error);
            });
    },
    handleSearchResults(sportvatars){
        this.sportvatars = sportvatars.data;
        this.nextPage = (sportvatars.current_page < sportvatars.last_page) ? sportvatars.current_page + 1 : null;
        this.totalResults = sportvatars.total;
    },
    loadNextPage(){
        if(this.nextPage) {
            let self = this;
            axios.post('/locker-room/search', this.getSearchParams()).then(function (response) {
                self.handleNextResults(response.data);
            })
                .catch(function (error) {
                    console.log(error);
                });
        }
    },
    handleNextResults(sportvatars){
        let newSportvatars = _.concat(this.sportvatars, sportvatars.data);
        this.sportvatars = newSportvatars;
        this.nextPage = (sportvatars.current_page < sportvatars.last_page) ? sportvatars.current_page + 1 : null;
    },
    getRarityText(sportvatar){
        return sportvatar && sportvatar.rarity ? sportvatar.rarity : '';
    },

    getRarityColor(sportvatar){
        return this.getRarityText(sportvatar);
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
    getMintRange() {
        let output = '';
        if(this.minMint && this.maxMint){
            output = this.minMint + ' - ' + this.maxMint;
        } else if(this.minMint){
            output = this.minMint + ' - ∞';
        } else if(this.maxMint){
            output = '0 - ' + this.maxMint;
        } else {
            return '';
        }

        return output;
    },
    getSportvatarPrice(sportvatar){
        if(sportvatar.sale_offers && sportvatar.sale_offers.length>0){
            return window.formatPrice(sportvatar.sale_offers[0].price)+' <span>$</span>';
        } else {
            return null;
        }
    }
}));

window.initShowroom = function(dispatch){
    window.dispatch = dispatch;
}
