/**
 * Tree-view control.
 *
 * @require-package core
 * @requires database.js
 */

Snap.TreeView = function(elementIDs, filters, typeahead) {
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
  this._typeahead = typeahead;

  this._filters.register_change_callback(this._filters_changed.bind(this));

  this._tree = [];
  this._tree_ui = [];
  this._tree_visibility = [];
  // [category][hierarchy] => tree node
  this._hierarchy_to_node = {};

  this._elements.view.html('<div class="initial_loading">Loading table of contents...</div>');

  // [category][id] => List of functions
  this._function_cache = {};

  this._db = Snap.Database.singleton;

  this._db.register_callbacks({
    receive_hier          : this._receive_hier.bind(this)
  });

  $(window).bind('resize', this._resize_frame.bind(this));
  this._resize_frame();
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

  _resize_frame : function() {
    $('#tree-view').height(($(window).height() - $('#topbar').height()) + 'px');
  },

  _create_ui : function() {
    var html = [];
    html.push('<ul class="top">');
    for( var i = 0; i < this._tree.length; ++i ) {
      var tree = this._tree[i];
      this._tree_visibility.push(false);

      var has_children = tree.children.length > 0 || tree.fun_count > 0;
      var id = 'cat_' + tree.id + '-1';
      html.push('<li id="',id,'" class="root" style="display:none"><div class="node_text root_text');
      if( has_children ) {
        html.push(' has_children');
      }
      html.push('">');
      if( has_children ) {
        html.push('<div class="expander">+</div>');
      }
      html.push('<div class="the_text">',tree.name, '</div></div>');
      if( has_children ) {
        html.push('<ul style="display:none"></ul>');
      }
      html.push('</li>');
    }
    html.push('</ul>');
    this._elements.view.html(html.join(''));
    var t = this;
    $(this._elementIDs.view+' .root').each(function(index) {
      t._tree_ui[index] = $(this);
    });

    function handle_expand() {
      if( $(this).html() == '+' ) {
        $(this).html('-');
      } else {
        $(this).html('+');
      }
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
        if( ul.children('.child').length == 0 ) {
          // This node hasn't been rendered yet.
          var node_children = t._hierarchy_to_node[category][hierarchy];

          var html = [];
          for( var i = 0; i < node_children.length; ++i ) {
            var has_children = node_children[i].children.length > 0 || node_children[i].fun_count > 0;
            var id = 'cat_' + category + '-' + node_children[i].id;
            html.push('<li id="',id,'" class="child"><div class="node_text');
            if( has_children ) {
              html.push(' has_children');
            }
            html.push('">');
            if( has_children ) {
              html.push('<div class="expander">+</div>');
            }
            html.push('<div class="the_text">',node_children[i].name, '</div></div>');
            if( has_children ) {
              html.push('<ul style="display:none"></ul>');
            }
            html.push('</li>');
          }
          ul.html(html.join(''));
          ul.children('.child').children('.has_children').children('.expander').click(handle_expand);
        }
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
    }

    $(this._elementIDs.view+' .has_children .expander').click(handle_expand);
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
      var hierarchy = result.h;
      var list = result.l;

      var cache = this._function_cache[category][hierarchy];
      cache.loading = false;
      cache.loaded = true;
      cache.list = list;
      var html = [];
      for( var i = 0; i < list.length; ++i ) {
        var item = list[i];
        var id = 'fun_' + category + '-' + item.id;
        html.push('<li id="',id,'" class="child"><div class="node_text">');
        html.push('<div class="the_text link_text"><tt>',item.name, '</tt></div></div>');
        html.push('</li>');
      }
      cache.ul.children('.loading').remove();
      cache.ul.append(html.join(''));
      var t = this;
      cache.ul.children('.child').click(function() {
        var id = $(this).attr('id');
        var category = parseInt(id.substr(4, id.indexOf('-') - 4));
        var function_id = parseInt(id.substr(id.indexOf('-')+1));
        var name = $(this).children('.node_text').children('.the_text').children('tt').html();
        t._typeahead.force_selection(category, hierarchy, function_id, name);
      });
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
      this._hierarchy_to_node[category] = {};

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

        var this_node = null;
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
          this_node = tree[map[id]];
        } else {
          if( undefined != parentnode.map[id] ) {
            parentnode.children[parentnode.map[id]].name = node.name;
            parentnode.children[parentnode.map[id]].fun_count = node.fun_count;
            parentnode.children[parentnode.map[id]].id = id;
          } else {
            parentnode.children.push({name:node.name, id:id, fun_count: node.fun_count, map:{}, children:[]});
            parentnode.map[id] = parentnode.children.length - 1;
          }
          this_node = parentnode.children[parentnode.map[id]];
        }
        this._hierarchy_to_node[category][id] = this_node.children;
      }
      this._hierarchy_to_node[category][1] = tree;

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
