(function ($) {
  
    var tfLineChart = (function () {
  
      var chartBar = function () {
      
        var options = {
            series: [{
            name: 'Profit',
            data: window.jdAdminChartData && window.jdAdminChartData.earningsProfit ? window.jdAdminChartData.earningsProfit : [0, 0, 0, 0, 0, 0, 0, 0]
          }, {
            name: 'Revenue',
            data: window.jdAdminChartData && window.jdAdminChartData.earningsRevenue ? window.jdAdminChartData.earningsRevenue : [0, 0, 0, 0, 0, 0, 0, 0]
          }],
            chart: {
            type: 'bar',
            height: 263,
            toolbar: {
              show: false,
            },
          },
          plotOptions: {
            bar: {
              horizontal: false,
              columnWidth: '10px',
              endingShape: 'rounded'
            },
          },
          dataLabels: {
            enabled: false
          },
          legend: {
            show: false,
          },
          colors: ['#2377FC33', '#2377FC'],
          stroke: {
            show: false,
          },
          xaxis: {
            categories: window.jdAdminChartData && window.jdAdminChartData.earningsLabels ? window.jdAdminChartData.earningsLabels : ['Jan' , 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
            labels: {
              style: {
                colors: '#95989D',
              },
            },
          },
          yaxis: {
            show: false,
          },
          fill: {
            opacity: 1
          },
          tooltip: {
            y: {
              formatter: function (val) {
                return "KSh " + Number(val || 0).toLocaleString()
              }
            }
          }
          };

        chart = new ApexCharts(
          document.querySelector("#line-chart-6"),
          options
        );
        if ($("#line-chart-6").length > 0) {
          chart.render();
        }
      };
  
      /* Function ============ */
      return {
        init: function () {},
  
        load: function () {
          chartBar();
        },
        resize: function () {},
      };
    })();
  
    jQuery(document).ready(function () {});
  
    jQuery(window).on("load", function () {
      tfLineChart.load();
    });
  
    jQuery(window).on("resize", function () {});
})(jQuery);
