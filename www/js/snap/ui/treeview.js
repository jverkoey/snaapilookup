/**
 * Tree-view control.
 *
 * @require-package core
 * @requires database.js
 */

Snap.TreeView = function(elementIDs, filters) {
  this._elementIDs = elementIDs;
  this._elements = {};
  for( var key in this._elementIDs ) {
    if( key != 'canvas' ) {
      this._elements[key] = $(this._elementIDs[key]);
    } else {
      this._elements[key] = $('#'+this._elementIDs[key]);
    }
  }
  this._filters = filters;

  this._filters.register_change_callback(this._filters_changed.bind(this));

  this._tree = [];

  this._db = Snap.Database.singleton;

  this._db.register_callbacks({
    receive_hier          : this._receive_hier.bind(this)
  });
};

Snap.TreeView.prototype = {

  _filters_changed : function() {
    this._render();
  },

  _render : function() {
    var html = [];
    var any_filters = this._filters.any_filtered();
    // if( !any_filters || this._filters.is_filtered(tree.id) ) {
    html.push('<ul class="top">');
    for( var i = 0; i < this._tree.length; ++i ) {
      var tree = this._tree[i];
      html.push('<li>',tree.name,'</li>');
    }
    html.push('</ul>');
    this._elements.view.html(html.join(''));
  },

  _receive_hier : function() {
    var hier = this._db.get_hierarchies();

    for( var category in hier ) {
      var list = hier[category];

      var tree = [];
      var map = {};

      for( var id in list ) {
        var node = list[id];
        // node.name
        // node.ancestors
        var parentnode = null;
        for( var i = 0; i < node.ancestors.length; ++i ) {
          var parent = node.ancestors[i];
          if( parentnode ) {
            if( !parentnode.children[parent] ) {
              parentnode.children[parent] = {children:{}};
            }
            parentnode = parentnode.children[parent];
          } else {  
            if( undefined == map[parent] ) {
              tree.push({children:{}});
              map[parent] = tree.length - 1;
            }
            parentnode = tree[map[parent]];
          }
        }
        if( !parentnode ) {
          if( undefined != map[id] ) {
            list[map[id]].name = node.name;
          } else {
            tree.push({name:node.name, children:{}});
            map[id] = tree.length - 1;
          }
        } else {
          if( undefined != parentnode.children[id] ) {
            parentnode.children[id].name = node.name;
          } else {
            parentnode.children[id] = {name:node.name, children:{}};
          }
        }
      }

      this._tree.push({id:category, name:this._db.id_to_name(category), tree:tree});
    }

    function compare_names(left, right) {
      var result =
        (left.name.toLowerCase() < right.name.toLowerCase() ? -1 :
        (left.name.toLowerCase() == right.name.toLowerCase() ? 0 : 1));
      return result;
    }
    this._tree = this._tree.sort(compare_names);

    this._render();
  }

};
