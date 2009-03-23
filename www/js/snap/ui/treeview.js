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
  this._tree_ui = [];
  this._tree_visibility = [];

  this._elements.view.html('Loading table of contents...');

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
    var any_filters = this._filters.any_filtered();
    for( var i = 0; i < this._tree.length; ++i ) {
      var tree = this._tree[i];
      if( !any_filters || this._filters.is_filtered(tree.id) ) {
        if( !this._tree_visibility[i] ) {
          this._tree_visibility[i] = true;
          this._tree_ui[i].fadeIn('slow');
        }
      } else {
        if( this._tree_visibility[i] ) {
          this._tree_visibility[i] = false;
          this._tree_ui[i].fadeOut('slow');
        }
      }
    }
  },

  _create_ui : function() {
    var html = [];
    html.push('<ul class="top">');
    function traverse_children(parent_node, className) {
      var has_children = parent_node.children.length > 0;
      html.push('<li class="',className,'" style="display:none"><div class="node_text');
      if( has_children ) {
        html.push(' has_children');
      }
      html.push('"><span class="expander">+</span>', parent_node.name, '</div>');
      if( has_children ) {
        var children = parent_node.children;
        html.push('<ul>');
        for( var i = 0; i < children.length; ++i ) {
          traverse_children(children[i], 'child');
        }
        html.push('</ul>');
      }
      html.push('</li>');
    }

    for( var i = 0; i < this._tree.length; ++i ) {
      var tree = this._tree[i];
      this._tree_visibility.push(false);
      traverse_children(tree, 'root');
    }
    html.push('</ul>');
    this._elements.view.html(html.join(''));
    var t = this;
    $(this._elementIDs.view+' .root').each(function(index) {
      t._tree_ui[index] = $(this);
    });
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

        // Traverse the ancestry, creating the path along the way.
        var parentnode = null;
        for( var i = 0; i < node.ancestors.length; ++i ) {
          var iter = node.ancestors[i];
          if( parentnode ) {
            if( undefined == parentnode.map[iter] ) {
              parentnode.children.push({map:{}, children:[]});
              parentnode.map[iter] = parentnode.children.length - 1;
            }
            parentnode = parentnode.children[parentnode.map[iter]];
          } else {  
            if( undefined == map[iter] ) {
              tree.push({map:{}, children:[]});
              map[iter] = tree.length - 1;
            }
            parentnode = tree[map[iter]];
          }
        }

        // Now that we've created the path, let's add this node.
        if( !parentnode ) {
          if( undefined != map[id] ) {
            tree[map[id]].name = node.name;
            tree[map[id]].id = id;
          } else {
            tree.push({name:node.name, id:id, map:{}, children:[]});
            map[id] = tree.length - 1;
          }
        } else {
          if( undefined != parentnode.map[id] ) {
            parentnode.children[parentnode.map[id]].name = node.name;
            parentnode.children[parentnode.map[id]].id = id;
          } else {
            parentnode.children.push({name:node.name, id:id, map:{}, children:[]});
            parentnode.map[id] = parentnode.children.length - 1;
          }
        }
      }

      this._tree.push({id:category, name:this._db.id_to_name(category), children:tree});
    }

    function compare_names(left, right) {
      var result =
        (left.name.toLowerCase() < right.name.toLowerCase() ? -1 :
        (left.name.toLowerCase() == right.name.toLowerCase() ? 0 : 1));
      return result;
    }

    this._tree = this._tree.sort(compare_names);

/*    function traverse_tree(node) {
      for( var i = 0; i < node.children.length; ++i ) {
        traverse_tree(node.children[i]);
      }
      node.children = node.children.sort(compare_names);
    }

    console.dir(this._tree);
    for( var i = 0; i < this._tree.length; ++i ) {
      traverse_tree(this._tree[i]);
    }
    console.dir(this._tree);*/

    this._create_ui();
    this._render();
  }

};
