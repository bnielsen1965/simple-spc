// control chart jquery plugin
(function( $ ){
	$.fn.controlchart = function( method ) {  
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || !method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.controlchart' );
		}
	};
	
	var plotCount = 0;
	var activePlots = [];

				//var plot2 = null;


	var methods = {
		init: function(options) {
			// Create some defaults, extending them with any options that were provided
			var settings = $.extend( {
					'chartURL'         : false,
					'chartTitle' : 'Control Chart'
			}, options);
			
			
			// maintain chainability
			return this.each(function() {        
//				var plotDrawn = false;
				var plotElement = this;
//				var plotData = null;
	
				if( settings.chartURL ) {
					// check to see if this element has a plot id
					plotID = $(this).data('plotID');
					
					// if no ID then create one
					if( !plotID ) {
						// increment plot counter
						plotCount += 1;
					
						// create ID for this plot
						plotID = 'plotID_' + plotCount;
						
						// add id to element
						$(this).data('plotID', plotID);
						
						//push new plot object int active plots
						activePlots.push({plot: null, plotID: plotID});
						
						// note the new plots index value
						plotIndex = activePlots.length - 1;
					}
					else {
						// already have a plotID, destroy existing plot
						for(i = 0; i < activePlots.length; i++) {
							// check to see if we found the plot
							if( plotID == activePlots[i].plotID ) {
								// note the plot index value
								plotIndex = i;
								
								// destroy the current plot
								if( activePlots[plotIndex].plot != null ) {
									activePlots[plotIndex].plot.destroy();
								}
								else {
									$(plotElement).html('');
								}
								
								// end for loop
								break;
							}
						}
					}

					
					// clear out any previous violation messages
					$('#violation_messages').html('');

					// clear out any previous description messages
					$('#chart_description').html('');

					
					// make ajax call for plot data
					$.ajax({
						url: settings.chartURL,
						dataType: "json",
						success: function(data) {
							if( data.data && data.data.length > 0 ) {
//							plotData = [];
					
								var points = [data.data];
					
								var series = [{label: "Data", color: "#000000"}];
					
								var points = [data.data, data.centerLine];
								var series = [{label: "Data", color: "#000000"},{label: 'Centerline', color: '#00A200', showMarker: false}];
					
								// if Upper Control Limit was provided then include series
								if( data.UCL ) {
									points.push(data.UCL);
									series.push({label: "UCL", color: "#B80000", showMarker: false});
								}
								
								// if Lower Control Limit was provided then include series
								if( data.LCL ) {
									points.push(data.LCL);
									series.push({label: 'LCL', color: '#CC8300', showMarker: false});
								}
								
								// if violation data was provided then include series
								if( data.violationData ) {
									points.push(data.violationData);
									series.push({label: 'Violations', color: '#FF0000', showLine: false, markerOptions: {style: 'circle', size: 12} });
								}
								
								// store data points
	//							plotData = points;
					
								var chartPadding = 1.2;
								var decimalPoints = data.decimalPoints;
								
								//if( plot2 != null ) plot2.destroy();
								
								// create new plot and add to active plots array
								activePlots[plotIndex].plot = $.jqplot($(plotElement).attr('id'), points, {
									title: settings.chartTitle,
									axes: {
										xaxis: {
											renderer: $.jqplot.CategoryAxisRenderer,
											tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
											numberTicks: 10,
											tickOptions: {
											fontSize: 10,
												angle: -30
											}
										},
										yaxis: {
											tickOptions: {
												formatString: '%.' + decimalPoints + 'f'
											}
										}
									},
									series: series,
									highlighter: {
										useXValue: true,
										show: true,
										sizeAdjust: 7.5,
										tooltipLocation: 'ne'
									},
									cursor: {
										show: false
									}
				
								});
								
								
								// append information div if there are messages
								if( data.violationMessages && data.violationMessages.length > 0 ) {
									$('<ul>').addClass('violation_messages').appendTo('#violation_messages');
									for(i = 0; i < data.violationMessages.length; i++) {
										liclass = (i%2 == 0 ? 'even' : 'odd');
										$('<li>').html(data.violationMessages[i]).addClass(liclass).appendTo($('#violation_messages').find('ul.violation_messages'));
									}
								}
								
								
								// show description if provided
								if( data.description && data.description.length > 0 ) {
									$('#chart_description').html('Description: ' + data.description);
								}
								
						/*
						$('#chart2').bind(
							'jqplotDataClick',
							function (ev, seriesIndex, pointIndex, data) {
								
								$('#info').html(plotData[seriesIndex][pointIndex][0] + ', ' + plotData[seriesIndex][pointIndex][1]);
							}
						);
						*/
							} // end of if check for data
							else {
								// no data for chart
								$(plotElement).html('No chart data.');
							}
						} // end of ajax success function
					}); // end of ajax request for chart
				} // end of if chartURL set
			}); // end of this.each()
		} // end of init
	} // end of methods
	
})( jQuery );
