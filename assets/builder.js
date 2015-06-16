FormCraftApp.controller('MyMailController', function($scope, $http) {
	$scope.addMap = function(){
		if ($scope.SelectedList==''){return false;}
		$scope.$parent.Addons.MyMail = $scope.$parent.Addons.MyMail || {};
		$scope.$parent.Addons.MyMail.Map = $scope.$parent.Addons.MyMail.Map || [];
		$scope.$parent.Addons.MyMail.Map.push({
			'listID': $scope.SelectedList,
			'listName': jQuery('#mymail-map .select-list option:selected').text(),
			'columnID': $scope.SelectedColumn,
			'formField': jQuery('#mymail-map .select-field').val()
		});
	}
	$scope.removeMap = function ($index)
	{
		$scope.$parent.Addons.MyMail.Map.splice($index, 1);
	}
});