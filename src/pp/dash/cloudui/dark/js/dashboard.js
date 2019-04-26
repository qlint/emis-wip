(function($) {
  'use strict';
  $(function() {
    if ($("#earning-report").length) {
      var earningReportData = {
        datasets: [{
          data: [60, 30, 10],
          backgroundColor: [
            '#22548e',
            '#af827c',
            '#f3f6f9'
          ],
          borderWidth: 0
        }],

        // These labels appear in the legend and in the tooltips when hovering different arcs
        labels: [
          'Primary',
          'Highschool',
          'Pre-School'
        ]
      };
      var earningReportOptions = {
        responsive: true,
        maintainAspectRatio: true,
        animation: {
          animateScale: true,
          animateRotate: true
        },
        legend: {
          display: false
        },
        legendCallback: function(chart) {
          var text = [];
          text.push('<ul class="legend'+ chart.id +'">');
          for (var i = 0; i < chart.data.datasets[0].data.length; i++) {
            text.push('<li><span class="legend-label" style="background-color:' + chart.data.datasets[0].backgroundColor[i] + '"></span>');
            if (chart.data.labels[i]) {
              text.push(chart.data.labels[i]);
            }
            text.push('<span class="legend-percentage ml-auto">'+ chart.data.datasets[0].data[i] + '%</span>');
            text.push('</li>');
          }
          text.push('</ul>');
          return text.join("");
        },
        cutoutPercentage: 70
      };
      var earningReportCanvas = $("#earning-report").get(0).getContext("2d");
      var earningReportChart = new Chart(earningReportCanvas, {
        type: 'doughnut',
        data: earningReportData,
        options: earningReportOptions
      });
      document.getElementById('earning-report-legend').innerHTML = earningReportChart.generateLegend();
    }
  });
  if ($("#chart-activity").length) {
      $.get("/pp/dash/cloudui/ajax/commStats.php", function(data, status){
        // alert("Data: " + data + "\nStatus: " + status);
        var schoolCommsArr = [];
        var commsData = JSON.parse(data);
        for (var sc = 0; sc < commsData.length; sc++) {
            var eachSchoolComm = JSON.parse(commsData[sc]);
            schoolCommsArr.push(eachSchoolComm);
        }
        console.log(schoolCommsArr);
        
        // this is a summation function
        Array.prototype.sum = function (prop) {
            var total = 0
            for ( var i = 0, _len = this.length; i < _len; i++ ) {
                total += parseInt(this[i][prop])
            }
            return total
        }
        
        //now we sum the data from all the schools
        var totalMessages = schoolCommsArr.sum("total_messages");
        var totalSmsMessages = schoolCommsArr.sum("total_sms");
        var totalAppMessages = schoolCommsArr.sum("total_app");
        
        //total monthly
        var totalJan = schoolCommsArr.sum("tot_comms_jan");
        var totalFeb = schoolCommsArr.sum("tot_comms_feb");
        var totalMar = schoolCommsArr.sum("tot_comms_mar");
        var totalApr = schoolCommsArr.sum("tot_comms_apr");
        var totalMay = schoolCommsArr.sum("tot_comms_may");
        var totalJun = schoolCommsArr.sum("tot_comms_jun");
        var totalJul = schoolCommsArr.sum("tot_comms_jul");
        var totalAug = schoolCommsArr.sum("tot_comms_aug");
        var totalSep = schoolCommsArr.sum("tot_comms_sep");
        var totalOct = schoolCommsArr.sum("tot_comms_oct");
        var totalNov = schoolCommsArr.sum("tot_comms_nov");
        var totalDec = schoolCommsArr.sum("tot_comms_dec");
        
        //total sms monthly
        var totalJanSms = schoolCommsArr.sum("tot_sms_jan");
        var totalFebSms = schoolCommsArr.sum("tot_sms_feb");
        var totalMarSms = schoolCommsArr.sum("tot_sms_mar");
        var totalAprSms = schoolCommsArr.sum("tot_sms_apr");
        var totalMaySms = schoolCommsArr.sum("tot_sms_may");
        var totalJunSms = schoolCommsArr.sum("tot_sms_jun");
        var totalJulSms = schoolCommsArr.sum("tot_sms_jul");
        var totalAugSms = schoolCommsArr.sum("tot_sms_aug");
        var totalSepSms = schoolCommsArr.sum("tot_sms_sep");
        var totalOctSms = schoolCommsArr.sum("tot_sms_oct");
        var totalNovSms = schoolCommsArr.sum("tot_sms_nov");
        var totalDecSms = schoolCommsArr.sum("tot_sms_dec");
        
        //total app monthly
        var totalJanApp = schoolCommsArr.sum("tot_app_jan");
        var totalFebApp = schoolCommsArr.sum("tot_app_feb");
        var totalMarApp = schoolCommsArr.sum("tot_app_mar");
        var totalAprApp = schoolCommsArr.sum("tot_app_apr");
        var totalMayApp = schoolCommsArr.sum("tot_app_may");
        var totalJunApp = schoolCommsArr.sum("tot_app_jun");
        var totalJulApp = schoolCommsArr.sum("tot_app_jul");
        var totalAugApp = schoolCommsArr.sum("tot_app_aug");
        var totalSepApp = schoolCommsArr.sum("tot_app_sep");
        var totalOctApp = schoolCommsArr.sum("tot_app_oct");
        var totalNovApp = schoolCommsArr.sum("tot_app_nov");
        var totalDecApp = schoolCommsArr.sum("tot_app_dec");
        
        var areaData = {
      labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
      datasets: [{
          data: [totalJanSms, totalFebSms, totalMarSms, totalAprSms, totalMaySms, totalJunSms, totalJulSms, totalAugSms, totalSepSms, totalOctSms, totalNovSms, totalDecSms],
          backgroundColor: [
            '#00FF7F'
          ],
          borderColor: [
            '#32CD32'
          ],
          borderWidth: 0,
          fill: 'origin',
        },
        {
          data: [totalJanApp, totalFebApp, totalMarApp, totalAprApp, totalMayApp, totalJunApp, totalJulApp, totalAugApp, totalSepApp, totalOctApp, totalNovApp, totalDecApp],
          backgroundColor: [
            '#FF00FF'
          ],
          borderColor: [
            '#FF00FF'
          ],
          borderWidth: 0,
          fill: 'origin',
        }
      ]
    };
    var areaOptions = {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        filler: {
          propagate: false
        }
      },
      scales: {
        xAxes: [{
          gridLines: {
            lineWidth: 0,
            color: "rgba(0,0,0,0)"
          }
        }],
        yAxes: [{
          display: false,
          ticks: {
            display: false,
            autoSkip: false,
            maxRotation: 0,
            stepSize: 50,
            min: 0,
            max: 900
          }
        }]
      },
      legend: {
        display: false
      },
      tooltips: {
        enabled: true
      },
      elements: {
        line: {
          tension: 0
        },
        point: {
          radius: 5
        }
      }
    }
    var activityChartCanvas = $("#chart-activity").get(0).getContext("2d");
    var activityChart = new Chart(activityChartCanvas, {
      type: 'line',
      data: areaData,
      options: areaOptions
    });
      });
    // var areaData = {
    //   labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    //   datasets: [{
    //       data: [totalJanSms, totalFebSms, totalMarSms, totalAprSms, totalMaySms, totalJunSms, totalJulSms, totalAugSms, totalSepSms, totalOctSms, totalNovSms, totalDecSms],
    //       backgroundColor: [
    //         '#af827c'
    //       ],
    //       borderColor: [
    //         '#af827c'
    //       ],
    //       borderWidth: 0,
    //       fill: 'origin',
    //     },
    //     {
    //       data: [143, 250, 179, 220, 185, 240, 122],
    //       backgroundColor: [
    //         '#22548e'
    //       ],
    //       borderColor: [
    //         '#22548e'
    //       ],
    //       borderWidth: 0,
    //       fill: 'origin',
    //     }
    //   ]
    // };
    // var areaOptions = {
    //   responsive: true,
    //   maintainAspectRatio: true,
    //   plugins: {
    //     filler: {
    //       propagate: false
    //     }
    //   },
    //   scales: {
    //     xAxes: [{
    //       gridLines: {
    //         lineWidth: 0,
    //         color: "rgba(0,0,0,0)"
    //       }
    //     }],
    //     yAxes: [{
    //       display: false,
    //       ticks: {
    //         display: false,
    //         autoSkip: false,
    //         maxRotation: 0,
    //         stepSize: 15,
    //         min: 0,
    //         max: 250
    //       }
    //     }]
    //   },
    //   legend: {
    //     display: false
    //   },
    //   tooltips: {
    //     enabled: true
    //   },
    //   elements: {
    //     line: {
    //       tension: 0
    //     },
    //     point: {
    //       radius: 0
    //     }
    //   }
    // }
    // var activityChartCanvas = $("#chart-activity").get(0).getContext("2d");
    // var activityChart = new Chart(activityChartCanvas, {
    //   type: 'line',
    //   data: areaData,
    //   options: areaOptions
    // });
  }
  if ($('#sales-chart').length) {
    var lineChartCanvas = $("#sales-chart").get(0).getContext("2d");
    var data = {
      labels: ["2013", "2014", "2014", "2015", "2016", "2017", "2018"],
      datasets: [
        {
          label: 'Support',
          data: [1500, 7030, 1050, 2300, 3510, 6800, 4500],
          borderColor: [
            '#af827c'
          ],
          borderWidth: 3,
          fill: false
        },
        {
          label: 'Product',
          data: [5500, 4080, 3050, 5600, 4510, 5300, 2400],
          borderColor: [
            '#22548e'
          ],
          borderWidth: 3,
          fill: false
        }
      ]
    };
    var options = {
      scales: {
        yAxes: [{
          display: false,
          gridLines: {
            drawBorder: false,
            lineWidth: 0,
            color: "rgba(0,0,0,0)"
          },
          ticks: {
            stepSize: 2000,
            fontColor: "#686868"
          }
        }],
        xAxes: [{
          gridLines: {
            drawBorder: false,
            lineWidth: 0,
            color: "rgba(0,0,0,0)"
          }
        }]
      },
      legend: {
        display: false
      },
      elements: {
        point: {
          radius: 0
        }
      },
      stepsize: 1
    };
    var lineChart = new Chart(lineChartCanvas, {
      type: 'line',
      data: data,
      options: options
    });
  }
  if ($("#inline-datepicker-example").length) {
    $('#inline-datepicker-example').datepicker({
      enableOnReadonly: true,
      todayHighlight: true,
    });
  }
})(jQuery);
