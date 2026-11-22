import Alpine from "alpinejs";
import party from "party-js";

Alpine.data('packData', () => ({
    packData: null,
    packId: null,
    packCode: null,
    packAvailable: true,
    init(){
        let self = this;
        //window.preReservePack('terrakit');

        if(window.packClaimCode){
            this.packRedeemCode = window.packClaimCode;
        }

        this.$watch('packPurchased', () => {
            if(self.packPurchased === true){
                self.showConfetti();
            }
        });

        if(window.packClaimCode){
            window.checkPackAvailable(this.packRedeemId());
        }

    },
    shotConfetti(){
        party.confetti(document.getElementById('pack-img-hero'), {
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
    purchasePack(){
        if(this.packData){
            window.dispatch('loading-started');
            window.buyPack(this.packData.id, this.packData.price, this.packData.signature)
        } else {
            reservePack('terrakit');
        }
    }
}));

window.initSales = function(dispatch){
    window.dispatch = dispatch;
}
