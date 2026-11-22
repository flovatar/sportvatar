import Alpine from "alpinejs";
import Chart from 'chart.js/auto';

Alpine.data('chartData', (sales, saleOffers) => ({
    sales: sales,
    saleOffers: saleOffers,
    showSales: true,
    showDropdown: false,
    selectedOption: 0,
    chart: null,
    fetch(){
        console.log('fetch sales');
        console.log(this.sales);
        console.log(this.saleOffers);
        this.renderChart();
    },
    renderChart(){
        console.log('init chart');
        //console.log(this.sales);
        if(this.chart){
            this.chart.destroy();
        }

        let ctx = document.getElementById('salesChart').getContext('2d');

        let self = this;
        this.chart = new Chart(ctx, {
            type: "line",
            data: {
                labels: self.showSales ? self.sales.date : self.saleOffers.date,
                datasets: [
                    {
                        label: "Average Price",
                        backgroundColor: "rgba(237, 100, 166, 0.25)",
                        borderColor: "rgba(237, 100, 166, 1)",
                        pointBackgroundColor: "rgba(237, 100, 166, 1)",
                        data: self.showSales ? self.sales.avg : self.saleOffers.avg,
                    },
                ],
            },
            layout: {
                padding: {
                    right: 10
                }
            },
            options: {
                aspectRatio: 2,
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            callback: function(value,index,array) {
                                return value > 1000 ? ((value < 1000000) ? value/1000 + 'K USD' : value/1000000 + 'M USD') : value + ' USD';
                            }
                        }
                    }
                }
            }
        });

    }
}));
