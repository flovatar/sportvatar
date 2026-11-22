import Alpine from "alpinejs";
import Parallax from "parallax-js";

Alpine.data('homepageData', (sportvatars, templates) => ({
    sportvatars: sportvatars,
    templates: templates,
    curCategory: 'body',
    showComponentsTable: false,
    init(){
        console.log(this.templates);
    }
}));

window.initHomepage = function(dispatch){
    window.dispatch = dispatch;
    var scene = document.getElementById('home-hero-box');
    if(scene){
        var parallaxInstance = new Parallax(scene);
    }

}
