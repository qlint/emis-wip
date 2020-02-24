var app = angular.module('app', ['ngMaterial']);

app.controller('Ctrl', function() {
  var self = this;
  
  self.allPermissions =
        {
            globalPermissions: {
                name: 'Global Access',
                values: [
                    {name: 'All', icon: 'lock_open', isSelected: false},
                    {name: 'create', icon: 'add', isSelected: false},
                    {name: 'edit', icon: 'edit', isSelected: false},
                    {name: 'delete', icon: 'delete', isSelected: false},
                    {name: 'view', icon: 'remove_red_eye', isSelected: false},
                    {name: 'export', icon: 'open_in_new', isSelected: false}
                ]
            },
            permissions: [
                {
                    name: 'Directory Management',
                    values: [
                        {name: 'full', isSelected: false},
                        {name: 'create', isSelected: false},
                        {name: 'edit', isSelected: false},
                        {name: 'delete', isSelected: false},
                        {name: 'view', isSelected: false},
                        {name: 'export', isSelected: false}
                    ],
                    children: [
                        {
                            name: 'Users',
                            values: [
                                {name: 'full', isSelected: false},
                                {name: 'create', isSelected: false},
                                {name: 'edit', isSelected: false},
                                {name: 'delete', isSelected: false},
                                {name: 'view', isSelected: false},
                                {name: 'export', isSelected: false}
                            ]
                        }, {
                            name: 'Groups',
                            values: [
                                {name: 'full', isSelected: false},
                                {name: 'create', isSelected: false},
                                {name: 'edit', isSelected: false},
                                {name: 'delete', isSelected: false},
                                {name: 'view', isSelected: false},
                                {name: 'export', isSelected: false}
                            ]
                        }, {
                            name: 'Org Units',
                            values: [
                                {name: 'full', isSelected: false},
                                {name: 'create', isSelected: false},
                                {name: 'edit', isSelected: false},
                                {name: 'delete', isSelected: false},
                                {name: 'view', isSelected: false},
                                {name: 'export', isSelected: false}
                            ]
                        }
                    ]
                },
                {
                    name: 'Data Management',
                    values: [
                        {name: 'full', isSelected: false},
                        {name: 'create', isSelected: false},
                        {name: 'edit', isSelected: false},
                        {name: 'delete', isSelected: false},
                        {name: 'view', isSelected: false},
                        {name: 'export', isSelected: false}
                    ]
                },
                {
                    name: 'Workflows',
                    values: [
                        {name: 'full', isSelected: false},
                        {name: 'create', isSelected: false},
                        {name: 'edit', isSelected: false},
                        {name: 'delete', isSelected: false},
                        {name: 'view', isSelected: false},
                        {name: 'export', isSelected: false}
                    ]
                },
                {
                    name: 'Reports',
                    values: [
                        {name: 'full', isSelected: false},
                        {name: 'create', isSelected: false},
                        {name: 'edit', isSelected: false},
                        {name: 'delete', isSelected: false},
                        {name: 'view', isSelected: false},
                        {name: 'export', isSelected: false}
                    ]
                },
                {
                    name: 'Alerts',
                    values: [
                        {name: 'full', isSelected: false},
                        {name: 'create', isSelected: false},
                        {name: 'edit', isSelected: false},
                        {name: 'delete', isSelected: false},
                        {name: 'view', isSelected: false},
                        {name: 'export', isSelected: false}
                    ]
                },
                {
                    name: 'Support',
                    values: [
                        {name: 'full', isSelected: false},
                        {name: 'create', isSelected: false},
                        {name: 'edit', isSelected: false},
                        {name: 'delete', isSelected: false},
                        {name: 'view', isSelected: false},
                        {name: 'export', isSelected: false}
                    ]
                }
            ]
        };

  self.gridName = self.allPermissions.globalPermissions.name;
  self.rows = self.allPermissions.permissions;
  self.header = self.allPermissions.globalPermissions;

  // Set column header
  self.setColumnHeader = function(index, header, rows, stateCheck) {
    for (var i = 0; i < rows.length; i++) {
      rows[i].values[index].isSelected = header.values[index].isSelected;
      if (index === 0) {
        self.setCell(index, rows[i], rows, header, false);
      }
      if (angular.isDefined(rows[i].children) && rows[i].children.length > 0) {
        self.setColumnHeader(index, rows[i], rows[i].children, false);
      }
    }
    if (stateCheck) {
      setState(header, rows, true);
    }
  };

  // Set cell
  self.setCell = function(index, row, rows, header, stateCheck) {
    if (index === 0) {
      for (var i = 1; i < row.values.length; i++) {
        row.values[i].isSelected = row.values[0].isSelected;
      }
    }
    if (angular.isDefined(row.children) && row.children.length > 0) {
      self.setColumnHeader(index, row, row.children, false);
    }
    if (stateCheck) {
      setState(header, rows, true);
    }
  };

  // Get row state
  function getRowState(row) {
    var state = true;
    for (var i = 1; i < row.values.length; i++) {
      if (row.values[i].isSelected === false) {
        state = false;
        break;
      }
    }
    row.values[0].isSelected = state;
  }

  // Get column state
  function getColumnState(header, rows, index) {
    var state = true;
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].values[index].isSelected === false) {
        state = false;
        break;
      }
    }
    header.values[index].isSelected = state;
  }

  // Set state
  function setState(header, rows, recursion) {
    for (var i = 0; i < rows.length; i++) {
      getRowState(rows[i]);

      if (recursion && angular.isDefined(rows[i].children) && rows[i].children.length > 0) {
        setState(rows[i], rows[i].children, false);
      }
    }
    for (var j = 0; j < header.values.length; j++) {
      getColumnState(header, rows, j);
    }
    getRowState(header);

    if (rows !== self.rows) {
      setState(self.header, self.rows, false);
    }
  }

  // Toggle sub row
  self.toggleSubRow = function(row) {
    row.subRowsToggled = !row.subRowsToggled;
    row.isExpanded = row.subRowsToggled;
  };
  
});