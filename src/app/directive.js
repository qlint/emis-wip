angular.module('eduwebApp')
.directive('adjustForSmallScreen', function($window, $rootScope, $timeout) {
    return function (scope, element, attrs) {
		setTimeout( 
			function () {
				
				// just in case the floating header is still kicking around, kill it
				$('.fixedHeader-floating').remove();
				
				// i can't remember what this is for.. may not need it
				// might be to close a navigation item on mobile
				if( $('#navigation').hasClass('in') ) $('#mainnav').trigger('click');
				
				
				$timeout( function () {
					adjustPositions();						
				}, 100);
				
				// watch for resizing and adjust layout as necessary
				$window.addEventListener('resize', function() {
					$timeout( function () {
						adjustPositions();						
					}, 100);
					
				}, false);
				
				function adjustPositions()
				{
					$rootScope.isSmallScreen = (window.innerWidth < 768 ? true : false );
					
					// need to adjust the top position of body content based
					// on the height of the top fixed items
					var headerHeight = $('.navbar-header').height();
					var fixedHeader = $('.content-fixed-header').height();
					
					if( $rootScope.isSmallScreen )
					{
						//$('.subnavbar-container').css('top',headerHeight+8);
						//$('#mainContainer').css('top',headerHeight+15);	
						//$('.content-fixed-header').css('top',headerHeight+5);
						//$('#body-content .main-datagrid').css('padding-top',fixedHeader+40);

					}
					else
					{
						
						//$('.subnavbar-container').css('top',headerHeight+8);
						//$('#mainContainer').css('top',headerHeight+15);	
						//$('.content-fixed-header').css('top',headerHeight+5);
						//$('#body-content .main-datagrid').css('padding-top',fixedHeader-10);
					}
				}
			} 
		,100);

    }
})
.directive('ignoreDirty', [function() {
    return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, elm, attrs, ctrl) {
      ctrl.$setPristine = function() {};
      ctrl.$pristine = false;
    }
  }
}])
.directive('ngEnter', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
                scope.$apply(function (){
                    scope.$eval(attrs.ngEnter);
                });

                event.preventDefault();
            }
        });
    };
})
.directive('mytabset', function () {
  return {
    restrict: 'E',
    replace: true,
    transclude: true,
    controller: function($scope,$rootScope) {
		$scope.templateUrl = '';
		var tabs = $scope.tabs = [];
		var controller = this;

		$scope.selectTab = this.selectTab = function (tab) 
		{
			angular.forEach(tabs, function (tab) {
				tab.selected = false;
			});
			tab.selected = true;

			// make a few adjustments
			$scope.searchTxt = ( tab.title == 'Search' ? $scope.savedSearchTxt : '');
			
			if( !$rootScope.checkIfMobile )
			{
				if( tab.title == 'Search' )
				{
					setTimeout( function () {				
						$scope.zoomToSearchResults();				
					}, 1);
				}
				else if( tab.title == 'History' )
				{
					setTimeout( function () {				
						$scope.zoomToHistoryPaths();

					}, 1);
				}
				else if( tab.title == 'GeoFence' )
				{
					setTimeout( function () {				
						$scope.initGFTrucksDataGrids('assignOutTable');
					}, 1);
				}
				else if( tab.title == 'Assets' )
				{
					//$scope.reCenterMap();	
				}
				else if( tab.title == 'Performance' )
				{
					$scope.plotCharts(); // this is for drivers section
				}
				else if( tab.title == 'Directions' )
				{
					setTimeout($scope.initDirections, 100);
				}
			}
			else
			{
				if( tab.title == 'Search' )
				{
					setTimeout( function () {				
						//$scope.zoomToSearchResults();				
					}, 1);
				}
				else if( tab.title == 'History' )
				{
					setTimeout( function () {				
						$scope.formatHistoryDataTables();
					}, 1);
				}
				else if( tab.title == 'GeoFence' )
				{
					setTimeout( function () {				
						$scope.initGFTrucksDataGrids('assignOutTable');
					}, 1);
				}
				else
				{
					//$scope.reCenterMap();	
				}
			}
			
			// close expanded items
			angular.forEach( $scope.positions, function(item,key)
			{
				item.isExpanded = false;
			});
			// history is expanded by default for details datagrids to render correctly
			angular.forEach( $scope.historyData, function(item,key)
			{
				item.isExpanded = true;
			});
			
			$('.vehicle-details').removeClass('in');
		};

		this.setTabTemplate = function (templateUrl) {
			$scope.templateUrl = templateUrl;
		}

		this.addTab = function (tab) {
			if (tabs.length == 0) {
				tab.show = true;
				this.selectTab(tab);
			}
			else
			{
				tab.show = ( tab.showWhen == 'always' ? true : false );
			}
			tabs.push(tab);
		};

		$scope.activateTab = function (id) {
			var tab = $scope.tabs[id];
			tab.show = true;	
			$scope.selectTab(tab);
		}
		
		$scope.hideTab = function (id) {
			var tab = $scope.tabs[id];
			tab.show = false;
		}

    },
    template:
	  '<div class="full-height">' +
        '<div class="panel-tabs">' +
          '<ul class="nav nav-pills" ng-transclude></ul>' +
		'</div>' +
        '<div class="full-height2" id="mainView">' +
		  '<div class="full-height" ng-include="templateUrl"></div>' +
		'</div>' +
      '</div>'
  };
})
.directive('mytab', function () {
  return {
    restrict: 'E',
    replace: true,
    require: '^mytabset',
    scope: {
      title: '@',
      templateUrl: '@',
	  showWhen: '@'
    },
    link: function(scope, element, attrs, tabsetController) {
      tabsetController.addTab(scope);
 
      scope.select = function () {
        tabsetController.selectTab(scope);
      }
 
      scope.$watch('selected', function () {
        if (scope.selected) {
          tabsetController.setTabTemplate(scope.templateUrl);
        }
      });
	  
 
    },
    template:
      '<li ng-class="{active: selected}" ng-show="show" id="{{ id }}">' +
        '<a href="" ng-click="select()">{{ title }}</a>' +
      '</li>'
  };
})
.directive('slideToggle', function() {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            var target = document.querySelector(attrs.slideToggle);
            attrs.expanded = (!attrs.expanded) ? false : attrs.expanded;
            element.bind('click', function() {
		
                var content = target.querySelector('.slideable_content');
                if(!attrs.expanded) {
                    content.style.border = '1px solid rgba(0,0,0,0)';
                    var y = content.clientHeight;
                    content.style.border = 0;
                    //target.style.height = y + 'px';
					target.style.height = '340px';
                } else {
                    target.style.height = '0px';
                }
                //attrs.expanded = !attrs.expanded;
            });
        }
    }
})
.directive('compile', ['$compile', function ($compile) {
  return function(scope, element, attrs) {
    scope.$watch(
      function(scope) {
        return scope.$eval(attrs.compile);
      },
      function(value) {
        element.html(value);
        $compile(element.contents())(scope);
      }
   )};
}])
.directive('focusMe', function($timeout) {
  return {
    scope: { trigger: '@focusMe' },
    link: function(scope, element) {
      scope.$watch('trigger', function(value) {
        if(value === "true") { 
          $timeout(function() {
            element[0].focus(); 
          });
        }
      });
    }
  };
});;