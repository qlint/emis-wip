angular.module('eduwebApp').service('ajaxService', ['$http','$rootScope', function ($http,$rootScope) {

        // setting timeout of 1 second to simulate a busy server.

		var loadingCount = 0;
		//console.log($rootScope.clientIdentifier);

        this.AjaxPost = function (data, route, successFunction, errorFunction, extras) {
			$http({
				method: 'POST',
				url: route,
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				transformRequest: function(obj) {
					var str = [];
					for(var p in obj)
					str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
					return str.join("&");
				},
				data: data
			}).success(function (response, status, headers, config) {
				successFunction(response, status, extras);
			}).error(function (response) {
				errorFunction(response);
			});

        }

		this.AjaxPost2 = function (data, route, successFunction, errorFunction, extras) {
			$http({
				method: 'POST',
				url: route,
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				data: data
			}).success(function (response, status, headers, config) {
				successFunction(response, status, extras);
			}).error(function (response) {
				errorFunction(response);
			});

        }

		this.AjaxPut = function (data, route, successFunction, errorFunction, extras) {
			$http({
				method: 'PUT',
				url: route,
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				data: data
			}).success(function (response, status, headers, config) {
				successFunction(response, status, extras);
			}).error(function (response) {
				errorFunction(response);
			});

        }

		this.AjaxDelete = function (route, successFunction, errorFunction, extras) {
			$http({
					method: 'DELETE',
					url: route,
					// headers: {'X-SCHOOL-IDENTIFIER': $rootScope.clientIdentifier},
			}).success(function (response, status, headers, config) {
				successFunction(response, status, extras);
			}).error(function (response) {
				errorFunction(response);
			});
        }

		this.AjaxGet = function (route, successFunction, errorFunction, extras) {

			$http({
				method: 'GET',
				url: route,
				// headers: {'X-SCHOOL-IDENTIFIER': $rootScope.clientIdentifier},
			}).success(function (response, status, headers, config) {
				successFunction(response, status, extras);
				return response;
			}).error(function (response) {
				errorFunction(response);
			});

        }

        this.AjaxGetWithData = function (data, route, successFunction, errorFunction, extras) {

			$http({
				method: 'GET',
				url: route,
				// headers: {'X-SCHOOL-IDENTIFIER': $rootScope.clientIdentifier},
				params: data
			}).success(function (response, status, headers, config) {
				successFunction(response, status, extras);
				return response;
			}).error(function (response) {
				errorFunction(response);
			});

        }

		this.JSONPGet = function (data, route, successFunction, errorFunction, extras) {
            //blockUI.start();
          //  setTimeout(function () {
				if(++loadingCount === 1) $rootScope.$broadcast('loading:progress');
                $http.jsonp(route).success(function (response, status, headers, config) {
					if(--loadingCount === 0) $rootScope.$broadcast('loading:finish');
                    successFunction(response, status, extras);
                }).error(function (response) {
					if(--loadingCount === 0) $rootScope.$broadcast('loading:finish');
                    errorFunction(response);
                });
           // }, 1000);

        }


		return this;
}]);
