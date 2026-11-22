import Alpine from "alpinejs";
import party from "party-js";

Alpine.data('packSaleData', (rarity, packId, signature, price, address) => ({
    rarity: rarity,
    packData: null,
    packId: packId,
    price: price,
    packCode: signature,
    packAvailable: true,
    reservedAddress: address,
    init(){
        let self = this;

        this.packData = {
            id: this.packId,
            price: this.price,
            signature: this.packCode
        };
        //window.preReservePack('terrakit');


        this.$watch('packPurchased', () => {
            if(self.packPurchased === true){
                self.showConfetti();
            }
        });

        window.checkPackAvailable(this.packId);

    },

    hasCorrectAddress(){
        return this.reservedAddress && this.reservedAddress == this.address();
    },
    getPackPrice(){
      let price = 9.99;
      if(this.rarity == 'rare'){
          price = 24.99;
      } else if(this.rarity == 'epic'){
          price = 69.99;
      } else if(this.rarity == 'legendary'){
          price = 199.99;
      }
      return price;
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
            window.buyPackV2(this.packData.id, this.packData.price)
        }
    }
}));

window.initSales = function(dispatch){
    window.dispatch = dispatch;
}
