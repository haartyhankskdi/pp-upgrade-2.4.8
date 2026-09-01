define([
    'jquery',
    'uiComponent',
    'Magezon_Core/js/chartjs/chartjs'
], function ($, Component, Chart) {
    'use strict';

    return Component.extend({
    	defaults: {
    		visible: false,
    		template: 'Magezon_PopupBuilder/chart',
            imports: {
                labels: '${ $.provider }:data.labels',
                views: '${ $.provider }:data.views',
                totalViews: '${ $.provider }:data.totalViews',
                clicks: '${ $.provider }:data.clicks',
                totalClicks: '${ $.provider }:data.totalClicks',
                closes: '${ $.provider }:data.closes',
                totalCloses: '${ $.provider }:data.totalCloses',
                conversion: '${ $.provider }:data.conversion',
                loading: '${ $.provider }:params.loading'
            },
            listens: {
                views: 'loadChart',
                clicks: 'loadChart',
                closes: 'loadChart'
            }
    	},

    	initObservable: function () {
    		this._super()
    		.observe('visible totalViews totalClicks totalCloses conversion')
    		.track({
				views: [],
				clicks: [],
				closes: []
			});
            return this;
    	},

        loadChart: function(chartData) {
        	if (this.views && this.clicks && this.closes) {
	            if (this.chart) this.chart.destroy();
	            var chartSelector = $('#mgzreport-chart');
	            this.chart = new Chart(chartSelector[0].getContext('2d'), {
	                type: 'line',
	                options: {
						scales: {
							xAxes: [{
								ticks: {
									beginAtZero: true,
									autoSkip: true,
									maxTicksLimit: 7,
									maxRotation: 0,
									minRotation: 0
								}
							}],
							yAxes: [{
								ticks: {
									beginAtZero: true,
									autoSkip: true,
									maxTicksLimit: 8
								}
							}]
						},
						tooltips: {
							titleFontSize: 12,
							bodyFontSize: 12,
							xPadding: 12,
							yPadding: 12,
							caretSize: 6,
							cornerRadius: 3,
							displayColors: false,
							titleFontColor: '#fff',
							backgroundColor: 'rgb(85, 102, 119)',
							bodyFontColor: '#fff',
							intersect: false,
							mode: 'index',
							borderWidth: 1,
							borderColor: 'rgb(85, 102, 119)'
						},
						hover: {
							intersect: false,
							mode: 'index'
						},
						animation: {
							duration: 500
						},
						legend: {
							display: false
						}
					},
	                data: {
	                    labels: this.labels,
	                    datasets: [
	                    	{
	                    		lineTension: .15,
				                borderCapStyle: "butt",
				                borderJoinStyle: "miter",
				                pointRadius: 0,
				                pointHoverRadius: 3,
				                borderWidth: 2,
								label: "Views",
								borderColor: "rgba(237, 133, 66, 1)",
								pointBackgroundColor: "rgba(237, 133, 66, 1)",
								backgroundColor: "rgba(237, 133, 66, 0.1)",
								data: this.views
	                    	},
	                        {
	                        	lineTension: .15,
				                borderCapStyle: "butt",
				                borderJoinStyle: "miter",
				                pointRadius: 0,
				                pointHoverRadius: 3,
				                borderWidth: 2,
				                label: "Clicks",
				                borderColor: "rgb(100, 151, 243)",
				                pointBackgroundColor: "rgb(100, 151, 243)",
				                backgroundColor: "rgba(100, 151, 243, 0.1)",
				                data: this.clicks
	                        },
	                        {
	                        	lineTension: .15,
				                borderCapStyle: "butt",
				                borderJoinStyle: "miter",
				                pointRadius: 0,
				                pointHoverRadius: 3,
				                borderWidth: 2,
				                label: "Closes",
				                borderColor: "rgb(52, 215, 137)",
				                pointBackgroundColor: "rgb(52, 215, 137)",
				                backgroundColor: "rgba(52, 215, 137, 0.1)",
				                data: this.closes
	                        }
	                    ]
	                }
	            });
	            this.chart.update();
	            this.visible(true);
	        }
        }
    });
});