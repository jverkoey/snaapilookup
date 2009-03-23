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

  // [category][id] => List of functions
  this._function_cache = {};

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
          this._tree_ui[i].fadeIn('fast');
        }
      } else {
        if( this._tree_visibility[i] ) {
          this._tree_visibility[i] = false;
          this._tree_ui[i].fadeOut('fast');
        }
      }
    }
  },

  _create_ui : function() {
    var html = [];
    html.push('<ul class="top">');
    function traverse_children(category, parent_node, className) {
      var has_children = parent_node.children.length > 0 || parent_node.fun_count > 0;
      var id = 'cat_' + category + (className == 'root' ? '-1' : '-'+parent_node.id);
      html.push('<li id="',id,'" class="',className,'"');
      if( className == 'root' ) {
        html.push(' style="display:none"');
      }
      html.push('><div class="node_text');
      if( className == 'root' ) {
        html.push(' root_text');
      }
      if( has_children ) {
        html.push(' has_children');
      }
      html.push('">');
      if( has_children ) {
        html.push('<div class="expander">+</div>');
      }
      html.push('<div class="the_text">',parent_node.name, '</div></div>');
      if( has_children ) {
        var children = parent_node.children;
        html.push('<ul style="display:none">');
        for( var i = 0; i < children.length; ++i ) {
          traverse_children(category, children[i], 'child');
        }
        html.push('</ul>');
      }
      html.push('</li>');
    }

    for( var i = 0; i < this._tree.length; ++i ) {
      var tree = this._tree[i];
      this._tree_visibility.push(false);
      traverse_children(tree.id, tree, 'root');
    }
    html.push('</ul>');
    this._elements.view.html(html.join(''));
    var t = this;
    $(this._elementIDs.view+' .root').each(function(index) {
      t._tree_ui[index] = $(this);
    });

    $(this._elementIDs.view+' .has_children .expander').click(function() {
      var parent = $(this).parent().parent();
      var id = parent.attr('id');
      var ul = parent.children('ul:first');
      var category = parseInt(id.substr(4, id.indexOf('-') - 4));
      var hierarchy = parseInt(id.substr(id.indexOf('-')+1));
      if( ul.css('display') != 'none' ) {
        ul.fadeOut('fast', function() {
          $(this).css({display:'block', visibility:'hidden'}).slideUp('fast');
        });
      } else {
        if( !t._is_loaded(category, hierarchy) &&
            !t._is_loading(category, hierarchy) &&
            t._any_to_load(category, hierarchy) ) {
          ul.append('<div class="loading">Grabbing the function list...</div>');
          t._request(category, hierarchy, ul);
        }
        ul.css({visibility:'hidden'}).slideDown('fast', function() {
          $(this).css({display:'none', visibility:'visible'}).fadeIn('fast');
        });
      }
    });
  },

  _request : function(category, id, ul) {
    this._function_cache[category][id].ul = ul;
    this._function_cache[category][id].loading = true;
    this._function_cache[category][id].request =
      $.ajax({
        type    : 'GET',
        url     : '/hierarchy/list',
        dataType: 'json',
        data    : {
          category  : category,
          id        : id
        },
        success : this._receive_list.bind(this),
        failure : this._fail_to_receive_list.bind(this)
      });
  },

  _receive_list : function(result, textStatus) {
    if( result.s ) {
      var category = result.c;
      var id = result.i;
      var list = result.l;

      var cache = this._function_cache[category][id];
      cache.loading = false;
      cache.loaded = true;
      cache.list = list;
      var html = [];
      for( var i = 0; i < list.length; ++i ) {
        var item = list[i];
        var id = 'fun_' + category + '' + item.id;
        html.push('<li id="',id,'" class="child"><div class="node_text">');
        html.push('<div class="the_text link_text"><tt>',item.name, '</tt></div></div>');
        html.push('</li>');
      }
      cache.ul.children('.loading').remove();
      cache.ul.append(html.join(''));
    }
  },

  _fail_to_receive_list : function(result, textStatus) {

  },

  _any_to_load : function(category, id) {
    return this._function_cache[category][id].fun_count > 0;
  },

  _is_loaded : function(category, id) {
    return this._function_cache[category][id].loaded;
  },

  _is_loading : function(category, id) {
    return this._function_cache[category][id].loading;
  },

  _receive_hier : function() {
    var hier = this._db.get_hierarchies();

    for( var category in hier ) {
      this._function_cache[category] = {};

      var list = hier[category];

      var tree = [];
      var map = {};

      this._function_cache[category][1] = {fun_count: 0};

      for( var id in list ) {
        var node = list[id];
        // node.name
        // node.ancestors
        // node.fun_count  <- Number of functions in this category.

        this._function_cache[category][id] = {fun_count: node.fun_count};

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
            tree[map[id]].fun_count = node.fun_count;
            tree[map[id]].id = id;
          } else {
            tree.push({name:node.name, id:id, fun_count:node.fun_count, map:{}, children:[]});
            map[id] = tree.length - 1;
          }
        } else {
          if( undefined != parentnode.map[id] ) {
            parentnode.children[parentnode.map[id]].name = node.name;
            parentnode.children[parentnode.map[id]].fun_count = node.fun_count;
            parentnode.children[parentnode.map[id]].id = id;
          } else {
            parentnode.children.push({name:node.name, id:id, fun_count: node.fun_count, map:{}, children:[]});
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

    function traverse_tree(node) {
      for( var i = 0; i < node.children.length; ++i ) {
        traverse_tree(node.children[i]);
      }
      node.children = node.children.sort(compare_names);
    }

    for( var i = 0; i < this._tree.length; ++i ) {
      traverse_tree(this._tree[i]);
    }

    // TODO: Takes 700ms to complete.
    this._create_ui();
    this._render();
  }

};
